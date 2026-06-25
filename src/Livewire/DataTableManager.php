<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Joelseneque\DataTableModal\Columns\Column;
use Joelseneque\DataTableModal\DataSource\ArrayStateDataSource;
use Joelseneque\DataTableModal\DataSource\DataSource;
use Joelseneque\DataTableModal\DataSource\EloquentDataSource;
use Joelseneque\DataTableModal\DataSource\Row;
use Joelseneque\DataTableModal\Support\Aggregate;
use Joelseneque\DataTableModal\Support\Footer;
use Joelseneque\DataTableModal\Support\GroupReorderer;
use Joelseneque\DataTableModal\Support\HierarchyNumberer;
use Livewire\Component;

/**
 * The interactive core. Renders the table + modal, owns all mutation methods,
 * and talks ONLY to a DataSource (Eloquent or array). Mounted by the DataTable
 * field's Blade view with serializable descriptors.
 */
class DataTableManager extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    /** @var array<string, mixed> */
    public array $sourceDescriptor = [];

    /** @var array<int, array<string, mixed>> */
    public array $columns = [];

    /** @var array<int, array<string, mixed>> */
    public array $rowActions = [];

    /** @var array<int, array<string, mixed>> */
    public array $bulkActions = [];

    public ?string $modalSchema = null;

    /** @var array<int, array<string, mixed>>|null */
    public ?array $footer = null;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<string, mixed> */
    public array $config = [];

    // UI state
    public bool $modalOpen = false;

    public string|int|null $editingRowId = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<int, string> */
    public array $selectedRowIds = [];

    public string $search = '';

    /**
     * @param  array<string, mixed>  $sourceDescriptor
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<int, array<string, mixed>>  $rowActions
     * @param  array<int, array<string, mixed>>  $bulkActions
     * @param  array<int, array<string, mixed>>|null  $footer
     * @param  array<int, array<string, mixed>>  $initialItems
     * @param  array<string, mixed>  $config
     */
    public function mount(
        array $sourceDescriptor,
        array $columns,
        array $rowActions,
        array $bulkActions,
        ?string $modalSchema,
        ?array $footer,
        array $initialItems,
        array $config,
    ): void {
        $this->sourceDescriptor = $sourceDescriptor;
        $this->columns = $columns;
        $this->rowActions = $rowActions;
        $this->bulkActions = $bulkActions;
        $this->modalSchema = $modalSchema;
        $this->footer = $footer;
        $this->config = $config;

        // In array mode the seeded rows arrive without a synthetic id. Stamp them
        // once here so every later request resolves rows against stable ids —
        // otherwise normalizeIds() would mint a fresh uuid on each render and
        // find()/update() would never match the id the browser sends back.
        if (($sourceDescriptor['type'] ?? null) === 'array') {
            $this->items = ArrayStateDataSource::fromDescriptor($sourceDescriptor)
                ->withItems($initialItems)
                ->items();
        } else {
            $this->items = $initialItems;
        }

        $this->form->fill();
        $this->recomputeFooter();
    }

    // ---- Data access ----

    protected function makeSource(): DataSource
    {
        if (($this->sourceDescriptor['type'] ?? null) === 'array') {
            return ArrayStateDataSource::fromDescriptor($this->sourceDescriptor)->withItems($this->items);
        }

        return EloquentDataSource::fromDescriptor($this->sourceDescriptor);
    }

    protected function isArrayMode(): bool
    {
        return ($this->sourceDescriptor['type'] ?? null) === 'array';
    }

    /**
     * @return array<int, Row>
     */
    public function getRowsProperty(): array
    {
        $rows = $this->makeSource()->all();

        if ($this->search !== '' && ($this->config['searchable'] ?? false)) {
            $needle = mb_strtolower($this->search);
            $rows = array_values(array_filter($rows, function (Row $row) use ($needle): bool {
                foreach ($row->attributes as $value) {
                    if (is_scalar($value) && str_contains(mb_strtolower((string) $value), $needle)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        return $rows;
    }

    /**
     * @return array<int, Column>
     */
    public function getColumnObjectsProperty(): array
    {
        return array_map(fn (array $d): Column => Column::fromDescriptor($d), $this->columns);
    }

    /**
     * @return array<string, string>
     */
    public function getNumbersProperty(): array
    {
        if (! ($this->config['numbering'] ?? false)) {
            return [];
        }

        return HierarchyNumberer::number($this->rows);
    }

    // ---- Modal form ----

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeSchema()->statePath('data'),
            ),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->modalComponents())->columns(2);
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected function modalComponents(): array
    {
        if ($this->modalSchema !== null) {
            $closure = unserialize($this->modalSchema)->getClosure();

            return $closure();
        }

        // Fallback: derive a simple schema from inline-editable / named columns.
        return array_map(
            fn (Column $column) => TextInput::make($column->getName())->label($column->getLabel())->columnSpanFull(),
            $this->columnObjects,
        );
    }

    public function openCreateModal(): void
    {
        if ($this->isDisabled() || $this->hasReachedMax()) {
            return;
        }

        $this->editingRowId = null;
        $this->form->fill($this->defaultRowAttributes());
        $this->modalOpen = true;
    }

    public function openEditModal(string|int $rowId): void
    {
        $row = $this->makeSource()->find($rowId);

        if ($row === null) {
            return;
        }

        $this->editingRowId = $rowId;
        $this->form->fill($row->attributes);
        $this->modalOpen = true;
    }

    public function save(): void
    {
        if ($this->isDisabled()) {
            return;
        }

        $data = $this->form->getState();
        $source = $this->makeSource();

        if ($this->editingRowId !== null) {
            $row = $source->update($this->editingRowId, $data);
            $this->afterMutation($source);
            $this->runHook('afterRowUpdated', $row, $source);
        } else {
            $row = $source->create($data);
            $this->afterMutation($source);
            $this->runHook('afterRowCreated', $row, $source);
        }

        $this->closeModal();
        $this->recomputeFooter();
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        $this->editingRowId = null;
        $this->form->fill();
    }

    // ---- Inline editing ----

    public function updateField(string|int $rowId, string $field, mixed $value): void
    {
        if ($this->isDisabled() || ! $this->columnIsInlineEditable($field)) {
            return;
        }

        $source = $this->makeSource();
        $source->update($rowId, [$field => $value]);
        $this->afterMutation($source);
        $this->recomputeFooter();
    }

    public function toggleColumn(string|int $rowId, string $field): void
    {
        if ($this->isDisabled() || ! $this->columnIsInlineToggle($field)) {
            return;
        }

        $source = $this->makeSource();
        $current = $source->find($rowId)?->get($field);
        $source->update($rowId, [$field => ! $current]);
        $this->afterMutation($source);
        $this->recomputeFooter();
    }

    // ---- Reordering ----

    public function moveUp(string|int $rowId): void
    {
        $this->applyReorder(GroupReorderer::move($this->rows, $rowId, 'up'));
    }

    public function moveDown(string|int $rowId): void
    {
        $this->applyReorder(GroupReorderer::move($this->rows, $rowId, 'down'));
    }

    /**
     * @param  array<int, string|int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        // Drag-and-drop produces a flat list; re-thread children under parents.
        $normalized = ($this->config['parentChild'] ?? false)
            ? GroupReorderer::normalize($this->rows, $orderedIds)
            : $orderedIds;

        $this->applyReorder($normalized);
    }

    /**
     * @param  array<int, string|int>  $orderedIds
     */
    protected function applyReorder(array $orderedIds): void
    {
        if ($this->isDisabled()) {
            return;
        }

        $source = $this->makeSource();
        $source->reorder($orderedIds);
        $this->afterMutation($source);
        $this->runHook('afterReordered', null, $source);
        $this->recomputeFooter();
    }

    // ---- Row actions ----

    public function deleteRow(string|int $rowId): void
    {
        if ($this->isDisabled() || ! $this->hasReachedMin()) {
            return;
        }

        $source = $this->makeSource();
        $row = $source->find($rowId);
        $source->delete($rowId);
        $this->afterMutation($source);
        $this->runHook('afterRowDeleted', $row, $source);
        $this->recomputeFooter();
    }

    public function duplicateRow(string|int $rowId): void
    {
        if ($this->isDisabled() || $this->hasReachedMax()) {
            return;
        }

        $source = $this->makeSource();
        $row = $source->duplicate($rowId);
        $this->afterMutation($source);
        $this->runHook('afterRowCreated', $row, $source);
        $this->recomputeFooter();
    }

    public function runRowAction(string $name, string|int $rowId): void
    {
        $descriptor = collect($this->rowActions)->firstWhere('name', $name);

        if ($descriptor === null) {
            return;
        }

        if (! empty($descriptor['dispatch_event'])) {
            $this->dispatch($descriptor['dispatch_event'], id: $rowId);

            return;
        }

        if (! empty($descriptor['handler'])) {
            $source = $this->makeSource();
            $handler = unserialize($descriptor['handler'])->getClosure();
            $handler($source->find($rowId), $source);
            $this->afterMutation($source);
            $this->recomputeFooter();
        }
    }

    // ---- Selection + bulk ----

    public function toggleSelectAll(): void
    {
        $allIds = array_map(fn (Row $row): string => (string) $row->id, $this->rows);

        $this->selectedRowIds = count($this->selectedRowIds) === count($allIds) ? [] : $allIds;
    }

    public function runBulkAction(string $name): void
    {
        $descriptor = collect($this->bulkActions)->firstWhere('name', $name);

        if ($descriptor === null || $this->selectedRowIds === []) {
            return;
        }

        $source = $this->makeSource();

        if (($descriptor['type'] ?? null) === 'delete') {
            foreach ($this->selectedRowIds as $id) {
                $source->delete($id);
            }
        } elseif (! empty($descriptor['dispatch_event'])) {
            $this->dispatch($descriptor['dispatch_event'], ids: $this->selectedRowIds);

            return;
        } elseif (! empty($descriptor['handler'])) {
            $rows = collect($this->selectedRowIds)->map(fn ($id) => $source->find($id))->filter();
            $handler = unserialize($descriptor['handler'])->getClosure();
            $handler($rows, $source);
        }

        $this->selectedRowIds = [];
        $this->afterMutation($source);
        $this->recomputeFooter();
    }

    // ---- Footer / totals bridge ----

    public function recomputeFooter(): void
    {
        if (empty($this->footer)) {
            return;
        }

        $footer = new Footer(array_map(
            fn (array $d) => $this->aggregateFromDescriptor($d),
            $this->footer,
        ));

        $values = $footer->compute($this->rows);

        $this->dispatch('data-table:totals', statePath: $this->config['statePath'] ?? null, values: $values);
    }

    /**
     * @param  array<string, mixed>  $descriptor
     */
    protected function aggregateFromDescriptor(array $descriptor): Aggregate
    {
        $type = $descriptor['type'];
        $aggregate = match ($type) {
            'sum' => Aggregate::sum($descriptor['column']),
            'avg' => Aggregate::avg($descriptor['column']),
            'count' => Aggregate::count(),
            default => Aggregate::custom(unserialize($descriptor['custom'])->getClosure()),
        };

        if (! empty($descriptor['where'])) {
            $aggregate->where(unserialize($descriptor['where'])->getClosure());
        }

        if (! empty($descriptor['dispatch_to'])) {
            $aggregate->dispatchTo($descriptor['dispatch_to']);
        }

        return $aggregate->round($descriptor['round'] ?? 2);
    }

    // ---- Helpers ----

    protected function afterMutation(DataSource $source): void
    {
        if ($this->isArrayMode() && $source instanceof ArrayStateDataSource) {
            $this->items = $source->items();
            $this->dispatch(
                'data-table:sync',
                statePath: $this->config['statePath'] ?? null,
                items: $this->items,
            );
        }

        unset($this->rows);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultRowAttributes(): array
    {
        $default = $this->config['defaultRow'] ?? [];

        if (is_string($default)) {
            return (array) unserialize($default)->getClosure()();
        }

        return (array) $default;
    }

    /**
     * The heading shown in the modal header. A configured Closure is resolved
     * against the row being edited (null when creating) and its 1-based
     * position, so headings like "Unit 3" can track the row. Falls back to the
     * static string heading, or null so the view shows its Add/Edit default.
     */
    public function modalHeadingText(): ?string
    {
        $resolver = $this->config['modalHeadingResolver'] ?? null;

        if ($resolver !== null) {
            $row = $this->editingRowId !== null
                ? $this->makeSource()->find($this->editingRowId)
                : null;

            $position = $row !== null ? $row->order + 1 : count($this->rows) + 1;

            return (string) unserialize($resolver)->getClosure()($row, $position);
        }

        return $this->config['modalHeading'] ?? null;
    }

    protected function columnIsInlineEditable(string $field): bool
    {
        return collect($this->columns)->contains(
            fn (array $c): bool => $c['name'] === $field && ($c['inline_editable'] ?? false),
        );
    }

    protected function columnIsInlineToggle(string $field): bool
    {
        return collect($this->columns)->contains(
            fn (array $c): bool => $c['name'] === $field && ($c['inline_toggle'] ?? false),
        );
    }

    public function rowActionVisible(string $name, Row $row): bool
    {
        $descriptor = collect($this->rowActions)->firstWhere('name', $name);

        if ($descriptor === null) {
            return false;
        }

        $visible = $descriptor['visible'] ?? true;

        if (is_string($visible)) {
            return (bool) unserialize($visible)->getClosure()($row);
        }

        return (bool) $visible;
    }

    public function isDisabled(): bool
    {
        return (bool) ($this->config['disabled'] ?? false);
    }

    protected function hasReachedMax(): bool
    {
        $max = $this->config['maxItems'] ?? null;

        return $max !== null && count($this->rows) >= $max;
    }

    protected function hasReachedMin(): bool
    {
        $min = $this->config['minItems'] ?? null;

        return $min === null || count($this->rows) > $min;
    }

    protected function runHook(string $name, ?Row $row, DataSource $source): void
    {
        $hook = $this->config['hooks'][$name] ?? null;

        if (! empty($hook)) {
            unserialize($hook)->getClosure()($row, $source);
        }
    }

    public function render()
    {
        return view('data-table-modal::livewire.data-table-manager');
    }
}

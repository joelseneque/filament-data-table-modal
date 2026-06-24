<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal;

use Closure;
use Filament\Forms\Components\Field;
use Joelseneque\DataTableModal\Actions\BulkAction;
use Joelseneque\DataTableModal\Actions\RowAction;
use Joelseneque\DataTableModal\Columns\Column;
use Joelseneque\DataTableModal\DataSource\ArrayStateDataSource;
use Joelseneque\DataTableModal\DataSource\EloquentDataSource;
use Joelseneque\DataTableModal\Enums\ModalWidth;
use Joelseneque\DataTableModal\Livewire\DataTableManager;
use Joelseneque\DataTableModal\Support\Footer;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * The Filament form field. A pure configuration carrier: it collects all options
 * and produces a serializable payload that its Blade view uses to mount the
 * DataTableManager Livewire component (which owns all interactivity).
 *
 * @see DataTableManager
 */
class DataTable extends Field
{
    protected string $view = 'data-table-modal::forms.components.data-table';

    // Data source
    protected ?string $sourceModel = null;

    protected ?string $sourceRelationship = null;

    protected ?string $ownerKey = null;

    protected string $orderColumn = 'order';

    protected bool $arrayState = false;

    // Hierarchy
    protected bool $parentChild = false;

    protected string $parentColumn = 'parent_id';

    protected bool $numbering = false;

    protected ?string $summaryColumn = null;

    protected ?string $childNumberingColumn = null;

    // Ordering
    protected bool $reorderable = false;

    protected bool $dragAndDrop = false;

    // Columns / actions / selection / bulk
    protected array|Closure $columnsConfig = [];

    protected array|Closure $rowActions = [];

    protected bool $selectable = false;

    protected array|Closure $bulkActions = [];

    // Modal
    protected ?Closure $modalSchema = null;

    protected bool $slideOver = true;

    protected ModalWidth|string $modalWidth = ModalWidth::TwoExtraLarge;

    protected string|Closure|null $modalHeading = null;

    // Footer
    protected Footer|Closure|null $footer = null;

    // Extras
    protected string|Closure|null $emptyStateHeading = null;

    protected string|Closure|null $emptyStateIcon = null;

    protected int|Closure|null $minItems = null;

    protected int|Closure|null $maxItems = null;

    protected array|Closure|null $defaultRow = null;

    protected bool $searchable = false;

    protected int|bool $paginate = false;

    protected ?bool $confirmDeleteOverride = null;

    protected bool $persistImmediately = true;

    protected ?Closure $recordClasses = null;

    // Lifecycle hooks
    protected ?Closure $afterRowCreated = null;

    protected ?Closure $afterRowUpdated = null;

    protected ?Closure $afterRowDeleted = null;

    protected ?Closure $afterReordered = null;

    // ---- DATA SOURCE ----

    public function records(string $modelClass): static
    {
        $this->sourceModel = $modelClass;

        return $this;
    }

    public function relationship(string $name): static
    {
        $this->sourceRelationship = $name;

        return $this;
    }

    public function ownerKey(string $foreignKey): static
    {
        $this->ownerKey = $foreignKey;

        return $this;
    }

    public function orderColumn(string $column = 'order'): static
    {
        $this->orderColumn = $column;

        return $this;
    }

    public function arrayState(bool $condition = true): static
    {
        $this->arrayState = $condition;

        return $this;
    }

    // ---- HIERARCHY ----

    public function parentChild(bool $condition = true): static
    {
        $this->parentChild = $condition;

        return $this;
    }

    public function tree(bool $condition = true): static
    {
        return $this->parentChild($condition);
    }

    public function parentColumn(string $column = 'parent_id'): static
    {
        $this->parentColumn = $column;

        return $this;
    }

    public function numbering(bool $condition = true): static
    {
        $this->numbering = $condition;

        return $this;
    }

    public function summaryColumn(string $column = 'is_summary_item'): static
    {
        $this->summaryColumn = $column;

        return $this;
    }

    public function childNumberingColumn(string $column = 'add_numbering'): static
    {
        $this->childNumberingColumn = $column;

        return $this;
    }

    // ---- ORDERING ----

    public function reorderable(bool $condition = true): static
    {
        $this->reorderable = $condition;

        return $this;
    }

    public function reorderableWithDragAndDrop(bool $condition = true): static
    {
        $this->reorderable = $condition;
        $this->dragAndDrop = $condition;

        return $this;
    }

    // ---- COLUMNS / ACTIONS / SELECTION / BULK ----

    public function tableColumns(array|Closure $columns): static
    {
        $this->columnsConfig = $columns;

        return $this;
    }

    public function rowActions(array|Closure $actions): static
    {
        $this->rowActions = $actions;

        return $this;
    }

    public function selectable(bool $condition = true): static
    {
        $this->selectable = $condition;

        return $this;
    }

    public function bulkActions(array|Closure $actions): static
    {
        $this->bulkActions = $actions;

        return $this;
    }

    // ---- MODAL ----

    public function modalSchema(Closure $schema): static
    {
        $this->modalSchema = $schema;

        return $this;
    }

    public function slideOver(bool $condition = true): static
    {
        $this->slideOver = $condition;

        return $this;
    }

    public function modalWidth(ModalWidth|string $width = ModalWidth::TwoExtraLarge): static
    {
        $this->modalWidth = $width;

        return $this;
    }

    public function modalHeading(string|Closure|null $heading): static
    {
        $this->modalHeading = $heading;

        return $this;
    }

    // ---- FOOTER ----

    public function footer(Footer|Closure $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    // ---- EXTRAS ----

    public function emptyStateHeading(string|Closure $text): static
    {
        $this->emptyStateHeading = $text;

        return $this;
    }

    public function emptyStateIcon(string|Closure $icon): static
    {
        $this->emptyStateIcon = $icon;

        return $this;
    }

    public function minItems(int|Closure $count): static
    {
        $this->minItems = $count;

        return $this;
    }

    public function maxItems(int|Closure $count): static
    {
        $this->maxItems = $count;

        return $this;
    }

    public function defaultRow(array|Closure $attributes): static
    {
        $this->defaultRow = $attributes;

        return $this;
    }

    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function paginate(int|bool $perPage = 25): static
    {
        $this->paginate = $perPage;

        return $this;
    }

    public function confirmDelete(bool $condition = true): static
    {
        $this->confirmDeleteOverride = $condition;

        return $this;
    }

    public function persistImmediately(bool $condition = true): static
    {
        $this->persistImmediately = $condition;

        return $this;
    }

    public function recordClasses(Closure $callback): static
    {
        $this->recordClasses = $callback;

        return $this;
    }

    public function afterRowCreated(Closure $callback): static
    {
        $this->afterRowCreated = $callback;

        return $this;
    }

    public function afterRowUpdated(Closure $callback): static
    {
        $this->afterRowUpdated = $callback;

        return $this;
    }

    public function afterRowDeleted(Closure $callback): static
    {
        $this->afterRowDeleted = $callback;

        return $this;
    }

    public function afterReordered(Closure $callback): static
    {
        $this->afterReordered = $callback;

        return $this;
    }

    // ---- PAYLOAD ----

    /**
     * The serializable payload the Blade view passes to the Livewire component.
     *
     * @return array<string, mixed>
     */
    public function getMountData(): array
    {
        return [
            'sourceDescriptor' => $this->buildSourceDescriptor(),
            'columns' => array_map(fn ($c) => $c->toDescriptor(), $this->getResolvedColumns()),
            'rowActions' => array_map(fn ($a) => $a->toDescriptor(), $this->getResolvedRowActions()),
            'bulkActions' => array_map(fn ($a) => $a->toDescriptor(), $this->getResolvedBulkActions()),
            'modalSchema' => $this->modalSchema !== null
                ? serialize(new SerializableClosure($this->modalSchema))
                : null,
            'footer' => $this->getResolvedFooter()?->toDescriptor(),
            'initialItems' => $this->arrayState ? $this->getArrayStateItems() : [],
            'config' => $this->buildConfig(),
        ];
    }

    /**
     * @return array<int, Column>
     */
    public function getResolvedColumns(): array
    {
        return $this->evaluate($this->columnsConfig) ?: [];
    }

    /**
     * @return array<int, RowAction>
     */
    public function getResolvedRowActions(): array
    {
        return $this->evaluate($this->rowActions) ?: [];
    }

    /**
     * @return array<int, BulkAction>
     */
    public function getResolvedBulkActions(): array
    {
        return $this->evaluate($this->bulkActions) ?: [];
    }

    public function getResolvedFooter(): ?Footer
    {
        $footer = $this->evaluate($this->footer);

        return $footer instanceof Footer ? $footer : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSourceDescriptor(): array
    {
        if ($this->arrayState) {
            return (new ArrayStateDataSource(
                items: [],
                parentColumn: $this->parentChild ? $this->parentColumn : null,
                summaryColumn: $this->summaryColumn,
                childNumberingColumn: $this->childNumberingColumn,
            ))->toDescriptor();
        }

        return (new EloquentDataSource(
            model: $this->resolveModelClass(),
            ownerKey: $this->ownerKey,
            ownerId: $this->resolveOwnerId(),
            orderColumn: $this->orderColumn,
            parentColumn: $this->parentChild ? $this->parentColumn : null,
            summaryColumn: $this->summaryColumn,
            childNumberingColumn: $this->childNumberingColumn,
        ))->toDescriptor();
    }

    protected function resolveModelClass(): string
    {
        if ($this->sourceModel !== null) {
            return $this->sourceModel;
        }

        if ($this->sourceRelationship !== null && ($record = $this->getRecord()) !== null) {
            return $record->{$this->sourceRelationship}()->getRelated()::class;
        }

        throw new \LogicException('DataTable requires ->records() or ->relationship() in Eloquent mode, or ->arrayState() for array mode.');
    }

    protected function resolveOwnerId(): string|int|null
    {
        return $this->getRecord()?->getKey();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getArrayStateItems(): array
    {
        $state = $this->getState();

        return is_array($state) ? array_values($state) : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildConfig(): array
    {
        return [
            'statePath' => $this->getStatePath(),
            'parentChild' => $this->parentChild,
            'parentColumn' => $this->parentColumn,
            'numbering' => $this->numbering,
            'summaryColumn' => $this->summaryColumn,
            'reorderable' => $this->reorderable,
            'dragAndDrop' => $this->dragAndDrop,
            'selectable' => $this->selectable,
            'slideOver' => $this->slideOver,
            'modalWidth' => $this->modalWidth instanceof ModalWidth ? $this->modalWidth->value : $this->modalWidth,
            'modalHeading' => is_string($this->modalHeading) ? $this->modalHeading : null,
            'emptyStateHeading' => is_string($this->emptyStateHeading) ? $this->emptyStateHeading : null,
            'emptyStateIcon' => is_string($this->emptyStateIcon) ? $this->emptyStateIcon : null,
            'minItems' => $this->evaluate($this->minItems),
            'maxItems' => $this->evaluate($this->maxItems),
            'defaultRow' => $this->defaultRow instanceof Closure
                ? serialize(new SerializableClosure($this->defaultRow))
                : ($this->defaultRow ?? []),
            'searchable' => $this->searchable,
            'paginate' => $this->paginate,
            'confirmDelete' => $this->confirmDeleteOverride ?? (bool) config('data-table-modal.confirm_delete', true),
            'persistImmediately' => $this->persistImmediately,
            'disabled' => $this->isDisabled(),
            'recordClasses' => $this->recordClasses !== null
                ? serialize(new SerializableClosure($this->recordClasses))
                : null,
            'hooks' => array_filter([
                'afterRowCreated' => $this->serializeHook($this->afterRowCreated),
                'afterRowUpdated' => $this->serializeHook($this->afterRowUpdated),
                'afterRowDeleted' => $this->serializeHook($this->afterRowDeleted),
                'afterReordered' => $this->serializeHook($this->afterReordered),
            ]),
        ];
    }

    protected function serializeHook(?Closure $closure): ?string
    {
        return $closure !== null ? serialize(new SerializableClosure($closure)) : null;
    }
}

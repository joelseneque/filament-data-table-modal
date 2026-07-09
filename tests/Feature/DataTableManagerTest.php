<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Joelseneque\DataTableModal\Actions\RowAction;
use Joelseneque\DataTableModal\Columns\Column;
use Joelseneque\DataTableModal\DataSource\ArrayStateDataSource;
use Joelseneque\DataTableModal\Livewire\DataTableManager;
use Laravel\SerializableClosure\SerializableClosure;
use Livewire\Livewire;

function mountData(array $initialItems = []): array
{
    $source = new ArrayStateDataSource(items: [], parentColumn: null);

    return [
        'sourceDescriptor' => $source->toDescriptor(),
        'columns' => [
            Column::make('name')->inlineEditable()->toDescriptor(),
            Column::make('qty')->inlineEditable()->toDescriptor(),
        ],
        'rowActions' => [
            RowAction::edit()->toDescriptor(),
            RowAction::delete()->toDescriptor(),
        ],
        'bulkActions' => [],
        'modalSchema' => serialize(new SerializableClosure(fn () => [
            TextInput::make('name'),
            TextInput::make('qty'),
        ])),
        'footer' => null,
        'initialItems' => $initialItems,
        'config' => [
            'statePath' => 'data.items',
            'parentChild' => false,
            'numbering' => false,
            'reorderable' => true,
            'dragAndDrop' => false,
            'selectable' => true,
            'searchable' => false,
            'confirmDelete' => true,
            'disabled' => false,
            'persistImmediately' => false,
            'hooks' => [],
        ],
    ];
}

it('renders the manager component', function () {
    Livewire::test(DataTableManager::class, mountData())
        ->assertOk();
});

it('applies a modal field default when opening the create modal', function () {
    $mount = mountData();
    $mount['modalSchema'] = serialize(new SerializableClosure(fn () => [
        TextInput::make('name'),
        TextInput::make('qty')->numeric()->default(12),
    ]));

    Livewire::test(DataTableManager::class, $mount)
        ->call('openCreateModal')
        ->assertSet('data.qty', 12);
});

it('overlays defaultRow attributes over component defaults on create', function () {
    $mount = mountData();
    $mount['modalSchema'] = serialize(new SerializableClosure(fn () => [
        TextInput::make('name')->default('Unnamed'),
        TextInput::make('qty')->numeric()->default(12),
    ]));
    $mount['config']['defaultRow'] = ['qty' => 5];

    Livewire::test(DataTableManager::class, $mount)
        ->call('openCreateModal')
        ->assertSet('data.name', 'Unnamed') // component default kept
        ->assertSet('data.qty', 5);         // defaultRow overrides it
});

it('creates a row through the modal and syncs array state', function () {
    Livewire::test(DataTableManager::class, mountData())
        ->call('openCreateModal')
        ->assertSet('modalOpen', true)
        ->set('data.name', 'Widget')
        ->set('data.qty', 3)
        ->call('save')
        ->assertSet('modalOpen', false)
        ->assertCount('items', 1)
        ->assertDispatched('data-table:sync');
});

it('stamps stable ids on rows seeded without an __id', function () {
    $component = Livewire::test(DataTableManager::class, mountData([
        ['name' => 'Seeded', 'qty' => 2],
    ]));

    // The row arrived without an __id; mount() must stamp one so it persists.
    $id = $component->get('items')[0]['__id'];

    expect($id)->not->toBeEmpty();

    // The same id must resolve the row on a later request (edit + inline edit).
    $component->call('openEditModal', $id)
        ->assertSet('modalOpen', true)
        ->assertSet('editingRowId', $id);

    $component->call('updateField', $id, 'name', 'Renamed');

    expect($component->get('items')[0]['name'])->toBe('Renamed')
        ->and($component->get('items')[0]['__id'])->toBe($id);
});

it('edits a row inline', function () {
    $component = Livewire::test(DataTableManager::class, mountData())
        ->call('openCreateModal')
        ->set('data.name', 'Old')
        ->set('data.qty', 1)
        ->call('save');

    $id = $component->get('items')[0]['__id'];

    $component->call('updateField', $id, 'name', 'New');

    expect($component->get('items')[0]['name'])->toBe('New');
});

it('deletes a row', function () {
    $component = Livewire::test(DataTableManager::class, mountData())
        ->call('openCreateModal')
        ->set('data.name', 'Temp')
        ->set('data.qty', 1)
        ->call('save');

    $id = $component->get('items')[0]['__id'];

    $component->call('deleteRow', $id)
        ->assertCount('items', 0);
});

it('resolves a closure modal heading against the row position', function () {
    $mount = mountData([
        ['name' => 'A', 'qty' => 1],
        ['name' => 'B', 'qty' => 2],
    ]);
    $mount['config']['modalHeadingResolver'] = serialize(new SerializableClosure(
        fn ($row, int $number): string => "Unit {$number}"
    ));

    $component = Livewire::test(DataTableManager::class, $mount);

    // Creating: heading reflects the next position (count + 1).
    expect($component->instance()->modalHeadingText())->toBe('Unit 3');

    // Editing the second row: heading reflects its 1-based position.
    $secondId = $component->get('items')[1]['__id'];
    $component->call('openEditModal', $secondId);

    expect($component->instance()->modalHeadingText())->toBe('Unit 2');
});

it('falls back to the static string modal heading', function () {
    $mount = mountData();
    $mount['config']['modalHeading'] = 'Line item';

    expect(Livewire::test(DataTableManager::class, $mount)->instance()->modalHeadingText())
        ->toBe('Line item');
});

it('reorders rows with move down', function () {
    $component = Livewire::test(DataTableManager::class, mountData());

    foreach (['A', 'B', 'C'] as $name) {
        $component->call('openCreateModal')->set('data.name', $name)->set('data.qty', 1)->call('save');
    }

    $firstId = $component->get('items')[0]['__id'];

    $component->call('moveDown', $firstId);

    expect(array_column($component->get('items'), 'name'))->toBe(['B', 'A', 'C']);
});

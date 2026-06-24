# data-table-modal — agent cheat-sheet

`joelseneque/data-table-modal` is a **Filament v5 form field** that renders an
interactive table + slide-over/modal editor. It is a **standalone plugin** (no
Panel `Plugin` contract). Use this file to orient quickly when the package shows
up in `vendor/` of a consuming app, or when working on the package itself.

## What it does
A condensed editable table with: inline-edit cells, inline toggles, parent/child
grouping + numbering, group-aware reorder (buttons or SortableJS drag-drop), row
actions (edit/delete/duplicate/custom), selection + bulk actions, a modal that
embeds a real Filament form, and footer aggregates pushed into sibling form
fields. Data comes from **an Eloquent model OR array state** behind one interface.

## Architecture (how it fits together)
- `src/DataTable.php` — the **Field** (extends `Filament\Forms\Components\Field`).
  Pure config carrier. Its view `resources/views/forms/components/data-table.blade.php`
  mounts the Livewire component with a serializable payload from `getMountData()`,
  and bridges `data-table:totals` / `data-table:sync` browser events back into the
  parent form's fields.
- `src/Livewire/DataTableManager.php` — the **interactive core** (a real Livewire
  component, registered as `data-table-modal-manager`). Owns all mutation methods
  and the embedded modal form. Talks ONLY to a `DataSource`. View:
  `resources/views/livewire/data-table-manager.blade.php` (+ `partials/modal*.blade.php`).
- `src/DataSource/` — `DataSource` interface + `EloquentDataSource` (immediate
  persistence, writes `order` column) + `ArrayStateDataSource` (deferred, array
  state, UUID ids) + `Row` DTO (`id, attributes[], parentId, order, meta[]`).
- `src/Columns/Column.php` — column config + per-cell formatting/inline-edit flags.
- `src/Actions/{RowAction,BulkAction}.php` — built-in types (edit/delete/duplicate)
  handled in the component; custom ones via `->dispatch()` or `->action(Closure)`.
- `src/Support/` — `GroupReorderer` (parent+children move as a unit),
  `HierarchyNumberer` (1,2,3 / a,b,c / summary reset), `Footer` + `Aggregate`
  (totals bridge).

## Key constraint that shaped the design
A Filament custom `Field` is **not** a Livewire component, so interactivity lives
in the nested `DataTableManager`. Config crosses that boundary as **serializable
descriptors** — every config object has `toDescriptor()`/`fromDescriptor()`, and
closures (column callbacks, action handlers, footer where/custom, lifecycle hooks,
`modalSchema`) are wrapped with `Laravel\SerializableClosure`. That's why
`->modalSchema()` requires a **Closure** returning components, not an array.

## Two gotchas when writing the field config
- Use `->records(Model::class)` — NOT `->model()` (collides with `Field::model()`).
- Use `->tableColumns([...])` — NOT `->columns()` (collides with the grid
  column-count method on every Filament component).

## Minimal usage
```php
DataTable::make('items')
    ->records(LineItem::class)->ownerKey('variation_id')->orderColumn('order')
    ->tableColumns([Column::make('name')->inlineEditable()])
    ->rowActions([RowAction::edit(), RowAction::delete()->requiresConfirmation()])
    ->modalSchema(fn () => [TextInput::make('name')->required()]);
// array mode: ->arrayState() instead of ->records()/->ownerKey()
```
Full API: `docs/usage.md`. Human intro: `README.md`.

## Data modes
- **Model** (`->records()`): immediate DB writes; field reads owner id from the
  form record → put it on an edit page. Reorder writes `order`.
- **Array** (`->arrayState()`): deferred; rows in field state, synced to the parent
  via `data-table:sync`. Reorder reindexes.

## Working on the package
- Tests: `composer test` (Pest + Orchestra Testbench; `tests/TestCase.php` boots
  Filament's Support/Forms/Schemas/Actions providers + Livewire). 33 tests cover
  the data sources, reorder/numbering, column formatting, and the Livewire
  component in both modes. Add/extend tests for any change.
- Format: `vendor/bin/pint` before finishing.
- Assets: `npm run build` rebuilds `resources/dist/data-table-modal.js`
  (SortableJS). Registered via `FilamentAsset` in the service provider.
- Don't reintroduce `->model()`/`->columns()` names; don't pass closures across the
  field→component boundary without `SerializableClosure`.
```

# Usage

Full reference for the `DataTable` field.

## Data source

### Eloquent (model mode)

```php
DataTable::make('line_items')
    ->records(LineItem::class)   // NOTE: ->records(), not ->model()
    ->ownerKey('variation_id')   // FK set on new rows; scopes queries to the owner record
    ->orderColumn('order')       // column used for ordering (default 'order')
```

Or resolve the model from a relationship on the form's record:

```php
DataTable::make('line_items')
    ->relationship('lineItems')  // uses $record->lineItems()->getRelated()
    ->ownerKey('variation_id')
```

### Array (repeater mode)

```php
DataTable::make('items')->arrayState()
```

Rows are stored in the field's own array state and persisted with the parent form.
Each row carries a synthetic `__id`.

## Hierarchy (parent / child)

```php
->parentChild()                       // alias: ->tree()
->parentColumn('parent_id')           // self-referencing column (default 'parent_id')
->numbering()                         // parents 1,2,3 ; children a,b,c
->summaryColumn('is_summary_item')    // rows flagged here are unnumbered + reset the sequence
->childNumberingColumn('add_numbering') // per-child opt-in to lettering
```

Parents move together with their children; children reorder only among siblings.

## Ordering

```php
->reorderable()                  // up/down buttons (no JS needed)
->reorderableWithDragAndDrop()   // SortableJS drag handles (needs built assets)
```

## Columns

```php
use Joelseneque\DataTableModal\Columns\Column;
use Joelseneque\DataTableModal\Columns\Enums\{Alignment, ColumnFormat};

->tableColumns([                  // NOTE: ->tableColumns(), not ->columns()
    Column::make('name')
        ->label('Item')
        ->inlineEditable()                       // click-to-edit text/number in the cell
        ->align(Alignment::Left)
        ->width('w-auto'),

    Column::make('price')
        ->currency('$', 2)                       // or ->format(ColumnFormat::Currency)
        ->align(Alignment::Right)
        ->inlineEditable(),

    Column::make('active')
        ->inlineToggle(),                        // boolean toggle switch in the cell

    Column::make('description')
        ->format(ColumnFormat::Html)
        ->opensModal(),                          // click opens the modal for rich editing

    Column::make('status')
        ->getStateUsing(fn ($state, $row) => $state ?? 'draft')
        ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

    Column::make('secret')->visible(fn () => auth()->user()->isAdmin()),
])
```

Formats: `Text`, `Number`, `Currency`, `Date`, `Boolean`, `Badge`, `Html`.
`Badge`/`Html` are rendered unescaped.

## Row actions

```php
use Joelseneque\DataTableModal\Actions\RowAction;

->rowActions([
    RowAction::edit(),                              // opens the modal
    RowAction::duplicate(),                         // clones the row
    RowAction::delete()->requiresConfirmation(),    // deletes (browser confirm)

    // custom — either dispatch a host event…
    RowAction::make('approve')->label('Approve')->dispatch('approve-row'),
    // …or run a closure (serialized into Livewire state; keep it serializable)
    RowAction::make('flag')
        ->label('Flag')
        ->visible(fn ($row) => ! $row->isSummary())
        ->action(fn ($row, $source) => $source->update($row->id, ['flagged' => true])),
])
```

## Selection + bulk actions

```php
use Joelseneque\DataTableModal\Actions\BulkAction;

->selectable()
->bulkActions([
    BulkAction::delete(),                           // deletes selected rows
    BulkAction::make('export')->label('Export')->dispatch('export-rows'),
    BulkAction::make('archive')
        ->action(fn ($rows, $source) => $rows->each(fn ($r) => $source->update($r->id, ['archived' => true]))),
])
```

## Modal

```php
use Joelseneque\DataTableModal\Enums\ModalWidth;
use Filament\Forms\Components\{TextInput, Textarea, Select, Toggle};

->modalSchema(fn () => [                            // MUST be a Closure returning components
    TextInput::make('name')->required(),
    Textarea::make('notes')->columnSpanFull(),
    Select::make('parent_id')->relationship('parent', 'name'),
    Toggle::make('active'),
])
->slideOver()                                       // or ->slideOver(false) for a centered modal
->modalWidth(ModalWidth::TwoExtraLarge)             // sm … 7xl, screen
->modalHeading('Line item')
```

`modalSchema` is a closure (not an array) so its components can be rebuilt
server-side inside the Livewire component — Filament components are not
serializable.

## Footer aggregates (the "totals bridge")

Compute values from the rows and push them into **other fields on the same form**
(e.g. a subtotal/GST/total). Mirrors the original quote-totals behaviour.

```php
use Joelseneque\DataTableModal\Support\{Footer, Aggregate};

->footer(Footer::make()
    ->aggregate(
        Aggregate::sum('price')
            ->where(fn ($row) => ! $row->get('price_included'))
            ->dispatchTo('subtotal')                // writes to the 'subtotal' form field
    )
    ->aggregate(Aggregate::custom(fn ($rows) => collect($rows)->sum(fn ($r) => $r->get('price') * 0.1))->dispatchTo('gst_amount'))
    ->aggregate(Aggregate::count()->dispatchTo('line_count'))
)
```

The named fields (`subtotal`, `gst_amount`, …) must exist in the surrounding form.

## Extras

```php
->emptyStateHeading('No items yet')->emptyStateIcon('heroicon-o-table-cells')
->minItems(1)->maxItems(20)
->defaultRow(['gst' => true])                       // seeds the create modal
->searchable()
->paginate(25)
->confirmDelete()
->persistImmediately(false)                         // model-backed but deferred saving
->disableLiveSave()                                 // array-state: don't auto-save the host form
->liveSave(method: 'create')                        // array-state: call a different host method
->recordClasses(fn ($row) => $row->isSummary() ? 'font-semibold' : '')
->afterRowCreated(fn ($row, $source) => /* … */)
->afterRowUpdated(fn ($row, $source) => /* … */)
->afterRowDeleted(fn ($row, $source) => /* … */)
->afterReordered(fn ($row, $source) => /* … */)
```

`disabled()` / `readonly()` inherited from `Field` are respected — the table hides
add/edit/delete/reorder and inline editing.

### Live save (array state)

In array-state mode every mutation (a row saved in the modal, reordered, deleted,
inline-edited, …) syncs into the host form's state. By default the host form is
then **persisted immediately** — saving a row in the modal commits it without a
separate save of the page. This calls the host Livewire component's `save` method
once the synced state has been applied.

```php
DataTable::make('sections')->arrayState()        // live save is on by default
DataTable::make('sections')->arrayState()->disableLiveSave()   // require a page save
DataTable::make('sections')->arrayState()->liveSave(method: 'create') // e.g. a create page

// Edit-only: the condition and method accept closures (Filament injects $operation)
DataTable::make('sections')->arrayState()
    ->liveSave(fn (string $operation): bool => $operation === 'edit')
```

Live save only applies to array-state tables — Eloquent-backed rows already persist
through their data source. Set the global default with the `live_save` config key.
On a **create** page there is no record yet and the host method is `create`, not
`save` — so gate live save to `edit` (as above) or `disableLiveSave()` there.

## Notes on closures

Custom action handlers, footer `where`/`custom` callbacks, column closures, and
lifecycle hooks are serialized (via `Laravel\SerializableClosure`) so the nested
Livewire component can run them. Keep them serializable — don't bind
non-serializable objects. If you'd rather keep logic on the host page, use
`->dispatch('event-name')` on actions and listen for it in your Livewire
page/resource.

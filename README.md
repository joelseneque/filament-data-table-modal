# Data Table with Modal

A Filament v5 **form field** that renders a condensed, interactive data table with
a slide-over/modal editor. Inline-edit cells, parent/child grouping, drag-and-drop
or button reordering, row + bulk actions, selection, and live footer aggregates.

Backed by **either an Eloquent model** (immediate persistence) **or array state**
(repeater-style, deferred until the form saves) — the same UI serves both.

```php
use Joelseneque\DataTableModal\DataTable;
use Joelseneque\DataTableModal\Columns\Column;
use Joelseneque\DataTableModal\Columns\Enums\{Alignment, ColumnFormat};
use Joelseneque\DataTableModal\Actions\RowAction;
use Filament\Forms\Components\{TextInput, Textarea, Toggle};

DataTable::make('line_items')
    ->records(LineItem::class)
    ->ownerKey('variation_id')
    ->orderColumn('order')
    ->parentChild()->parentColumn('parent_id')->numbering()
    ->reorderable()
    ->selectable()
    ->tableColumns([
        Column::make('item_name')->label('Item')->inlineEditable(),
        Column::make('description')->format(ColumnFormat::Html)->opensModal(),
        Column::make('price')->currency('$')->align(Alignment::Right)->width('w-24')->inlineEditable(),
        Column::make('gst')->label('GST')->inlineToggle()->align(Alignment::Center)->width('w-16'),
    ])
    ->rowActions([RowAction::edit(), RowAction::duplicate(), RowAction::delete()->requiresConfirmation()])
    ->modalSchema(fn () => [
        TextInput::make('item_name')->required(),
        Textarea::make('description')->columnSpanFull(),
        TextInput::make('price')->numeric()->prefix('$'),
        Toggle::make('gst'),
    ])
    ->slideOver()
```

> ⚠️ Two field methods are renamed from what you might expect, to avoid clashing
> with Filament's base `Field`: use **`->records()`** (not `->model()`) for the
> Eloquent class, and **`->tableColumns()`** (not `->columns()`, which sets the
> grid column count on every Filament component).

## Installation

```bash
composer require joelseneque/data-table-modal
```

The package auto-registers its service provider, views, translations, and a
Livewire component (`data-table-modal-manager`). Publish the config if you want to
change defaults:

```bash
php artisan vendor:publish --tag="data-table-modal-config"
```

### Assets

A pre-built JS bundle (table drag-and-drop via SortableJS) ships in
`resources/dist` and is registered with `FilamentAsset`, loaded on demand. If you
install from source and want to rebuild:

```bash
npm install && npm run build
```

### Tailwind

The field uses Tailwind utility classes and Filament's `primary`/`danger` CSS
variables, so it themes with your panel automatically. If you use a custom theme,
import the package's plugin stylesheet once in your theme's CSS (after the
Filament theme import):

```css
@import '../../../../vendor/joelseneque/data-table-modal/resources/css/plugin.css';
```

That single import is all you need — it scans the package's Blade views for
utility classes, force-generates the classes that are built at runtime (the
modal builds its width as `'max-w-' . $width`, which the Tailwind scanner can
never see as a literal), and pulls in the drag-and-drop styles. **Don't** add a
bare `@source '…/resources/views'` glob instead: it will miss the dynamic
`max-w-*` widths, so `->modalWidth()` silently has no effect. After changing your
theme CSS, rebuild it (e.g. `npm run build`).

## Data modes

| | **Model mode** (`->records()`) | **Array mode** (`->arrayState()`) |
|---|---|---|
| Persistence | Immediate (writes on every edit) | Deferred until the parent form saves |
| Row id | Database key | Synthetic UUID |
| Reorder | Writes the `order` column | Reindexes the array |
| Use when | The owner record already exists (edit pages) | You want repeater-style, transactional saving |

In model mode the field reads the owner id from the form's current record, so add
the field on an **edit** page (or guard it with `->visible(fn ($record) => $record)`).

## Configuration reference

See **[docs/usage.md](docs/usage.md)** for the full fluent API: hierarchy,
ordering, columns, row/bulk actions, selection, the modal, footer aggregates
(the totals bridge), validation, lifecycle hooks, and more.

## Testing

```bash
composer test
```

33 unit + feature tests cover the data sources, reorder/numbering logic, column
formatting, and the Livewire component in both modes.

## Credits

Generalized from a quote/job line-item builder. Built by Joel Seneque.

## License

MIT.

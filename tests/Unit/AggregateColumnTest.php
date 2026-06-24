<?php

declare(strict_types=1);

use Joelseneque\DataTableModal\Columns\Column;
use Joelseneque\DataTableModal\Columns\Enums\Alignment;
use Joelseneque\DataTableModal\Columns\Enums\ColumnFormat;
use Joelseneque\DataTableModal\DataSource\Row;
use Joelseneque\DataTableModal\Support\Aggregate;
use Joelseneque\DataTableModal\Support\Footer;

function priced(array $items): array
{
    return array_map(
        fn (array $i) => new Row(id: $i['name'], attributes: $i),
        $items,
    );
}

it('sums a column with a where filter', function () {
    $rows = priced([
        ['name' => 'a', 'price' => 100, 'inc' => false],
        ['name' => 'b', 'price' => 50, 'inc' => true],
    ]);

    $value = Aggregate::sum('price')->where(fn (Row $r) => ! $r->get('inc'))->compute($rows);

    expect($value)->toBe(100.0);
});

it('counts rows', function () {
    expect(Aggregate::count()->compute(priced([['name' => 'a'], ['name' => 'b']])))->toBe(2);
});

it('computes a footer keyed by dispatch path', function () {
    $rows = priced([['name' => 'a', 'price' => 100], ['name' => 'b', 'price' => 20]]);

    $values = Footer::make()
        ->aggregate(Aggregate::sum('price')->dispatchTo('subtotal'))
        ->aggregate(Aggregate::count()->dispatchTo('count'))
        ->compute($rows);

    expect($values)->toBe(['subtotal' => 120.0, 'count' => 2]);
});

it('formats currency and respects alignment', function () {
    $column = Column::make('price')->currency('$', 2)->align(Alignment::Right);
    $row = new Row(id: 1, attributes: ['price' => 1234.5]);

    expect($column->formatState($row))->toBe('$1,234.50')
        ->and($column->getAlignment())->toBe(Alignment::Right);
});

it('round-trips a column through its descriptor including closures', function () {
    $column = Column::make('name')
        ->inlineEditable()
        ->format(ColumnFormat::Text)
        ->formatStateUsing(fn ($state) => strtoupper((string) $state));

    $restored = Column::fromDescriptor($column->toDescriptor());
    $row = new Row(id: 1, attributes: ['name' => 'hi']);

    expect($restored->isInlineEditable())->toBeTrue()
        ->and($restored->formatState($row))->toBe('HI');
});

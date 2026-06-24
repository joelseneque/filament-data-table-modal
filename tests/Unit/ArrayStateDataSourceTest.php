<?php

declare(strict_types=1);

use Joelseneque\DataTableModal\DataSource\ArrayStateDataSource;

function source(): ArrayStateDataSource
{
    return new ArrayStateDataSource(
        items: [],
        parentColumn: 'parent_id',
        summaryColumn: 'is_summary',
        childNumberingColumn: 'num',
    );
}

function listNames(ArrayStateDataSource $s): string
{
    return implode(',', array_map(fn ($r) => $r->get('name'), $s->all()));
}

it('creates rows with synthetic ids and order', function () {
    $s = source();
    $a = $s->create(['name' => 'A']);

    expect($a->id)->not->toBeEmpty()
        ->and($s->all())->toHaveCount(1)
        ->and($s->find($a->id)?->get('name'))->toBe('A');
});

it('inserts a child immediately after its parent', function () {
    $s = source();
    $a = $s->create(['name' => 'A']);
    $s->create(['name' => 'B']);
    $s->create(['name' => 'A1', 'parent_id' => $a->id]);

    expect(listNames($s))->toBe('A,A1,B');
});

it('updates preserving the id', function () {
    $s = source();
    $a = $s->create(['name' => 'A']);
    $s->update($a->id, ['name' => 'Renamed']);

    expect($s->find($a->id)?->get('name'))->toBe('Renamed');
});

it('deletes a row', function () {
    $s = source();
    $a = $s->create(['name' => 'A']);
    $s->create(['name' => 'B']);
    $s->delete($a->id);

    expect(listNames($s))->toBe('B');
});

it('duplicates a row right after the original with a new id', function () {
    $s = source();
    $a = $s->create(['name' => 'A']);
    $dupe = $s->duplicate($a->id);

    expect(listNames($s))->toBe('A,A')
        ->and($dupe->id)->not->toBe($a->id);
});

it('reorders by id list', function () {
    $s = source();
    $a = $s->create(['name' => 'A']);
    $b = $s->create(['name' => 'B']);
    $c = $s->create(['name' => 'C']);

    $s->reorder([$c->id, $a->id, $b->id]);

    expect(listNames($s))->toBe('C,A,B');
});

it('reports deferred persistence', function () {
    expect(source()->supportsImmediatePersistence())->toBeFalse();
});

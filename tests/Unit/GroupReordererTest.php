<?php

declare(strict_types=1);

use Joelseneque\DataTableModal\DataSource\Row;
use Joelseneque\DataTableModal\Support\GroupReorderer;

/**
 * @param  array<int, array{0: string, 1?: string}>  $spec  [id, parentId?]
 * @return array<int, Row>
 */
function rows(array $spec): array
{
    $rows = [];
    foreach ($spec as $i => [$id, $parent]) {
        $rows[] = new Row(id: $id, attributes: ['name' => $id], parentId: $parent, order: $i);
    }

    return $rows;
}

function names(array $orderedIds): string
{
    return implode(',', $orderedIds);
}

it('moves a parent group up, carrying its children', function () {
    $r = rows([['A', null], ['A1', 'A'], ['B', null], ['C', null]]);

    expect(names(GroupReorderer::move($r, 'B', 'up')))->toBe('B,A,A1,C');
});

it('moves a parent group down, carrying its children', function () {
    $r = rows([['A', null], ['A1', 'A'], ['B', null]]);

    expect(names(GroupReorderer::move($r, 'A', 'down')))->toBe('B,A,A1');
});

it('reorders children only among siblings', function () {
    $r = rows([['A', null], ['A1', 'A'], ['A2', 'A'], ['B', null]]);

    expect(names(GroupReorderer::move($r, 'A2', 'up')))->toBe('A,A2,A1,B');
});

it('does not move the top group above itself', function () {
    $r = rows([['A', null], ['B', null]]);

    expect(names(GroupReorderer::move($r, 'A', 'up')))->toBe('A,B');
});

it('normalizes a flat drag order so children follow their parent', function () {
    $r = rows([['A', null], ['A1', 'A'], ['B', null]]);

    // Drag dropped A1 to the end; normalize pulls it back under A.
    expect(names(GroupReorderer::normalize($r, ['A', 'B', 'A1'])))->toBe('A,A1,B');
});

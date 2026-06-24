<?php

declare(strict_types=1);

use Joelseneque\DataTableModal\DataSource\Row;
use Joelseneque\DataTableModal\Support\HierarchyNumberer;

function row(string $id, ?string $parent = null, bool $summary = false, bool $num = false): Row
{
    return new Row(
        id: $id,
        attributes: ['name' => $id],
        parentId: $parent,
        meta: ['isSummary' => $summary, 'addNumbering' => $num],
    );
}

it('numbers parents 1,2,3 and lettered children a,b,c', function () {
    $rows = [
        row('P1'),
        row('c1', 'P1', num: true),
        row('c2', 'P1', num: true),
        row('P2'),
    ];

    $numbers = HierarchyNumberer::number($rows);

    expect($numbers)->toBe([
        'P1' => '1',
        'c1' => 'a',
        'c2' => 'b',
        'P2' => '2',
    ]);
});

it('omits child numbers when addNumbering is off', function () {
    $rows = [row('P1'), row('c1', 'P1', num: false)];

    expect(HierarchyNumberer::number($rows))->toBe(['P1' => '1', 'c1' => '']);
});

it('does not number summary rows and resets the sequence', function () {
    $rows = [row('P1'), row('S', summary: true), row('P2')];

    expect(HierarchyNumberer::number($rows))->toBe([
        'P1' => '1',
        'S' => '',
        'P2' => '1',
    ]);
});

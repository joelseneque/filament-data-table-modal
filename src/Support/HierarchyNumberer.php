<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Support;

use Joelseneque\DataTableModal\DataSource\Row;

/**
 * Pure numbering logic generalized from the squared line-item blade:
 *  - Parents are numbered 1, 2, 3…
 *  - Children are lettered a, b, c… but only when their row opts in (addNumbering meta)
 *  - Summary rows are not numbered and reset the sequence
 */
class HierarchyNumberer
{
    /**
     * @param  array<int, Row>  $rows  rows in display order
     * @return array<string, string> map of (string) row id => display number ('' when none)
     */
    public static function number(array $rows): array
    {
        $numbers = [];
        $parentCount = 0;
        $childCounts = [];

        foreach ($rows as $row) {
            $key = (string) $row->id;

            if ($row->isSummary()) {
                $numbers[$key] = '';
                $parentCount = 0;
                $childCounts = [];

                continue;
            }

            if (! $row->hasParent()) {
                $parentCount++;
                $childCounts[$key] = 0;
                $numbers[$key] = (string) $parentCount;

                continue;
            }

            if ($row->meta('addNumbering', false)) {
                $parentKey = (string) $row->parentId;
                $childCounts[$parentKey] = ($childCounts[$parentKey] ?? 0) + 1;
                $numbers[$key] = chr(96 + $childCounts[$parentKey]); // a, b, c…

                continue;
            }

            $numbers[$key] = '';
        }

        return $numbers;
    }
}

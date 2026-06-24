<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Support;

use Joelseneque\DataTableModal\DataSource\Row;

/**
 * Pure, framework-free reorder logic generalized from the squared
 * LineItemManager::moveUp()/moveDown() methods.
 *
 * Parents move together with all of their children as a single group; children
 * only reorder among siblings under the same parent. All methods return the new
 * flat top-to-bottom list of row ids, ready to hand to DataSource::reorder().
 */
class GroupReorderer
{
    /**
     * Move a row up or down, group-aware.
     *
     * @param  array<int, Row>  $rows  current rows in display order
     * @param  'up'|'down'  $direction
     * @return array<int, string|int> the new ordered id list
     */
    public static function move(array $rows, string|int $id, string $direction): array
    {
        $target = static::findRow($rows, $id);

        if ($target === null) {
            return static::ids($rows);
        }

        if ($target->hasParent()) {
            return static::moveChild($rows, $target, $direction);
        }

        return static::moveParentGroup($rows, $target, $direction);
    }

    /**
     * Re-thread a flat ordered id list (e.g. from drag-and-drop) so that every
     * child immediately follows its parent. A dragged parent keeps its children;
     * a child dropped outside its parent's run is pulled back beneath its parent.
     *
     * @param  array<int, Row>  $rows
     * @param  array<int, string|int>  $orderedIds
     * @return array<int, string|int>
     */
    public static function normalize(array $rows, array $orderedIds): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $byId[(string) $row->id] = $row;
        }

        $childrenByParent = [];
        foreach ($rows as $row) {
            if ($row->hasParent()) {
                $childrenByParent[(string) $row->parentId][] = $row;
            }
        }

        $result = [];
        $emitted = [];

        foreach ($orderedIds as $rid) {
            $key = (string) $rid;
            $row = $byId[$key] ?? null;

            if ($row === null || isset($emitted[$key])) {
                continue;
            }

            // Skip children here; they are emitted with their parent below.
            if ($row->hasParent() && isset($byId[(string) $row->parentId])) {
                continue;
            }

            $result[] = $row->id;
            $emitted[$key] = true;

            foreach ($childrenByParent[$key] ?? [] as $child) {
                if (! isset($emitted[(string) $child->id])) {
                    $result[] = $child->id;
                    $emitted[(string) $child->id] = true;
                }
            }
        }

        // Append any orphans/children whose parent was missing, preserving order.
        foreach ($rows as $row) {
            if (! isset($emitted[(string) $row->id])) {
                $result[] = $row->id;
                $emitted[(string) $row->id] = true;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, Row>  $rows
     * @return array<int, array<int, Row>> groups of [parent, ...children]
     */
    public static function groups(array $rows): array
    {
        $groups = [];
        $current = null;

        foreach ($rows as $row) {
            if (! $row->hasParent()) {
                if ($current !== null) {
                    $groups[] = $current;
                }
                $current = [$row];
            } else {
                if ($current === null) {
                    // Orphan child with no preceding parent — treat as its own group.
                    $current = [$row];

                    continue;
                }
                $current[] = $row;
            }
        }

        if ($current !== null) {
            $groups[] = $current;
        }

        return $groups;
    }

    /**
     * @param  array<int, Row>  $rows
     * @param  'up'|'down'  $direction
     * @return array<int, string|int>
     */
    protected static function moveParentGroup(array $rows, Row $target, string $direction): array
    {
        $groups = static::groups($rows);

        foreach ($groups as $index => $group) {
            if ($group[0]->id !== $target->id) {
                continue;
            }

            $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

            if ($swapWith < 0 || $swapWith >= count($groups)) {
                break;
            }

            [$groups[$index], $groups[$swapWith]] = [$groups[$swapWith], $groups[$index]];
            break;
        }

        $ids = [];
        foreach ($groups as $group) {
            foreach ($group as $row) {
                $ids[] = $row->id;
            }
        }

        return $ids;
    }

    /**
     * @param  array<int, Row>  $rows
     * @param  'up'|'down'  $direction
     * @return array<int, string|int>
     */
    protected static function moveChild(array $rows, Row $target, string $direction): array
    {
        $siblings = array_values(array_filter(
            $rows,
            fn (Row $row): bool => (string) $row->parentId === (string) $target->parentId,
        ));

        $position = null;
        foreach ($siblings as $index => $row) {
            if ($row->id === $target->id) {
                $position = $index;
                break;
            }
        }

        if ($position !== null) {
            $swapWith = $direction === 'up' ? $position - 1 : $position + 1;

            if ($swapWith >= 0 && $swapWith < count($siblings)) {
                [$siblings[$position], $siblings[$swapWith]] = [$siblings[$swapWith], $siblings[$position]];
            }
        }

        // Rebuild the full id list, substituting the re-ordered sibling run in place.
        $siblingIds = array_map(fn (Row $row) => $row->id, $siblings);
        $cursor = 0;
        $ids = [];

        foreach ($rows as $row) {
            if ((string) $row->parentId === (string) $target->parentId && $row->hasParent()) {
                $ids[] = $siblingIds[$cursor++];

                continue;
            }
            $ids[] = $row->id;
        }

        return $ids;
    }

    /**
     * @param  array<int, Row>  $rows
     * @return array<int, string|int>
     */
    protected static function ids(array $rows): array
    {
        return array_map(fn (Row $row) => $row->id, $rows);
    }

    /**
     * @param  array<int, Row>  $rows
     */
    protected static function findRow(array $rows, string|int $id): ?Row
    {
        foreach ($rows as $row) {
            if ((string) $row->id === (string) $id) {
                return $row;
            }
        }

        return null;
    }
}

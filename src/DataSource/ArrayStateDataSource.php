<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\DataSource;

use Illuminate\Support\Str;

/**
 * Backs the table with repeater-style array state. Mutations are in-memory only;
 * nothing persists until the parent Filament form is saved. The DataTableManager
 * builds one of these around its current working array, applies an operation,
 * reads the items back, and mirrors them into the parent form's state path.
 *
 * Each item is an associative array of attributes plus a synthetic id under the
 * `idKey` (default `__id`), since array rows have no database primary key.
 */
class ArrayStateDataSource implements DataSource
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        protected array $items = [],
        protected ?string $parentColumn = null,
        protected ?string $summaryColumn = null,
        protected ?string $childNumberingColumn = null,
        protected string $idKey = '__id',
    ) {
        $this->items = $this->normalizeIds(array_values($items));
    }

    public function all(): array
    {
        $rows = [];

        foreach ($this->items as $index => $item) {
            $rows[] = $this->toRow($item, $index);
        }

        return $rows;
    }

    public function find(string|int $id): ?Row
    {
        foreach ($this->items as $index => $item) {
            if ((string) $item[$this->idKey] === (string) $id) {
                return $this->toRow($item, $index);
            }
        }

        return null;
    }

    public function create(array $attributes): Row
    {
        $attributes[$this->idKey] = (string) Str::uuid();
        $parentId = $this->parentColumn ? ($attributes[$this->parentColumn] ?? null) : null;

        if ($parentId !== null) {
            $insertAt = $this->lastChildIndexOf($parentId) + 1;
            array_splice($this->items, $insertAt, 0, [$attributes]);
        } else {
            $this->items[] = $attributes;
        }

        $this->items = array_values($this->items);

        return $this->find($attributes[$this->idKey]);
    }

    public function update(string|int $id, array $attributes): Row
    {
        foreach ($this->items as $index => $item) {
            if ((string) $item[$this->idKey] === (string) $id) {
                $this->items[$index] = array_merge($item, $attributes, [$this->idKey => $item[$this->idKey]]);

                return $this->toRow($this->items[$index], $index);
            }
        }

        throw new \InvalidArgumentException("Row [{$id}] not found in array state.");
    }

    public function delete(string|int $id): void
    {
        $this->items = array_values(array_filter(
            $this->items,
            fn (array $item): bool => (string) $item[$this->idKey] !== (string) $id,
        ));
    }

    public function duplicate(string|int $id): Row
    {
        foreach ($this->items as $index => $item) {
            if ((string) $item[$this->idKey] === (string) $id) {
                $clone = $item;
                $clone[$this->idKey] = (string) Str::uuid();
                array_splice($this->items, $index + 1, 0, [$clone]);
                $this->items = array_values($this->items);

                return $this->toRow($clone, $index + 1);
            }
        }

        throw new \InvalidArgumentException("Row [{$id}] not found in array state.");
    }

    public function reorder(array $orderedIds): void
    {
        $byId = [];
        foreach ($this->items as $item) {
            $byId[(string) $item[$this->idKey]] = $item;
        }

        $reordered = [];
        foreach ($orderedIds as $id) {
            if (isset($byId[(string) $id])) {
                $reordered[] = $byId[(string) $id];
                unset($byId[(string) $id]);
            }
        }

        // Preserve any ids not present in the ordered list (defensive).
        foreach ($byId as $item) {
            $reordered[] = $item;
        }

        $this->items = array_values($reordered);
    }

    public function supportsImmediatePersistence(): bool
    {
        return false;
    }

    public function ownerKey(): ?string
    {
        return null;
    }

    public function parentColumn(): ?string
    {
        return $this->parentColumn;
    }

    /**
     * The working array, suitable for mirroring into the parent form state.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function toDescriptor(): array
    {
        return [
            'type' => 'array',
            'parent_column' => $this->parentColumn,
            'summary_column' => $this->summaryColumn,
            'child_numbering_column' => $this->childNumberingColumn,
            'id_key' => $this->idKey,
        ];
    }

    public static function fromDescriptor(array $descriptor): static
    {
        return new static(
            items: [],
            parentColumn: $descriptor['parent_column'] ?? null,
            summaryColumn: $descriptor['summary_column'] ?? null,
            childNumberingColumn: $descriptor['child_numbering_column'] ?? null,
            idKey: $descriptor['id_key'] ?? '__id',
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function withItems(array $items): static
    {
        $clone = clone $this;
        $clone->items = $clone->normalizeIds(array_values($items));

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function toRow(array $item, int $index): Row
    {
        return new Row(
            id: $item[$this->idKey],
            attributes: $item,
            parentId: $this->parentColumn ? ($item[$this->parentColumn] ?? null) : null,
            order: $index,
            meta: [
                'isSummary' => $this->summaryColumn ? (bool) ($item[$this->summaryColumn] ?? false) : false,
                'addNumbering' => $this->childNumberingColumn ? (bool) ($item[$this->childNumberingColumn] ?? false) : false,
            ],
        );
    }

    protected function lastChildIndexOf(string|int $parentId): int
    {
        $parentIndex = null;
        $lastChildIndex = null;

        foreach ($this->items as $index => $item) {
            if ((string) $item[$this->idKey] === (string) $parentId) {
                $parentIndex = $index;
            }

            if ($this->parentColumn && (string) ($item[$this->parentColumn] ?? '') === (string) $parentId) {
                $lastChildIndex = $index;
            }
        }

        return $lastChildIndex ?? $parentIndex ?? (count($this->items) - 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeIds(array $items): array
    {
        return array_map(function (array $item): array {
            if (empty($item[$this->idKey])) {
                $item[$this->idKey] = (string) Str::uuid();
            }

            return $item;
        }, $items);
    }
}

<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\DataSource;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A normalized representation of a single table row, produced by every
 * DataSource so the Livewire component and views never depend on whether the
 * data is backed by Eloquent or array state.
 *
 * @implements Arrayable<string, mixed>
 */
class Row implements Arrayable
{
    /**
     * @param  array<string, mixed>  $attributes  the row's column values
     * @param  array<string, mixed>  $meta  presentation flags (e.g. isSummary, addNumbering)
     */
    public function __construct(
        public string|int $id,
        public array $attributes = [],
        public string|int|null $parentId = null,
        public int $order = 0,
        public array $meta = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    public function isSummary(): bool
    {
        return (bool) $this->meta('isSummary', false);
    }

    public function hasParent(): bool
    {
        return $this->parentId !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'attributes' => $this->attributes,
            'parent_id' => $this->parentId,
            'order' => $this->order,
            'meta' => $this->meta,
        ];
    }
}

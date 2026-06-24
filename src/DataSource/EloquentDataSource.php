<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\DataSource;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Backs the table with an Eloquent model. Mutations persist immediately, so the
 * table is usable on an already-saved parent record (the squared behaviour).
 * Reordering rewrites the configured `order` column.
 */
class EloquentDataSource implements DataSource
{
    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(
        protected string $model,
        protected ?string $ownerKey = null,
        protected string|int|null $ownerId = null,
        protected string $orderColumn = 'order',
        protected ?string $parentColumn = null,
        protected ?string $summaryColumn = null,
        protected ?string $childNumberingColumn = null,
    ) {}

    public function all(): array
    {
        return $this->query()
            ->orderBy($this->orderColumn)
            ->get()
            ->map(fn (Model $model): Row => $this->toRow($model))
            ->all();
    }

    public function find(string|int $id): ?Row
    {
        $model = $this->query()->find($id);

        return $model ? $this->toRow($model) : null;
    }

    public function create(array $attributes): Row
    {
        $attributes = $this->applyOwner($attributes);
        $parentId = $this->parentColumn ? ($attributes[$this->parentColumn] ?? null) : null;

        $attributes[$this->orderColumn] = $parentId !== null
            ? $this->insertAfterParentOrder($parentId)
            : $this->nextOrder();

        /** @var Model $model */
        $model = $this->model::query()->create($attributes);

        return $this->toRow($model);
    }

    public function update(string|int $id, array $attributes): Row
    {
        /** @var Model $model */
        $model = $this->query()->findOrFail($id);
        unset($attributes[$this->orderColumn]); // order is managed via reorder()
        $model->update($attributes);

        return $this->toRow($model->refresh());
    }

    public function delete(string|int $id): void
    {
        $this->query()->whereKey($id)->delete();
    }

    public function duplicate(string|int $id): Row
    {
        /** @var Model $model */
        $model = $this->query()->findOrFail($id);

        $clone = $model->replicate();
        $clone->{$this->orderColumn} = $this->nextOrder();
        $clone->save();

        return $this->toRow($clone);
    }

    public function reorder(array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $index => $id) {
            $this->query()->whereKey($id)->update([$this->orderColumn => $index]);
        }
    }

    public function supportsImmediatePersistence(): bool
    {
        return true;
    }

    public function ownerKey(): ?string
    {
        return $this->ownerKey;
    }

    public function parentColumn(): ?string
    {
        return $this->parentColumn;
    }

    public function toDescriptor(): array
    {
        return [
            'type' => 'eloquent',
            'model' => $this->model,
            'owner_key' => $this->ownerKey,
            'owner_id' => $this->ownerId,
            'order_column' => $this->orderColumn,
            'parent_column' => $this->parentColumn,
            'summary_column' => $this->summaryColumn,
            'child_numbering_column' => $this->childNumberingColumn,
        ];
    }

    public static function fromDescriptor(array $descriptor): static
    {
        return new static(
            model: $descriptor['model'],
            ownerKey: $descriptor['owner_key'] ?? null,
            ownerId: $descriptor['owner_id'] ?? null,
            orderColumn: $descriptor['order_column'] ?? 'order',
            parentColumn: $descriptor['parent_column'] ?? null,
            summaryColumn: $descriptor['summary_column'] ?? null,
            childNumberingColumn: $descriptor['child_numbering_column'] ?? null,
        );
    }

    /**
     * @return Builder<Model>
     */
    protected function query()
    {
        $query = $this->model::query();

        if ($this->ownerKey !== null && $this->ownerId !== null) {
            $query->where($this->ownerKey, $this->ownerId);
        }

        return $query;
    }

    protected function toRow(Model $model): Row
    {
        $attributes = $model->attributesToArray();

        return new Row(
            id: $model->getKey(),
            attributes: $attributes,
            parentId: $this->parentColumn ? ($attributes[$this->parentColumn] ?? null) : null,
            order: (int) ($attributes[$this->orderColumn] ?? 0),
            meta: [
                'isSummary' => $this->summaryColumn ? (bool) ($attributes[$this->summaryColumn] ?? false) : false,
                'addNumbering' => $this->childNumberingColumn ? (bool) ($attributes[$this->childNumberingColumn] ?? false) : false,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function applyOwner(array $attributes): array
    {
        if ($this->ownerKey !== null && $this->ownerId !== null) {
            $attributes[$this->ownerKey] = $this->ownerId;
        }

        return $attributes;
    }

    protected function nextOrder(): int
    {
        return (int) ($this->query()->max($this->orderColumn) ?? -1) + 1;
    }

    protected function insertAfterParentOrder(string|int $parentId): int
    {
        $parentOrder = (int) ($this->query()->whereKey($parentId)->value($this->orderColumn) ?? 0);

        $lastChildOrder = $this->parentColumn
            ? $this->query()->where($this->parentColumn, $parentId)->max($this->orderColumn)
            : null;

        $insertAfter = $lastChildOrder !== null ? (int) $lastChildOrder : $parentOrder;

        // Shift everything after the insertion point down to make room.
        $this->query()->where($this->orderColumn, '>', $insertAfter)->increment($this->orderColumn);

        return $insertAfter + 1;
    }
}

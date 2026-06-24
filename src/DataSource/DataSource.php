<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\DataSource;

/**
 * The contract every backend implements. The DataTableManager Livewire
 * component depends ONLY on this interface — never on a concrete model or on
 * array state directly — so the same table/modal/reorder UI serves both an
 * Eloquent model and repeater-style array state.
 */
interface DataSource
{
    /**
     * All rows, ordered.
     *
     * @return array<int, Row>
     */
    public function all(): array;

    public function find(string|int $id): ?Row;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Row;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string|int $id, array $attributes): Row;

    public function delete(string|int $id): void;

    public function duplicate(string|int $id): Row;

    /**
     * Persist a new ordering. Receives the full list of row ids in their new
     * top-to-bottom order (already group-aware where hierarchy is enabled).
     *
     * @param  array<int, string|int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;

    /**
     * Whether mutations hit the backing store immediately (Eloquent) or are
     * deferred until the parent form is saved (array state).
     */
    public function supportsImmediatePersistence(): bool;

    public function ownerKey(): ?string;

    public function parentColumn(): ?string;

    /**
     * A serializable description used to rebuild this source inside the
     * Livewire component's mount() (Livewire cannot serialize closures/models).
     *
     * @return array<string, mixed>
     */
    public function toDescriptor(): array;

    /**
     * @param  array<string, mixed>  $descriptor
     */
    public static function fromDescriptor(array $descriptor): static;
}

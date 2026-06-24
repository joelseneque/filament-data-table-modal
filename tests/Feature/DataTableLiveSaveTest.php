<?php

declare(strict_types=1);

use Joelseneque\DataTableModal\DataTable;

it('live saves array-state tables by default', function () {
    $field = DataTable::make('items')->arrayState();

    expect($field->shouldLiveSave())->toBeTrue();
    expect($field->getLiveSaveMethod())->toBe('save');
});

it('disables live save via disableLiveSave()', function () {
    $field = DataTable::make('items')->arrayState()->disableLiveSave();

    expect($field->shouldLiveSave())->toBeFalse();
});

it('accepts a custom host method for live save', function () {
    $field = DataTable::make('items')->arrayState()->liveSave(method: 'create');

    expect($field->shouldLiveSave())->toBeTrue();
    expect($field->getLiveSaveMethod())->toBe('create');
});

it('accepts a closure condition for live save', function () {
    $on = DataTable::make('items')->arrayState()->liveSave(fn (): bool => true);
    $off = DataTable::make('items')->arrayState()->liveSave(fn (): bool => false);

    expect($on->shouldLiveSave())->toBeTrue();
    expect($off->shouldLiveSave())->toBeFalse();
});

it('never live saves eloquent-backed tables', function () {
    $field = DataTable::make('items')->records(stdClass::class);

    expect($field->shouldLiveSave())->toBeFalse();
});

it('honours the live_save config default for array-state tables', function () {
    config()->set('data-table-modal.live_save', false);

    $field = DataTable::make('items')->arrayState();

    expect($field->shouldLiveSave())->toBeFalse();
});

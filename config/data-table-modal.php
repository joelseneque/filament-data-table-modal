<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Modal defaults
    |--------------------------------------------------------------------------
    | Default presentation for the add/edit modal. These can be overridden
    | per-field via ->slideOver(), ->modalWidth() etc.
    */
    'modal' => [
        'slide_over' => true,
        'width' => '2xl',
    ],

    /*
    |--------------------------------------------------------------------------
    | Behaviour defaults
    |--------------------------------------------------------------------------
    */
    'confirm_delete' => true,

    /*
    | When an array-state table mutates (a row saved in the modal, reordered,
    | deleted, …) immediately persist the host form so the change is committed
    | without a separate save of the page. Override per-field with
    | ->disableLiveSave(). Eloquent-backed tables ignore this (rows persist via
    | their data source).
    */
    'live_save' => true,

    /*
    |--------------------------------------------------------------------------
    | Column defaults
    |--------------------------------------------------------------------------
    */
    'order_column' => 'order',
    'parent_column' => 'parent_id',
];

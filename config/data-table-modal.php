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
    |--------------------------------------------------------------------------
    | Column defaults
    |--------------------------------------------------------------------------
    */
    'order_column' => 'order',
    'parent_column' => 'parent_id',
];

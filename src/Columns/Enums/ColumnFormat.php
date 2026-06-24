<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Columns\Enums;

enum ColumnFormat: string
{
    case Text = 'text';
    case Number = 'number';
    case Currency = 'currency';
    case Date = 'date';
    case Boolean = 'boolean';
    case Badge = 'badge';
    case Html = 'html';
}

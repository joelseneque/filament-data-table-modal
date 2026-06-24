<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Columns\Enums;

enum Alignment: string
{
    case Left = 'left';
    case Center = 'center';
    case Right = 'right';

    public function textClass(): string
    {
        return match ($this) {
            self::Left => 'text-left',
            self::Center => 'text-center',
            self::Right => 'text-right',
        };
    }
}

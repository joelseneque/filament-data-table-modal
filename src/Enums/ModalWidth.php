<?php

declare(strict_types=1);

namespace Joelseneque\DataTableModal\Enums;

enum ModalWidth: string
{
    case Small = 'sm';
    case Medium = 'md';
    case Large = 'lg';
    case ExtraLarge = 'xl';
    case TwoExtraLarge = '2xl';
    case ThreeExtraLarge = '3xl';
    case FourExtraLarge = '4xl';
    case FiveExtraLarge = '5xl';
    case SixExtraLarge = '6xl';
    case SevenExtraLarge = '7xl';
    case Screen = 'screen';

    public function maxWidthClass(): string
    {
        return match ($this) {
            self::Screen => 'max-w-full',
            default => 'max-w-'.$this->value,
        };
    }
}

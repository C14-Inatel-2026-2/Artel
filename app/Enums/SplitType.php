<?php

namespace App\Enums;

enum SplitType: string
{
    case Equal = 'equal';
    case Exact = 'exact';
    case Percentage = 'percentage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromInput(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Equal;
    }
}

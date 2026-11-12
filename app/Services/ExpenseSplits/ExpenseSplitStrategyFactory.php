<?php

namespace App\Services\ExpenseSplits;

class ExpenseSplitStrategyFactory
{
    public function make(string $splitType): ExpenseSplitStrategy
    {
        return match ($splitType) {
            'exact' => new ExactSplitStrategy,
            'percentage' => new PercentageSplitStrategy,
            default => new EqualSplitStrategy,
        };
    }
}

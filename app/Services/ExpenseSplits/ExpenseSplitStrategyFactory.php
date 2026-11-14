<?php

namespace App\Services\ExpenseSplits;

use App\Enums\SplitType;

class ExpenseSplitStrategyFactory
{
    public function make(string $splitType): ExpenseSplitStrategy
    {
        return match (SplitType::fromInput($splitType)) {
            SplitType::Exact => new ExactSplitStrategy,
            SplitType::Percentage => new PercentageSplitStrategy,
            SplitType::Equal => new EqualSplitStrategy,
        };
    }
}

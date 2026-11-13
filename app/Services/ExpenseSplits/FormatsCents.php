<?php

namespace App\Services\ExpenseSplits;

trait FormatsCents
{
    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }
}

<?php

namespace App\Services\ExpenseSplits;

use InvalidArgumentException;

class PercentageSplitStrategy implements ExpenseSplitStrategy
{
    public function calculateShares(array $memberIds, int $totalCents, array $data): array
    {
        $hundredths = [];

        foreach ($data['splits'] as $split) {
            $hundredths[$split['user_id']] = (int) round($split['percentage'] * 100);
        }

        $sumHundredths = array_sum($hundredths);

        if ($sumHundredths !== 10000) {
            throw new InvalidArgumentException(sprintf(
                'A soma dos percentuais (%s%%) deve ser exatamente 100%%.',
                number_format($sumHundredths / 100, 2, ',', '.'),
            ));
        }

        $shares = [];
        $remainders = [];

        foreach ($hundredths as $userId => $value) {
            $product = $totalCents * $value;
            $shares[$userId] = intdiv($product, 10000);
            $remainders[$userId] = $product % 10000;
        }

        $leftoverCents = $totalCents - array_sum($shares);
        arsort($remainders);

        foreach (array_keys($remainders) as $userId) {
            if ($leftoverCents <= 0) {
                break;
            }

            $shares[$userId]++;
            $leftoverCents--;
        }

        return $shares;
    }
}

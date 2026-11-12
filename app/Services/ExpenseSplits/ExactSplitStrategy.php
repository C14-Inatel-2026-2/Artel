<?php

namespace App\Services\ExpenseSplits;

use InvalidArgumentException;

class ExactSplitStrategy implements ExpenseSplitStrategy
{
    use FormatsCents;

    public function calculateShares(array $memberIds, int $totalCents, array $data): array
    {
        $shares = [];

        foreach ($data['splits'] as $split) {
            $shares[$split['user_id']] = (int) round($split['amount'] * 100);
        }

        $sumCents = array_sum($shares);

        if ($sumCents !== $totalCents) {
            throw new InvalidArgumentException(sprintf(
                'A soma das parcelas (R$ %s) é diferente do valor da despesa (R$ %s).',
                $this->formatCents($sumCents),
                $this->formatCents($totalCents),
            ));
        }

        return $shares;
    }
}

<?php

namespace App\Services\ExpenseSplits;

class EqualSplitStrategy implements ExpenseSplitStrategy
{
    public function calculateShares(array $memberIds, int $totalCents, array $data): array
    {
        $userIds = array_values($data['participants'] ?? $memberIds);
        $baseCents = intdiv($totalCents, count($userIds));
        $remainder = $totalCents % count($userIds);
        $shares = [];

        foreach ($userIds as $index => $userId) {
            $shares[$userId] = $baseCents + ($index < $remainder ? 1 : 0);
        }

        return $shares;
    }
}

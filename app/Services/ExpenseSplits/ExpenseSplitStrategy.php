<?php

namespace App\Services\ExpenseSplits;

interface ExpenseSplitStrategy
{
    /**
     * Calcula a parte de cada participante em centavos.
     *
     * @param  list<int>  $memberIds
     * @param  array<string, mixed>  $data
     * @return array<int, int> user_id => cents
     */
    public function calculateShares(array $memberIds, int $totalCents, array $data): array;
}

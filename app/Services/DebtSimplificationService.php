<?php

namespace App\Services;

class DebtSimplificationService
{
    /**
     * Gera a lista mínima de transferências a partir dos saldos líquidos.
     *
     * Opera em centavos inteiros e emparelha iterativamente o maior devedor
     * com o maior credor.
     *
     * @param  array<int, array{user: array{id: int, name: string, email: string}, net_balance: float}>  $balances
     * @return array<int, array{from: array{id: int, name: string, email: string}, to: array{id: int, name: string, email: string}, amount: float}>
     */
    public function suggestTransfers(array $balances): array
    {
        $debtors = [];
        $creditors = [];

        foreach ($balances as $balance) {
            $cents = (int) round(((float) $balance['net_balance']) * 100);

            if ($cents < 0) {
                $debtors[] = [
                    'user' => $balance['user'],
                    'amount_cents' => abs($cents),
                ];
            } elseif ($cents > 0) {
                $creditors[] = [
                    'user' => $balance['user'],
                    'amount_cents' => $cents,
                ];
            }
        }

        $transfers = [];

        while ($debtors !== [] && $creditors !== []) {
            usort($debtors, fn (array $a, array $b): int => $b['amount_cents'] <=> $a['amount_cents']);
            usort($creditors, fn (array $a, array $b): int => $b['amount_cents'] <=> $a['amount_cents']);

            $debtorIndex = array_key_first($debtors);
            $creditorIndex = array_key_first($creditors);

            $payCents = min($debtors[$debtorIndex]['amount_cents'], $creditors[$creditorIndex]['amount_cents']);

            if ($payCents > 0) {
                $transfers[] = [
                    'from' => $debtors[$debtorIndex]['user'],
                    'to' => $creditors[$creditorIndex]['user'],
                    'amount' => round($payCents / 100, 2),
                ];
            }

            $debtors[$debtorIndex]['amount_cents'] -= $payCents;
            $creditors[$creditorIndex]['amount_cents'] -= $payCents;

            if ($debtors[$debtorIndex]['amount_cents'] === 0) {
                unset($debtors[$debtorIndex]);
            }

            if ($creditors[$creditorIndex]['amount_cents'] === 0) {
                unset($creditors[$creditorIndex]);
            }
        }

        return $transfers;
    }
}

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
        [$debtors, $creditors] = $this->partitionBalances($balances);

        return $this->matchTransfers($debtors, $creditors);
    }

    /**
     * Separa saldos líquidos em filas de devedores e credores (em centavos).
     *
     * @param  array<int, array{user: array{id: int, name: string, email: string}, net_balance: float}>  $balances
     * @return array{0: list<array{user: array{id: int, name: string, email: string}, amount_cents: int}>, 1: list<array{user: array{id: int, name: string, email: string}, amount_cents: int}>}
     */
    private function partitionBalances(array $balances): array
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

        return [$debtors, $creditors];
    }

    /**
     * Emparelha maior devedor com maior credor até zerar as filas.
     *
     * @param  list<array{user: array{id: int, name: string, email: string}, amount_cents: int}>  $debtors
     * @param  list<array{user: array{id: int, name: string, email: string}, amount_cents: int}>  $creditors
     * @return array<int, array{from: array{id: int, name: string, email: string}, to: array{id: int, name: string, email: string}, amount: float}>
     */
    private function matchTransfers(array $debtors, array $creditors): array
    {
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

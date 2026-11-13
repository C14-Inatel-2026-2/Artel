<?php

namespace App\Services;

use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Models\User;

class GroupBalanceService
{
    public function __construct(
        protected DebtSimplificationService $debtSimplification = new DebtSimplificationService,
    ) {}

    public function calculateBalance(Group $group): array
    {
        $balances = $group->members()
            ->select('users.id', 'users.name', 'users.email')
            ->get()
            ->map(fn (User $member) => $this->memberBalance($group, $member))
            ->all();

        return [
            'group_id' => $group->id,
            'group_name' => $group->name,
            'total_expenses' => round((float) $group->expenses()->sum('amount'), 2),
            'expenses_count' => $group->expenses()->count(),
            'balances' => $balances,
            'suggested_transfers' => $this->debtSimplification->suggestTransfers($balances),
        ];
    }

    private function memberBalance(Group $group, User $member): array
    {
        $totalPaid = (float) $group->expenses()->where('paid_by', $member->id)->sum('amount');

        $totalOwed = (float) ExpenseSplit::where('user_id', $member->id)
            ->whereHas('expense', fn ($q) => $q->where('group_id', $group->id))
            ->sum('amount');

        // Acertos: quem pagou reduz a dívida (sobe o saldo); quem recebeu reduz o crédito (desce o saldo).
        $totalSettledPaid = (float) $group->settlements()->where('paid_by', $member->id)->sum('amount');
        $totalSettledReceived = (float) $group->settlements()->where('paid_to', $member->id)->sum('amount');

        $netBalance = round($totalPaid - $totalOwed + $totalSettledPaid - $totalSettledReceived, 2);

        return [
            'user' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
            ],
            'total_paid' => round($totalPaid, 2),
            'total_owed' => round($totalOwed, 2),
            'net_balance' => $netBalance,
            'status' => $this->balanceStatus($netBalance),
        ];
    }

    private function balanceStatus(float $netBalance): string
    {
        return match (true) {
            $netBalance > 0 => 'a_receber',
            $netBalance < 0 => 'a_pagar',
            default => 'quitado',
        };
    }
}

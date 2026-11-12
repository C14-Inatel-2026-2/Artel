<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Models\User;
use App\Services\ExpenseSplits\ExpenseSplitStrategyFactory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseService
{
    public function __construct(
        protected DebtSimplificationService $debtSimplification = new DebtSimplificationService,
        protected ExpenseSplitStrategyFactory $splitStrategyFactory = new ExpenseSplitStrategyFactory,
    ) {}

    public function createExpense(Group $group, array $data): Expense
    {
        [$splitType, $shares] = $this->buildSplit($group, $data);

        return DB::transaction(function () use ($group, $data, $splitType, $shares) {
            $expense = Expense::create([
                'description' => $data['description'],
                'amount' => $data['amount'],
                'paid_by' => $data['paid_by'],
                'split_type' => $splitType,
                'group_id' => $group->id,
            ]);

            $this->createSplits($expense, $shares);

            return $expense->load(['payer:id,name,email', 'splits.user:id,name,email']);
        });
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        [$splitType, $shares] = $this->buildSplit($expense->group, $data);

        return DB::transaction(function () use ($expense, $data, $splitType, $shares) {
            $expense->update([
                'description' => $data['description'],
                'amount' => $data['amount'],
                'paid_by' => $data['paid_by'],
                'split_type' => $splitType,
            ]);

            $expense->splits()->delete();
            $this->createSplits($expense, $shares);

            return $expense->load(['payer:id,name,email', 'splits.user:id,name,email']);
        });
    }

    public function deleteExpense(Expense $expense): bool
    {
        return DB::transaction(function () use ($expense) {
            $expense->splits()->delete();

            return (bool) $expense->delete();
        });
    }

    public function calculateBalance(Group $group): array
    {
        $members = $group->members()->select('users.id', 'users.name', 'users.email')->get();
        $balances = [];

        foreach ($members as $member) {
            $totals = $this->balanceTotalsForMember($group, $member->id);
            $netBalance = $totals['net_balance'];

            $status = match (true) {
                $netBalance > 0 => 'a_receber',
                $netBalance < 0 => 'a_pagar',
                default => 'quitado',
            };

            $balances[] = [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ],
                'total_paid' => $totals['total_paid'],
                'total_owed' => $totals['total_owed'],
                'net_balance' => $netBalance,
                'status' => $status,
            ];
        }

        return [
            'group_id' => $group->id,
            'group_name' => $group->name,
            'total_expenses' => round((float) $group->expenses()->sum('amount'), 2),
            'expenses_count' => $group->expenses()->count(),
            'balances' => $balances,
            'suggested_transfers' => $this->debtSimplification->suggestTransfers($balances),
        ];
    }

    /**
     * Saldo líquido de um participante no grupo (mesma fórmula de calculateBalance).
     */
    public function netBalanceForMember(Group $group, User $user): float
    {
        return $this->balanceTotalsForMember($group, $user->id)['net_balance'];
    }

    /**
     * @return array{total_paid: float, total_owed: float, net_balance: float}
     */
    private function balanceTotalsForMember(Group $group, int $userId): array
    {
        $totalPaid = (float) $group->expenses()->where('paid_by', $userId)->sum('amount');

        $totalOwed = (float) ExpenseSplit::where('user_id', $userId)
            ->whereHas('expense', fn ($q) => $q->where('group_id', $group->id))
            ->sum('amount');

        // Acertos: quem pagou reduz a dívida (sobe o saldo); quem recebeu reduz o crédito (desce o saldo).
        $totalSettledPaid = (float) $group->settlements()->where('paid_by', $userId)->sum('amount');
        $totalSettledReceived = (float) $group->settlements()->where('paid_to', $userId)->sum('amount');

        return [
            'total_paid' => round($totalPaid, 2),
            'total_owed' => round($totalOwed, 2),
            'net_balance' => round($totalPaid - $totalOwed + $totalSettledPaid - $totalSettledReceived, 2),
        ];
    }

    private function buildSplit(Group $group, array $data): array
    {
        $members = $group->members;

        if ($members->isEmpty()) {
            throw new InvalidArgumentException('O grupo não possui participantes para divisão da despesa.');
        }

        $splitType = $data['split_type'] ?? 'equal';
        $totalCents = (int) round($data['amount'] * 100);
        $strategy = $this->splitStrategyFactory->make($splitType);

        return [
            $splitType,
            $strategy->calculateShares($members->pluck('id')->all(), $totalCents, $data),
        ];
    }

    private function createSplits(Expense $expense, array $shares): void
    {
        foreach ($shares as $userId => $cents) {
            ExpenseSplit::create([
                'expense_id' => $expense->id,
                'user_id' => $userId,
                'amount' => $cents / 100,
            ]);
        }
    }
}

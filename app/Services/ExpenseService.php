<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseService
{
    public function __construct(
        protected DebtSimplificationService $debtSimplification = new DebtSimplificationService,
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
            $totalPaid = (float) $group->expenses()->where('paid_by', $member->id)->sum('amount');

            $totalOwed = (float) ExpenseSplit::where('user_id', $member->id)
                ->whereHas('expense', fn ($q) => $q->where('group_id', $group->id))
                ->sum('amount');

            // Acertos: quem pagou reduz a dívida (sobe o saldo); quem recebeu reduz o crédito (desce o saldo).
            $totalSettledPaid = (float) $group->settlements()->where('paid_by', $member->id)->sum('amount');
            $totalSettledReceived = (float) $group->settlements()->where('paid_to', $member->id)->sum('amount');

            $netBalance = round($totalPaid - $totalOwed + $totalSettledPaid - $totalSettledReceived, 2);

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
                'total_paid' => round($totalPaid, 2),
                'total_owed' => round($totalOwed, 2),
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

    private function buildSplit(Group $group, array $data): array
    {
        $members = $group->members;

        if ($members->isEmpty()) {
            throw new InvalidArgumentException('O grupo não possui participantes para divisão da despesa.');
        }

        $splitType = $data['split_type'] ?? 'equal';
        $totalCents = (int) round($data['amount'] * 100);

        return [
            $splitType,
            $this->calculateShares($members->pluck('id')->all(), $totalCents, $splitType, $data),
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

    private function calculateShares(array $memberIds, int $totalCents, string $splitType, array $data): array
    {
        return match ($splitType) {
            'exact' => $this->exactShares($data['splits'], $totalCents),
            'percentage' => $this->percentageShares($data['splits'], $totalCents),
            default => $this->equalShares($data['participants'] ?? $memberIds, $totalCents),
        };
    }

    private function equalShares(array $userIds, int $totalCents): array
    {
        $userIds = array_values($userIds);
        $baseCents = intdiv($totalCents, count($userIds));
        $remainder = $totalCents % count($userIds);
        $shares = [];

        foreach ($userIds as $index => $userId) {
            $shares[$userId] = $baseCents + ($index < $remainder ? 1 : 0);
        }

        return $shares;
    }

    private function exactShares(array $splits, int $totalCents): array
    {
        $shares = [];

        foreach ($splits as $split) {
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

    private function percentageShares(array $splits, int $totalCents): array
    {
        $hundredths = [];

        foreach ($splits as $split) {
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

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }
}

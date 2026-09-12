<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseService
{
    public function createExpense(Group $group, array $data): Expense
    {
        $members = $group->members;

        if ($members->isEmpty()) {
            throw new InvalidArgumentException('O grupo não possui participantes para divisão da despesa.');
        }

        return DB::transaction(function () use ($group, $data, $members) {
            $expense = Expense::create([
                'description' => $data['description'],
                'amount' => $data['amount'],
                'paid_by' => $data['paid_by'],
                'group_id' => $group->id,
            ]);

            $totalCents = (int) round($data['amount'] * 100);
            $count = $members->count();
            $baseCents = intdiv($totalCents, $count);
            $remainder = $totalCents % $count;

            foreach ($members->values() as $index => $member) {
                $cents = $baseCents + ($index < $remainder ? 1 : 0);

                ExpenseSplit::create([
                    'expense_id' => $expense->id,
                    'user_id' => $member->id,
                    'amount' => $cents / 100,
                ]);
            }

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

            $netBalance = round($totalPaid - $totalOwed, 2);

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
        ];
    }
}

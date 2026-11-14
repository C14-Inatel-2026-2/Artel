<?php

namespace App\Services;

use App\Enums\SplitType;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Services\ExpenseSplits\ExpenseSplitStrategyFactory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpenseService
{
    public function __construct(
        protected ExpenseSplitStrategyFactory $splitStrategyFactory = new ExpenseSplitStrategyFactory,
    ) {}

    public function createExpense(Group $group, array $data): Expense
    {
        [$splitType, $shares] = $this->buildSplit($group, $data);

        return DB::transaction(function () use ($group, $data, $splitType, $shares) {
            $expense = Expense::create($this->expenseAttributes($data, $splitType) + ['group_id' => $group->id]);

            $this->createSplits($expense, $shares);

            return $this->withRelations($expense);
        });
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        [$splitType, $shares] = $this->buildSplit($expense->group, $data);

        return DB::transaction(function () use ($expense, $data, $splitType, $shares) {
            $expense->update($this->expenseAttributes($data, $splitType));

            $expense->splits()->delete();
            $this->createSplits($expense, $shares);

            return $this->withRelations($expense);
        });
    }

    public function deleteExpense(Expense $expense): bool
    {
        return DB::transaction(function () use ($expense) {
            $expense->splits()->delete();

            return (bool) $expense->delete();
        });
    }

    private function expenseAttributes(array $data, string $splitType): array
    {
        return [
            'description' => $data['description'],
            'amount' => $data['amount'],
            'paid_by' => $data['paid_by'],
            'split_type' => $splitType,
        ];
    }

    private function withRelations(Expense $expense): Expense
    {
        return $expense->load(['payer:id,name,email', 'splits.user:id,name,email']);
    }

    private function buildSplit(Group $group, array $data): array
    {
        $members = $group->members;

        if ($members->isEmpty()) {
            throw new InvalidArgumentException('O grupo não possui participantes para divisão da despesa.');
        }

        $splitType = $data['split_type'] ?? SplitType::equal->value;
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    public function index(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $expenses = $group->expenses()
            ->with(['payer:id,name,email', 'splits.user:id,name,email'])
            ->latest()
            ->get();

        return response()->json($expenses, 200);
    }

    public function store(StoreExpenseRequest $request, Group $group): JsonResponse
    {
        try {
            $expense = $this->expenseService->createExpense($group, $request->validated());

            return response()->json($expense, 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense->group);

        $expense->load([
            'group:id,name',
            'payer:id,name,email',
            'splits.user:id,name,email',
        ]);

        return response()->json($expense, 200);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense->group);

        $this->expenseService->deleteExpense($expense);

        return response()->json([
            'message' => 'Despesa excluída com sucesso.',
        ], 200);
    }
}

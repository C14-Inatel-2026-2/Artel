<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ExpensePageController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
    ) {}

    public function store(StoreExpenseRequest $request, Group $group): RedirectResponse
    {
        try {
            $this->expenseService->createExpense($group, $request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['splits' => $e->getMessage()]);
        }

        return redirect()
            ->route('web.groups.show', $group)
            ->with('status', 'Despesa registrada.');
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        try {
            $this->expenseService->updateExpense($expense, $request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['splits' => $e->getMessage()]);
        }

        return redirect()
            ->route('web.groups.show', $expense->group_id)
            ->with('status', 'Despesa atualizada.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('view', $expense->group);

        $this->expenseService->deleteExpense($expense);

        return redirect()
            ->route('web.groups.show', $expense->group_id)
            ->with('status', 'Despesa excluída.');
    }
}

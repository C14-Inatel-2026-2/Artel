<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Services\ExpenseService;
use App\Services\SettlementService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GroupPageController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
        protected SettlementService $settlementService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $groups = Group::query()
            ->where('user_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->with(['owner:id,name', 'members:id,name'])
            ->withCount(['members', 'expenses'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return view('groups.index', ['groups' => $groups]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            $group = Group::create([
                'name' => $request->validated('name'),
                'user_id' => $user->id,
            ]);

            $group->members()->attach($user->id);
        });

        return redirect()
            ->route('web.groups.index')
            ->with('status', 'Grupo criado com sucesso.');
    }

    public function show(Request $request, Group $group): View
    {
        Gate::authorize('view', $group);

        $group->load(['owner:id,name,email', 'members:id,name,email']);

        $expenses = $group->expenses()
            ->with(['payer:id,name', 'splits:id,expense_id,user_id,amount'])
            ->latest()
            ->latest('id')
            ->get();

        $balance = $this->expenseService->calculateBalance($group);

        $myBalance = collect($balance['balances'])
            ->firstWhere('user.id', $request->user()->id)['net_balance'] ?? 0.0;

        return view('groups.show', [
            'group' => $group,
            'expenses' => $expenses,
            'balance' => $balance,
            'myBalance' => $myBalance,
            'settlements' => $this->settlementService->listForGroup($group),
            'expensesData' => $this->expensesFormData($expenses),
        ]);
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return array<int, array<string, mixed>>
     */
    private function expensesFormData(Collection $expenses): array
    {
        return $expenses->mapWithKeys(function (Expense $expense) {
            $total = (float) $expense->amount;

            $splits = $expense->splits->map(fn ($split) => [
                'user_id' => $split->user_id,
                'amount' => (float) $split->amount,
                'percentage' => $total > 0 ? round((float) $split->amount / $total * 100, 2) : 0.0,
            ])->values()->all();

            if ($splits !== []) {
                $splits[0]['percentage'] = round($splits[0]['percentage'] + 100 - array_sum(array_column($splits, 'percentage')), 2);
            }

            return [$expense->id => [
                'id' => $expense->id,
                'description' => $expense->description,
                'amount' => number_format($total, 2, '.', ''),
                'paid_by' => $expense->paid_by,
                'split_type' => $expense->split_type ?? 'equal',
                'splits' => $splits,
                'update_url' => route('web.expenses.update', $expense),
                'delete_url' => route('web.expenses.destroy', $expense),
            ]];
        })->all();
    }
}

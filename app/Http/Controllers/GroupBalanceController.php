<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;

class GroupBalanceController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    public function index(Group $group): JsonResponse
    {
        $balances = $this->expenseService->calculateBalance($group);

        return response()->json($balances, 200);
    }
}

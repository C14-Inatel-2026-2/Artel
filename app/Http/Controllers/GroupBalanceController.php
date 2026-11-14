<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\GroupBalanceService;
use Illuminate\Http\JsonResponse;

class GroupBalanceController extends Controller
{
    public function __construct(
        protected GroupBalanceService $groupBalanceService
    ) {}

    public function index(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $balances = $this->groupBalanceService->calculateBalance($group);

        return response()->json($balances, 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettlementRequest;
use App\Models\Group;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;

class SettlementController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService = new SettlementService,
    ) {}

    /**
     * Lista o histórico de quitações do grupo.
     */
    public function index(Group $group): JsonResponse
    {
        return response()->json($this->settlementService->listForGroup($group), 200);
    }

    /**
     * Registra uma quitação (total ou parcial) entre dois participantes.
     */
    public function store(StoreSettlementRequest $request, Group $group): JsonResponse
    {
        $settlement = $this->settlementService->create($group, $request->validated());

        return response()->json($settlement, 201);
    }
}

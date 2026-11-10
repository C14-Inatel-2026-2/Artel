<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettlementRequest;
use App\Models\Group;
use App\Models\Settlement;
use Illuminate\Http\JsonResponse;

class SettlementController extends Controller
{
    /**
     * Lista o histórico de quitações do grupo.
     */
    public function index(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $settlements = $group->settlements()
            ->with(['payer:id,name,email', 'payee:id,name,email'])
            ->latest('settled_at')
            ->latest('id')
            ->get();

        return response()->json($settlements, 200);
    }

    /**
     * Registra uma quitação (total ou parcial) entre dois participantes.
     */
    public function store(StoreSettlementRequest $request, Group $group): JsonResponse
    {
        $data = $request->validated();

        $settlement = Settlement::create([
            'group_id' => $group->id,
            'paid_by' => $data['payer_id'],
            'paid_to' => $data['payee_id'],
            'amount' => $data['amount'],
            'settled_at' => now(),
        ]);

        $settlement->load(['payer:id,name,email', 'payee:id,name,email']);

        return response()->json($settlement, 201);
    }
}

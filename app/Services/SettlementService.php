<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Settlement;
use Illuminate\Database\Eloquent\Collection;

class SettlementService
{
    /**
     * Lista o histórico de quitações do grupo (mais recentes primeiro).
     *
     * @return Collection<int, Settlement>
     */
    public function listForGroup(Group $group): Collection
    {
        return $group->settlements()
            ->with(['payer:id,name,email', 'payee:id,name,email'])
            ->latest('settled_at')
            ->latest('id')
            ->get();
    }

    /**
     * Registra uma quitação (total ou parcial) entre dois participantes.
     *
     * @param  array{payer_id: int, payee_id: int, amount: float|string}  $data
     */
    public function create(Group $group, array $data): Settlement
    {
        $settlement = Settlement::create([
            'group_id' => $group->id,
            'paid_by' => $data['payer_id'],
            'paid_to' => $data['payee_id'],
            'amount' => $data['amount'],
            'settled_at' => now(),
        ]);

        return $settlement->load(['payer:id,name,email', 'payee:id,name,email']);
    }
}

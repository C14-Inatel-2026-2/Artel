<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettlementRequest;
use App\Models\Group;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;

class SettlementPageController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService,
    ) {}

    public function store(StoreSettlementRequest $request, Group $group): RedirectResponse
    {
        $this->settlementService->create($group, $request->validated());

        return redirect()
            ->to(route('web.groups.show', $group).'#saldos')
            ->with('status', 'Pagamento registrado.');
    }
}

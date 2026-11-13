<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseBalanceTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $joao;

    private User $maria;

    private User $pedro;

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();

        $this->joao = User::factory()->create(['name' => 'João']);
        $this->maria = User::factory()->create(['name' => 'Maria']);
        $this->pedro = User::factory()->create(['name' => 'Pedro']);
        $this->ana = User::factory()->create(['name' => 'Ana']);

        $this->group = Group::create(['name' => 'Viagem', 'user_id' => $this->joao->id]);
        $this->group->members()->attach([$this->joao->id, $this->maria->id, $this->pedro->id, $this->ana->id]);
    }

    private function balanceOf(array $balance, User $user): array
    {
        return collect($balance['balances'])->firstWhere('user.id', $user->id);
    }

    public function test_saldo_reune_pago_devido_estado_e_totais_do_grupo(): void
    {
        $service = app(ExpenseService::class);

        $service->createExpense($this->group, [
            'description' => 'Jantar',
            'amount' => 80,
            'paid_by' => $this->joao->id,
            'participants' => [$this->joao->id, $this->maria->id, $this->pedro->id, $this->ana->id],
        ]);

        $service->createExpense($this->group, [
            'description' => 'Táxi',
            'amount' => 60,
            'paid_by' => $this->maria->id,
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->joao->id, 'amount' => 10],
                ['user_id' => $this->maria->id, 'amount' => 20],
                ['user_id' => $this->pedro->id, 'amount' => 30],
            ],
        ]);

        app(SettlementService::class)->create($this->group, [
            'payer_id' => $this->pedro->id,
            'payee_id' => $this->joao->id,
            'amount' => 20,
        ]);

        $balance = $service->calculateBalance($this->group);

        $this->assertSame($this->group->id, $balance['group_id']);
        $this->assertSame('Viagem', $balance['group_name']);
        $this->assertSame(140.0, $balance['total_expenses']);
        $this->assertSame(2, $balance['expenses_count']);
        $this->assertCount(4, $balance['balances']);

        $joao = $this->balanceOf($balance, $this->joao);
        $this->assertSame(['id' => $this->joao->id, 'name' => 'João', 'email' => $this->joao->email], $joao['user']);
        $this->assertSame(80.0, $joao['total_paid']);
        $this->assertSame(30.0, $joao['total_owed']);
        $this->assertSame(30.0, $joao['net_balance']);
        $this->assertSame('a_receber', $joao['status']);

        $maria = $this->balanceOf($balance, $this->maria);
        $this->assertSame(60.0, $maria['total_paid']);
        $this->assertSame(40.0, $maria['total_owed']);
        $this->assertSame(20.0, $maria['net_balance']);
        $this->assertSame('a_receber', $maria['status']);

        $pedro = $this->balanceOf($balance, $this->pedro);
        $this->assertSame(0.0, $pedro['total_paid']);
        $this->assertSame(50.0, $pedro['total_owed']);
        $this->assertSame(-30.0, $pedro['net_balance']);
        $this->assertSame('a_pagar', $pedro['status']);

        $ana = $this->balanceOf($balance, $this->ana);
        $this->assertSame(0.0, $ana['total_paid']);
        $this->assertSame(20.0, $ana['total_owed']);
        $this->assertSame(-20.0, $ana['net_balance']);
        $this->assertSame('a_pagar', $ana['status']);
    }

    public function test_saldo_zerado_fica_quitado(): void
    {
        $service = app(ExpenseService::class);

        $service->createExpense($this->group, [
            'description' => 'Almoço',
            'amount' => 40,
            'paid_by' => $this->joao->id,
            'participants' => [$this->joao->id, $this->maria->id],
        ]);

        app(SettlementService::class)->create($this->group, [
            'payer_id' => $this->maria->id,
            'payee_id' => $this->joao->id,
            'amount' => 20,
        ]);

        $balance = $service->calculateBalance($this->group);

        $this->assertSame('quitado', $this->balanceOf($balance, $this->joao)['status']);
        $this->assertSame('quitado', $this->balanceOf($balance, $this->maria)['status']);
        $this->assertSame('quitado', $this->balanceOf($balance, $this->pedro)['status']);
        $this->assertSame([], $balance['suggested_transfers']);
    }

    public function test_grupo_sem_despesas_tem_totais_zerados_e_todos_quitados(): void
    {
        $balance = app(ExpenseService::class)->calculateBalance($this->group);

        $this->assertSame(0.0, $balance['total_expenses']);
        $this->assertSame(0, $balance['expenses_count']);
        $this->assertSame([], $balance['suggested_transfers']);
        $this->assertSame(['quitado'], array_values(array_unique(array_column($balance['balances'], 'status'))));
    }

    public function test_sugestoes_de_pagamento_acompanham_os_saldos(): void
    {
        $service = app(ExpenseService::class);

        $service->createExpense($this->group, [
            'description' => 'Hotel',
            'amount' => 100,
            'paid_by' => $this->joao->id,
            'participants' => [$this->joao->id, $this->maria->id],
        ]);

        $transfers = $service->calculateBalance($this->group)['suggested_transfers'];

        $this->assertCount(1, $transfers);
        $this->assertSame($this->maria->id, $transfers[0]['from']['id']);
        $this->assertSame($this->joao->id, $transfers[0]['to']['id']);
        $this->assertSame(50.0, $transfers[0]['amount']);
    }

    public function test_criar_e_editar_despesa_devolvem_pagador_e_parcelas_carregados(): void
    {
        $service = app(ExpenseService::class);

        $expense = $service->createExpense($this->group, [
            'description' => 'Mercado',
            'amount' => 30,
            'paid_by' => $this->joao->id,
            'participants' => [$this->joao->id, $this->maria->id],
        ]);

        $this->assertTrue($expense->relationLoaded('payer'));
        $this->assertTrue($expense->relationLoaded('splits'));
        $this->assertSame(['id', 'name', 'email'], array_keys($expense->payer->only(['id', 'name', 'email'])));
        $this->assertCount(2, $expense->splits);

        $updated = $service->updateExpense($expense, [
            'description' => 'Mercado do mês',
            'amount' => 45,
            'paid_by' => $this->maria->id,
            'participants' => [$this->joao->id, $this->maria->id, $this->pedro->id],
        ]);

        $this->assertSame('Mercado do mês', $updated->description);
        $this->assertSame($this->maria->id, $updated->paid_by);
        $this->assertTrue($updated->relationLoaded('payer'));
        $this->assertTrue($updated->relationLoaded('splits'));
        $this->assertCount(3, $updated->splits);
        $this->assertTrue($updated->splits->every(fn ($split) => $split->relationLoaded('user')));
    }
}

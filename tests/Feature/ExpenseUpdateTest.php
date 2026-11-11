<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExpenseUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $joao;

    private User $maria;

    private User $stranger;

    private Group $group;

    private Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Dono']);
        $this->joao = User::factory()->create(['name' => 'João']);
        $this->maria = User::factory()->create(['name' => 'Maria']);
        $this->stranger = User::factory()->create(['name' => 'Fora do grupo']);

        $this->group = Group::create(['name' => 'Viagem', 'user_id' => $this->owner->id]);
        $this->group->members()->attach([$this->owner->id, $this->joao->id, $this->maria->id]);

        $this->expense = app(ExpenseService::class)->createExpense($this->group, [
            'description' => 'Mercado',
            'amount' => 90,
            'paid_by' => $this->owner->id,
        ]);
    }

    private function update(array $overrides = [], ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->joao)->putJson("/api/expenses/{$this->expense->id}", array_merge([
            'description' => 'Mercado',
            'amount' => 90,
            'paid_by' => $this->owner->id,
        ], $overrides));
    }

    private function centsByUser(): array
    {
        return ExpenseSplit::where('expense_id', $this->expense->id)
            ->orderBy('user_id')
            ->get()
            ->mapWithKeys(fn (ExpenseSplit $split) => [$split->user_id => (int) round($split->amount * 100)])
            ->all();
    }

    private function balances(): array
    {
        return collect($this->actingAs($this->joao)->getJson("/api/groups/{$this->group->id}/balances")->json('balances'))
            ->mapWithKeys(fn (array $balance) => [$balance['user']['id'] => (float) $balance['net_balance']])
            ->all();
    }

    private function assertExpenseUnchanged(): void
    {
        $fresh = $this->expense->fresh();

        $this->assertSame('Mercado', $fresh->description);
        $this->assertEquals(90.0, (float) $fresh->amount);
        $this->assertSame($this->owner->id, $fresh->paid_by);
        $this->assertSame('equal', $fresh->split_type);
        $this->assertSame(
            [$this->owner->id => 3000, $this->joao->id => 3000, $this->maria->id => 3000],
            $this->centsByUser(),
        );
    }

    public function test_membro_atualiza_a_despesa_e_todas_as_parcelas(): void
    {
        $response = $this->update([
            'description' => 'Jantar',
            'amount' => 100,
            'paid_by' => $this->joao->id,
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 70],
                ['user_id' => $this->maria->id, 'amount' => 30],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('description', 'Jantar')
            ->assertJsonPath('paid_by', $this->joao->id)
            ->assertJsonPath('split_type', 'exact');
        $this->assertEquals(100.0, (float) $response->json('amount'));
        $this->assertSame([$this->owner->id => 7000, $this->maria->id => 3000], $this->centsByUser());
        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseCount('expense_splits', 2);
    }

    public function test_edicao_pode_trocar_para_equal_entre_participantes_escolhidos(): void
    {
        $this->update([
            'amount' => 100,
            'split_type' => 'equal',
            'participants' => [$this->joao->id, $this->maria->id],
        ])->assertOk();

        $this->assertSame([$this->joao->id => 5000, $this->maria->id => 5000], $this->centsByUser());
    }

    public function test_edicao_por_percentuais_fecha_o_valor_exato(): void
    {
        $this->update([
            'amount' => 10,
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $this->owner->id, 'percentage' => 33.34],
                ['user_id' => $this->joao->id, 'percentage' => 33.33],
                ['user_id' => $this->maria->id, 'percentage' => 33.33],
            ],
        ])->assertOk()->assertJsonPath('split_type', 'percentage');

        $this->assertSame(
            [$this->owner->id => 334, $this->joao->id => 333, $this->maria->id => 333],
            $this->centsByUser(),
        );
    }

    public function test_put_sem_split_type_volta_para_equal_entre_todos(): void
    {
        $this->update([
            'split_type' => 'exact',
            'splits' => [['user_id' => $this->owner->id, 'amount' => 90]],
        ])->assertOk();

        $this->update(['amount' => 10])->assertOk()->assertJsonPath('split_type', 'equal');

        $this->assertSame(
            [$this->owner->id => 334, $this->joao->id => 333, $this->maria->id => 333],
            $this->centsByUser(),
        );
    }

    public function test_editar_so_a_descricao_mantem_a_divisao(): void
    {
        $this->update(['description' => 'Mercado do mês'])->assertOk()->assertJsonPath('description', 'Mercado do mês');

        $this->assertSame(
            [$this->owner->id => 3000, $this->joao->id => 3000, $this->maria->id => 3000],
            $this->centsByUser(),
        );
    }

    public function test_saldos_refletem_a_edicao_sem_residuos_das_parcelas_antigas(): void
    {
        $before = $this->balances();
        $this->assertEquals(60.0, $before[$this->owner->id]);
        $this->assertEquals(-30.0, $before[$this->joao->id]);
        $this->assertEquals(-30.0, $before[$this->maria->id]);

        $this->update([
            'amount' => 100,
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 70],
                ['user_id' => $this->maria->id, 'amount' => 30],
            ],
        ])->assertOk();

        $after = $this->balances();
        $this->assertEquals(30.0, $after[$this->owner->id]);
        $this->assertEquals(0.0, $after[$this->joao->id]);
        $this->assertEquals(-30.0, $after[$this->maria->id]);

        $summary = $this->actingAs($this->joao)->getJson("/api/groups/{$this->group->id}/balances");
        $this->assertEquals(100.0, (float) $summary->json('total_expenses'));
        $this->assertSame(1, $summary->json('expenses_count'));
    }

    public function test_valor_negativo_ou_zero_retorna_422_e_nao_altera_nada(): void
    {
        $this->update(['amount' => -5])->assertStatus(422)->assertJsonValidationErrors(['amount']);
        $this->update(['amount' => 0])->assertStatus(422)->assertJsonValidationErrors(['amount']);

        $this->assertExpenseUnchanged();
    }

    public function test_pagador_fora_do_grupo_retorna_422_e_nao_altera_nada(): void
    {
        $this->update(['paid_by' => $this->stranger->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['paid_by']);

        $this->assertExpenseUnchanged();
    }

    public function test_soma_exata_diferente_do_total_retorna_422_e_nao_altera_nada(): void
    {
        $this->update([
            'amount' => 100,
            'description' => 'Outro nome',
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 60],
                ['user_id' => $this->joao->id, 'amount' => 30],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'A soma das parcelas (R$ 90,00) é diferente do valor da despesa (R$ 100,00).');

        $this->assertExpenseUnchanged();
    }

    public function test_soma_de_percentuais_diferente_de_100_retorna_422_e_nao_altera_nada(): void
    {
        $this->update([
            'description' => 'Outro nome',
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $this->owner->id, 'percentage' => 60],
                ['user_id' => $this->joao->id, 'percentage' => 39],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'A soma dos percentuais (99,00%) deve ser exatamente 100%.');

        $this->assertExpenseUnchanged();
    }

    public function test_usuario_fora_do_grupo_em_participantes_ou_parcelas_retorna_422_e_nao_altera_nada(): void
    {
        $this->update(['participants' => [$this->joao->id, $this->stranger->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participants.1']);

        $this->update([
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $this->owner->id, 'amount' => 45],
                ['user_id' => $this->stranger->id, 'amount' => 45],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['splits.1.user_id']);

        $this->assertExpenseUnchanged();
    }

    public function test_corpo_vazio_retorna_422_porque_o_put_substitui_a_despesa_inteira(): void
    {
        $this->actingAs($this->joao)
            ->putJson("/api/expenses/{$this->expense->id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description', 'amount', 'paid_by']);

        $this->assertExpenseUnchanged();
    }

    public function test_quem_nao_participa_do_grupo_recebe_403_e_nao_altera_nada(): void
    {
        $this->update([
            'description' => 'Tentativa de fora',
            'amount' => 999,
            'split_type' => 'exact',
            'splits' => [['user_id' => $this->owner->id, 'amount' => 999]],
        ], $this->stranger)->assertForbidden();

        $this->assertExpenseUnchanged();
    }

    public function test_o_403_tem_prioridade_sobre_a_validacao(): void
    {
        $this->update(['amount' => -5], $this->stranger)->assertForbidden();

        $this->assertExpenseUnchanged();
    }

    public function test_despesa_inexistente_retorna_404(): void
    {
        $this->actingAs($this->joao)
            ->putJson('/api/expenses/999999', ['description' => 'Jantar', 'amount' => 10, 'paid_by' => $this->owner->id])
            ->assertNotFound();
    }
}

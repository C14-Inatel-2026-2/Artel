<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $joao;

    private User $maria;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Dono']);
        $this->joao = User::factory()->create(['name' => 'João']);
        $this->maria = User::factory()->create(['name' => 'Maria']);

        $this->group = Group::create([
            'name' => 'Viagem',
            'user_id' => $this->owner->id,
        ]);

        $this->group->members()->attach([
            $this->owner->id,
            $this->joao->id,
            $this->maria->id,
        ]);

        Sanctum::actingAs($this->owner);
    }

    public function test_store_settlement_returns_created_settlement(): void
    {
        $response = $this->postJson("/api/groups/{$this->group->id}/settlements", [
            'payer_id' => $this->joao->id,
            'payee_id' => $this->maria->id,
            'amount' => 40.00,
        ]);

        $response->assertCreated()
            ->assertJsonPath('paid_by', $this->joao->id)
            ->assertJsonPath('paid_to', $this->maria->id)
            ->assertJsonPath('amount', '40.00')
            ->assertJsonPath('payer.name', 'João')
            ->assertJsonPath('payee.name', 'Maria')
            ->assertJsonStructure(['id', 'group_id', 'settled_at', 'created_at', 'updated_at']);

        $this->assertDatabaseHas('settlements', [
            'group_id' => $this->group->id,
            'paid_by' => $this->joao->id,
            'paid_to' => $this->maria->id,
            'amount' => 40.00,
        ]);
    }

    public function test_store_settlement_rejects_same_payer_and_payee(): void
    {
        $response = $this->postJson("/api/groups/{$this->group->id}/settlements", [
            'payer_id' => $this->joao->id,
            'payee_id' => $this->joao->id,
            'amount' => 10.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payer_id']);
    }

    public function test_store_settlement_rejects_non_member(): void
    {
        $outsider = User::factory()->create();

        $response = $this->postJson("/api/groups/{$this->group->id}/settlements", [
            'payer_id' => $outsider->id,
            'payee_id' => $this->maria->id,
            'amount' => 10.00,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payer_id']);
    }

    public function test_index_lists_settlements_history(): void
    {
        Settlement::create([
            'group_id' => $this->group->id,
            'paid_by' => $this->joao->id,
            'paid_to' => $this->maria->id,
            'amount' => 20.00,
            'settled_at' => now()->subHour(),
        ]);

        Settlement::create([
            'group_id' => $this->group->id,
            'paid_by' => $this->maria->id,
            'paid_to' => $this->owner->id,
            'amount' => 5.00,
            'settled_at' => now(),
        ]);

        $response = $this->getJson("/api/groups/{$this->group->id}/settlements");

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.amount', '5.00')
            ->assertJsonPath('1.amount', '20.00');
    }

    public function test_balance_reflects_registered_settlement(): void
    {
        // Maria pagou R$ 80,00; João e Maria dividem → cada um deve 40.
        // Antes do acerto: João -40 (a_pagar), Maria +40 (a_receber).
        $expense = Expense::create([
            'description' => 'Hotel',
            'amount' => 80.00,
            'paid_by' => $this->maria->id,
            'group_id' => $this->group->id,
        ]);

        // Divisão só entre João e Maria para espelhar o cenário do AC (dois participantes).
        ExpenseSplit::create(['expense_id' => $expense->id, 'user_id' => $this->joao->id, 'amount' => 40.00]);
        ExpenseSplit::create(['expense_id' => $expense->id, 'user_id' => $this->maria->id, 'amount' => 40.00]);

        $before = $this->getJson("/api/groups/{$this->group->id}/balances")->json('balances');
        $joaoBefore = collect($before)->firstWhere('user.id', $this->joao->id);
        $mariaBefore = collect($before)->firstWhere('user.id', $this->maria->id);

        $this->assertEquals(-40.0, $joaoBefore['net_balance']);
        $this->assertEquals(40.0, $mariaBefore['net_balance']);

        $this->postJson("/api/groups/{$this->group->id}/settlements", [
            'payer_id' => $this->joao->id,
            'payee_id' => $this->maria->id,
            'amount' => 40.00,
        ])->assertCreated();

        $after = $this->getJson("/api/groups/{$this->group->id}/balances")->json('balances');
        $joaoAfter = collect($after)->firstWhere('user.id', $this->joao->id);
        $mariaAfter = collect($after)->firstWhere('user.id', $this->maria->id);

        $this->assertEquals(0.0, $joaoAfter['net_balance']);
        $this->assertEquals('quitado', $joaoAfter['status']);
        $this->assertEquals(0.0, $mariaAfter['net_balance']);
        $this->assertEquals('quitado', $mariaAfter['status']);
    }
}

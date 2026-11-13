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

class GroupMemberRemovalTest extends TestCase
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

    public function test_cannot_remove_member_with_negative_balance(): void
    {
        $this->createSplitExpense(
            payer: $this->maria,
            amount: 80.00,
            splits: [
                $this->joao->id => 40.00,
                $this->maria->id => 40.00,
            ],
        );

        $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->joao->id}")
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'Não é possível remover o participante enquanto houver saldo pendente.',
            ]);

        $this->assertTrue($this->group->hasMember($this->joao));
    }

    public function test_cannot_remove_member_with_positive_balance(): void
    {
        $this->createSplitExpense(
            payer: $this->joao,
            amount: 80.00,
            splits: [
                $this->joao->id => 40.00,
                $this->maria->id => 40.00,
            ],
        );

        $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->joao->id}")
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'Não é possível remover o participante enquanto houver saldo pendente.',
            ]);

        $this->assertTrue($this->group->hasMember($this->joao));
    }

    public function test_cannot_remove_payer_of_active_expense_even_when_balance_is_zero(): void
    {
        $this->createSplitExpense(
            payer: $this->maria,
            amount: 80.00,
            splits: [
                $this->joao->id => 40.00,
                $this->maria->id => 40.00,
            ],
        );

        Settlement::create([
            'group_id' => $this->group->id,
            'paid_by' => $this->joao->id,
            'paid_to' => $this->maria->id,
            'amount' => 40.00,
            'settled_at' => now(),
        ]);

        $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->maria->id}")
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'Não é possível remover o participante que é pagador de uma despesa ativa no grupo.',
            ]);

        $this->assertTrue($this->group->hasMember($this->maria));
    }

    public function test_can_remove_member_with_zero_balance_who_is_not_a_payer(): void
    {
        $this->createSplitExpense(
            payer: $this->maria,
            amount: 80.00,
            splits: [
                $this->joao->id => 40.00,
                $this->maria->id => 40.00,
            ],
        );

        Settlement::create([
            'group_id' => $this->group->id,
            'paid_by' => $this->joao->id,
            'paid_to' => $this->maria->id,
            'amount' => 40.00,
            'settled_at' => now(),
        ]);

        $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->joao->id}")
            ->assertOk()
            ->assertExactJson([
                'message' => 'Participante removido do grupo com sucesso.',
            ]);

        $this->assertFalse($this->group->fresh()->hasMember($this->joao));
    }

    public function test_cannot_remove_group_creator(): void
    {
        $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->owner->id}")
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'O criador do grupo não pode ser removido.',
            ]);
    }

    public function test_returns_not_found_when_user_is_not_a_member(): void
    {
        $outsider = User::factory()->create(['name' => 'Fora']);

        $this->deleteJson("/api/groups/{$this->group->id}/members/{$outsider->id}")
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Este usuário não é participante deste grupo.',
            ]);
    }

    /**
     * @param  array<int, float>  $splits
     */
    private function createSplitExpense(User $payer, float $amount, array $splits): Expense
    {
        $expense = Expense::create([
            'description' => 'Hotel',
            'amount' => $amount,
            'paid_by' => $payer->id,
            'group_id' => $this->group->id,
        ]);

        foreach ($splits as $userId => $splitAmount) {
            ExpenseSplit::create([
                'expense_id' => $expense->id,
                'user_id' => $userId,
                'amount' => $splitAmount,
            ]);
        }

        return $expense;
    }
}

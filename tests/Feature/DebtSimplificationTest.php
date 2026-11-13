<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DebtSimplificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_balances_endpoint_includes_suggested_transfers(): void
    {
        $a = User::factory()->create(['name' => 'A']);
        $b = User::factory()->create(['name' => 'B']);
        $c = User::factory()->create(['name' => 'C']);

        $group = Group::create(['name' => 'Cadeia', 'user_id' => $a->id]);
        $group->members()->attach([$a->id, $b->id, $c->id]);

        // B pagou 50 (A deve a B): A -50, B +50
        $expenseAb = Expense::create([
            'description' => 'A deve B',
            'amount' => 50.00,
            'paid_by' => $b->id,
            'group_id' => $group->id,
        ]);
        ExpenseSplit::create(['expense_id' => $expenseAb->id, 'user_id' => $a->id, 'amount' => 50.00]);

        // C pagou 50 (B deve a C): B -50, C +50 → líquidos: A -50, B 0, C +50
        $expenseBc = Expense::create([
            'description' => 'B deve C',
            'amount' => 50.00,
            'paid_by' => $c->id,
            'group_id' => $group->id,
        ]);
        ExpenseSplit::create(['expense_id' => $expenseBc->id, 'user_id' => $b->id, 'amount' => 50.00]);

        Sanctum::actingAs($a);
        $response = $this->getJson("/api/groups/{$group->id}/balances");

        $response->assertOk()
            ->assertJsonPath('suggested_transfers.0.from.id', $a->id)
            ->assertJsonPath('suggested_transfers.0.to.id', $c->id)
            ->assertJsonPath('suggested_transfers.0.amount', 50)
            ->assertJsonCount(1, 'suggested_transfers');
    }

    public function test_suggested_transfers_empty_when_all_settled(): void
    {
        $owner = User::factory()->create();
        $group = Group::create(['name' => 'Quitado', 'user_id' => $owner->id]);
        $group->members()->attach($owner->id);

        Sanctum::actingAs($owner);
        $response = $this->getJson("/api/groups/{$group->id}/balances");

        $response->assertOk()
            ->assertJsonPath('suggested_transfers', []);
    }
}

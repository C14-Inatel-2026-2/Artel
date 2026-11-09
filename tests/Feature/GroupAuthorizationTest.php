<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GroupAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $member;

    private User $otherMember;

    private User $stranger;

    private Group $group;

    private Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->otherMember = User::factory()->create();
        $this->stranger = User::factory()->create();

        $this->group = Group::create(['name' => 'Viagem', 'user_id' => $this->owner->id]);
        $this->group->members()->attach([$this->owner->id, $this->member->id, $this->otherMember->id]);

        $this->expense = Expense::create([
            'description' => 'Mercado',
            'amount' => 90,
            'paid_by' => $this->owner->id,
            'group_id' => $this->group->id,
        ]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function groupRoutes(): array
    {
        return [
            'detalhar grupo' => ['GET', '/api/groups/{group}'],
            'atualizar grupo' => ['PUT', '/api/groups/{group}'],
            'excluir grupo' => ['DELETE', '/api/groups/{group}'],
            'listar participantes' => ['GET', '/api/groups/{group}/members'],
            'adicionar participante' => ['POST', '/api/groups/{group}/members'],
            'remover participante' => ['DELETE', '/api/groups/{group}/members/{member}'],
            'remover o dono' => ['DELETE', '/api/groups/{group}/members/{owner}'],
            'listar despesas' => ['GET', '/api/groups/{group}/expenses'],
            'criar despesa' => ['POST', '/api/groups/{group}/expenses'],
            'saldos do grupo' => ['GET', '/api/groups/{group}/balances'],
            'detalhar despesa' => ['GET', '/api/expenses/{expense}'],
            'excluir despesa' => ['DELETE', '/api/expenses/{expense}'],
        ];
    }

    #[DataProvider('groupRoutes')]
    public function test_stranger_receives_403_on_group_routes(string $method, string $uri): void
    {
        Sanctum::actingAs($this->stranger);

        $this->json($method, $this->resolveUri($uri))
            ->assertForbidden()
            ->assertExactJson(['message' => 'Acesso negado.']);
    }

    public function test_stranger_cannot_change_group_data(): void
    {
        Sanctum::actingAs($this->stranger);

        $this->putJson("/api/groups/{$this->group->id}", ['name' => 'Invadido'])->assertForbidden();
        $this->deleteJson("/api/groups/{$this->group->id}")->assertForbidden();
        $this->deleteJson("/api/expenses/{$this->expense->id}")->assertForbidden();

        $this->assertDatabaseHas('groups', ['id' => $this->group->id, 'name' => 'Viagem']);
        $this->assertDatabaseHas('expenses', ['id' => $this->expense->id]);
    }

    public function test_stranger_cannot_discover_registered_emails(): void
    {
        Sanctum::actingAs($this->stranger);

        $this->postJson("/api/groups/{$this->group->id}/members", ['email' => 'ninguem@example.com'])
            ->assertForbidden();
        $this->postJson("/api/groups/{$this->group->id}/members", ['email' => $this->otherMember->email])
            ->assertForbidden();
    }

    public function test_stranger_cannot_discover_group_members_through_expense_validation(): void
    {
        Sanctum::actingAs($this->stranger);

        $this->postJson("/api/groups/{$this->group->id}/expenses", [
            'description' => 'Jantar',
            'amount' => 50,
            'paid_by' => $this->stranger->id,
        ])->assertForbidden();
    }

    public function test_member_can_view_group_members_expenses_and_balances(): void
    {
        Sanctum::actingAs($this->member);

        $this->getJson("/api/groups/{$this->group->id}")->assertOk();
        $this->getJson("/api/groups/{$this->group->id}/members")->assertOk()->assertJsonCount(3);
        $this->getJson("/api/groups/{$this->group->id}/expenses")->assertOk()->assertJsonCount(1);
        $this->getJson("/api/groups/{$this->group->id}/balances")->assertOk();
        $this->getJson("/api/expenses/{$this->expense->id}")->assertOk();
    }

    public function test_member_can_add_members_and_manage_expenses(): void
    {
        Sanctum::actingAs($this->member);

        $this->postJson("/api/groups/{$this->group->id}/members", ['user_id' => $this->stranger->id])
            ->assertCreated();
        $this->postJson("/api/groups/{$this->group->id}/expenses", [
            'description' => 'Jantar',
            'amount' => 60,
            'paid_by' => $this->member->id,
        ])->assertCreated();
        $this->deleteJson("/api/expenses/{$this->expense->id}")->assertOk();
    }

    public function test_member_cannot_update_or_delete_group(): void
    {
        Sanctum::actingAs($this->member);

        $this->putJson("/api/groups/{$this->group->id}", ['name' => 'Renomeado'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Acesso negado.']);
        $this->deleteJson("/api/groups/{$this->group->id}")->assertForbidden();

        $this->assertDatabaseHas('groups', ['id' => $this->group->id, 'name' => 'Viagem']);
    }

    public function test_member_cannot_remove_another_member(): void
    {
        Sanctum::actingAs($this->member);

        $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->otherMember->id}")
            ->assertForbidden();

        $this->assertTrue($this->group->members()->where('users.id', $this->otherMember->id)->exists());
    }

    public function test_member_can_leave_group(): void
    {
        Sanctum::actingAs($this->member);

        $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->member->id}")->assertOk();

        $this->assertFalse($this->group->members()->where('users.id', $this->member->id)->exists());
    }

    public function test_owner_cannot_be_removed_from_group(): void
    {
        foreach ([$this->owner, $this->member] as $user) {
            Sanctum::actingAs($user);

            $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->owner->id}")
                ->assertUnprocessable()
                ->assertExactJson(['message' => 'O criador do grupo não pode ser removido.']);
        }
    }

    public function test_owner_can_update_group_and_remove_members(): void
    {
        Sanctum::actingAs($this->owner);

        $this->putJson("/api/groups/{$this->group->id}", ['name' => 'Renomeado'])
            ->assertOk()
            ->assertJsonPath('name', 'Renomeado');
        $this->deleteJson("/api/groups/{$this->group->id}/members/{$this->otherMember->id}")->assertOk();
    }

    public function test_owner_still_receives_validation_errors(): void
    {
        Sanctum::actingAs($this->owner);

        $this->putJson("/api/groups/{$this->group->id}", ['name' => 'ab'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_owner_can_delete_group(): void
    {
        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/groups/{$this->group->id}")->assertOk();

        $this->assertDatabaseMissing('groups', ['id' => $this->group->id]);
    }

    private function resolveUri(string $uri): string
    {
        return strtr($uri, [
            '{group}' => $this->group->id,
            '{expense}' => $this->expense->id,
            '{member}' => $this->member->id,
            '{owner}' => $this->owner->id,
        ]);
    }
}

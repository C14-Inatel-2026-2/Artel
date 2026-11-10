<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProtectedRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function protectedRoutes(): array
    {
        return [
            'listar grupos' => ['GET', '/api/groups'],
            'criar grupo' => ['POST', '/api/groups'],
            'detalhar grupo' => ['GET', '/api/groups/1'],
            'atualizar grupo' => ['PUT', '/api/groups/1'],
            'excluir grupo' => ['DELETE', '/api/groups/1'],
            'listar participantes' => ['GET', '/api/groups/1/members'],
            'adicionar participante' => ['POST', '/api/groups/1/members'],
            'remover participante' => ['DELETE', '/api/groups/1/members/1'],
            'listar despesas' => ['GET', '/api/groups/1/expenses'],
            'criar despesa' => ['POST', '/api/groups/1/expenses'],
            'saldos do grupo' => ['GET', '/api/groups/1/balances'],
            'listar quitações' => ['GET', '/api/groups/1/settlements'],
            'registrar quitação' => ['POST', '/api/groups/1/settlements'],
            'detalhar despesa' => ['GET', '/api/expenses/1'],
            'excluir despesa' => ['DELETE', '/api/expenses/1'],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_route_returns_401_without_token(string $method, string $uri): void
    {
        $this->json($method, $uri)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Não autenticado.']);
    }

    #[DataProvider('protectedRoutes')]
    public function test_route_returns_401_json_without_accept_header(string $method, string $uri): void
    {
        $this->call($method, $uri)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Não autenticado.']);
    }

    public function test_route_returns_401_with_invalid_token(): void
    {
        $this->withToken('token-invalido')
            ->getJson('/api/groups')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Não autenticado.']);
    }

    public function test_route_is_accessible_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->getJson('/api/groups')->assertOk();
    }

    public function test_index_lists_only_groups_of_authenticated_user(): void
    {
        $firstUser = User::factory()->create();
        $authenticatedUser = User::factory()->create();
        $foreignGroup = Group::create(['name' => 'Grupo alheio', 'user_id' => $firstUser->id]);
        $ownGroup = Group::create(['name' => 'Meu grupo', 'user_id' => $authenticatedUser->id]);
        $sharedGroup = Group::create(['name' => 'Grupo compartilhado', 'user_id' => $firstUser->id]);
        $sharedGroup->members()->attach($authenticatedUser->id);

        Sanctum::actingAs($authenticatedUser);

        $groupIds = $this->getJson('/api/groups')->assertOk()->json('*.id');

        $this->assertEqualsCanonicalizing([$ownGroup->id, $sharedGroup->id], $groupIds);
        $this->assertNotContains($foreignGroup->id, $groupIds);
    }

    public function test_store_sets_authenticated_user_as_owner_and_member(): void
    {
        User::factory()->create();
        $authenticatedUser = User::factory()->create();

        Sanctum::actingAs($authenticatedUser);

        $response = $this->postJson('/api/groups', ['name' => 'Viagem'])
            ->assertCreated()
            ->assertJsonPath('user_id', $authenticatedUser->id)
            ->assertJsonPath('owner.id', $authenticatedUser->id);

        $this->assertSame([$authenticatedUser->id], $response->json('members.*.id'));
    }
}

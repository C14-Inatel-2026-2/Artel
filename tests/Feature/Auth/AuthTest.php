<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Usuário cadastrado com sucesso.')
            ->assertJsonPath('user.email', 'maria@example.com')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token'])
            ->assertJsonMissingPath('user.password');

        $user = User::where('email', 'maria@example.com')->first();
        $this->assertTrue(Hash::check('senha1234', $user->password));
        $this->assertCount(1, $user->tokens);
    }

    public function test_register_returns_validation_errors_in_portuguese(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'senha1234',
            'password_confirmation' => 'outra-senha',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'Este e-mail já está cadastrado.',
                'password' => 'A confirmação de senha não confere.',
            ]);
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'senha1234']);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'senha1234',
            'device_name' => 'celular',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login realizado com sucesso.')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token']);

        $this->assertSame('celular', $user->tokens()->first()->name);
    }

    public function test_login_returns_401_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'senha1234']);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'senha-errada',
        ]);

        $response->assertUnauthorized()
            ->assertExactJson(['message' => 'E-mail ou senha inválidos.']);
        $this->assertCount(0, $user->tokens);
    }

    public function test_login_returns_401_with_unknown_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'ninguem@example.com',
            'password' => 'senha1234',
        ]);

        $response->assertUnauthorized()
            ->assertExactJson(['message' => 'E-mail ou senha inválidos.']);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $payload = ['email' => 'ninguem@example.com', 'password' => 'senha1234'];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', $payload)->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', $payload)->assertTooManyRequests();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    public function test_me_returns_401_without_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('celular')->plainTextToken;
        $otherToken = $user->createToken('notebook')->plainTextToken;

        $this->withToken($currentToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertExactJson(['message' => 'Logout realizado com sucesso.']);

        $this->assertSame(['notebook'], $user->tokens()->pluck('name')->all());

        $this->app['auth']->forgetGuards();
        $this->withToken($currentToken)->getJson('/api/auth/me')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)->getJson('/api/auth/me')->assertOk();
    }
}

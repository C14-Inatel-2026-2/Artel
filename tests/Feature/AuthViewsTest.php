<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_de_login_renderiza_com_layout_e_formulario(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('<title>Entrar - Artel</title>', false);
        $response->assertSee('action="'.url('/user/login').'"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="_token"', false);
    }

    public function test_tela_de_cadastro_renderiza_com_layout_e_formulario(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('<title>Criar conta - Artel</title>', false);
        $response->assertSee('action="'.url('/user/register').'"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
    }

    public function test_login_com_credenciais_invalidas_exibe_mensagem_de_erro(): void
    {
        $response = $this->from('/login')->followingRedirects()->post('/user/login', [
            'email' => 'ninguem@example.com',
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Email ou senha errado');
        $response->assertSee('role="alert"', false);
        $response->assertSee('data-timeout="8000"', false);
        $response->assertSee('value="ninguem@example.com"', false);
    }

    public function test_login_sem_campos_exibe_mensagens_de_validacao(): void
    {
        $this->from('/login')->post('/user/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_cadastro_com_dados_invalidos_exibe_erros_e_mantem_valores(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $response = $this->from('/register')->followingRedirects()->post('/user/register', [
            'name' => 'Maria',
            'email' => 'existente@example.com',
            'password' => 'curta',
            'password_confirmation' => 'diferente',
        ]);

        $response->assertStatus(200);
        $response->assertSee('value="Maria"', false);
        $response->assertSee('value="existente@example.com"', false);
        $response->assertSee('border-red-400', false);
        $response->assertSee('aria-invalid="true"', false);
        $response->assertSee('id="email-error"', false);
        $response->assertSee('id="password-error"', false);
    }

    public function test_cadastro_valido_cria_usuario_e_redireciona_para_o_login_com_aviso(): void
    {
        $this->post('/user/register', [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Conta criada. Entre para continuar.');

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
        $this->assertGuest();
    }

    public function test_aviso_de_conta_criada_aparece_na_tela_de_login(): void
    {
        $this->followingRedirects()->post('/user/register', [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])
            ->assertSee('Conta criada. Entre para continuar.')
            ->assertSee('role="status"', false);
    }

    public function test_login_valido_autentica_e_regenera_a_sessao_e_o_token_csrf(): void
    {
        $user = User::factory()->create(['password' => 'senha-segura-123']);

        $this->startSession();
        $idAntes = session()->getId();
        $tokenAntes = session()->token();

        $this->post('/user/login', ['email' => $user->email, 'password' => 'senha-segura-123'])
            ->assertRedirect('/groups');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($idAntes, session()->getId());
        $this->assertNotSame($tokenAntes, session()->token());
    }

    public function test_usuario_logado_e_redirecionado_ao_abrir_login_e_cadastro(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect('/groups');
        $this->actingAs($user)->get('/register')->assertRedirect('/groups');
        $this->actingAs($user)->post('/user/login', [])->assertRedirect('/groups');
        $this->actingAs($user)->post('/user/register', [])->assertRedirect('/groups');
    }

    public function test_raiz_manda_o_logado_para_os_grupos_e_o_visitante_para_o_login(): void
    {
        $this->get('/')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/groups');
    }

    public function test_aviso_do_cadastro_chega_ao_login_sem_passar_pela_raiz(): void
    {
        $this->post('/user/register', [
            'name' => 'Ana Teste',
            'email' => 'ana@example.com',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])->assertRedirect('/login');

        $this->followingRedirects()
            ->post('/user/register', [
                'name' => 'Bruno Teste',
                'email' => 'bruno@example.com',
                'password' => 'senha-segura-123',
                'password_confirmation' => 'senha-segura-123',
            ])
            ->assertSee('Conta criada. Entre para continuar.');
    }

    public function test_sair_encerra_a_sessao_e_volta_ao_login_com_aviso(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Você saiu da sua conta.');

        $this->assertGuest();

        $this->get('/groups')->assertRedirect('/login');
    }

    public function test_sair_regenera_a_sessao_e_o_token_csrf(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->startSession();
        $idAntes = session()->getId();
        $tokenAntes = session()->token();

        $this->post('/logout');

        $this->assertNotSame($idAntes, session()->getId());
        $this->assertNotSame($tokenAntes, session()->token());
    }

    public function test_visitante_nao_consegue_sair(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_logout_da_web_nao_aceita_get(): void
    {
        $this->actingAs(User::factory()->create())->get('/logout')->assertStatus(405);

        $this->assertAuthenticated();
    }

}

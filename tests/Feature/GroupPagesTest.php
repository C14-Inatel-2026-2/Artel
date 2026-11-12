<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupPagesTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(User $owner, string $name, array $members = []): Group
    {
        $group = Group::create(['name' => $name, 'user_id' => $owner->id]);
        $group->members()->attach(array_merge([$owner->id], array_map(fn (User $user) => $user->id, $members)));

        return $group;
    }

    public function test_visitante_e_redirecionado_para_o_login(): void
    {
        $group = $this->makeGroup(User::factory()->create(), 'Viagem');

        $this->get('/groups')->assertRedirect('/');
        $this->get('/groups/'.$group->id)->assertRedirect('/');
        $this->post('/groups', ['name' => 'Novo grupo'])->assertRedirect('/');
    }

    public function test_lista_somente_os_grupos_do_usuario_autenticado(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();

        $this->makeGroup($ana, 'Viagem da turma');
        $this->makeGroup($bruno, 'Churrasco de domingo', [$ana]);
        $this->makeGroup($bruno, 'Grupo secreto do Bruno');

        $response = $this->actingAs($ana)->get('/groups');

        $response->assertOk();
        $response->assertSee('Viagem da turma');
        $response->assertSee('Churrasco de domingo');
        $response->assertDontSee('Grupo secreto do Bruno');
        $response->assertSee('Você criou este grupo');
        $response->assertSee('Criado por '.$bruno->name);
    }

    public function test_cada_grupo_da_listagem_leva_aos_detalhes(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->get('/groups')
            ->assertSee('href="'.route('web.groups.show', $group).'"', false);
    }

    public function test_listagem_mostra_a_contagem_de_participantes_e_despesas_de_cada_grupo(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Teste']);
        $viagem = $this->makeGroup($ana, 'Viagem da turma');
        $this->makeGroup($ana, 'República');

        foreach (['Hotel', 'Passagens', 'Jantar'] as $description) {
            Expense::create([
                'description' => $description,
                'amount' => 100,
                'paid_by' => $ana->id,
                'group_id' => $viagem->id,
            ]);
        }

        $this->actingAs($ana)->get('/groups')
            ->assertSee('3 despesas')
            ->assertSee('0 despesas')
            ->assertSee('1 participante');
    }

    public function test_botao_criar_grupo_abre_o_modal_com_o_formulario(): void
    {
        $this->actingAs(User::factory()->create())->get('/groups')
            ->assertOk()
            ->assertSee('data-open-new-group', false)
            ->assertSee('cursor-pointer', false)
            ->assertSee('id="novo-grupo-modal"', false)
            ->assertSee('aria-label="Fechar"', false)
            ->assertSee('action="'.route('web.groups.store').'"', false)
            ->assertDontSee('Olá,')
            ->assertDontSee('openModal();', false);
    }

    public function test_listagem_mostra_as_iniciais_dos_participantes(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Teste']);
        $quirino = User::factory()->create(['name' => 'quirino Teste']);
        $this->makeGroup($ana, 'Viagem da turma', [$quirino]);

        $response = $this->actingAs($ana)->get('/groups');

        $response->assertSee('2 participantes');
        $this->assertMatchesRegularExpression('/ring-white[^>]*>\s*A\s*</', $response->getContent());
        $this->assertMatchesRegularExpression('/ring-white[^>]*>\s*Q\s*</', $response->getContent());
    }

    public function test_listagem_vazia_orienta_a_criar_o_primeiro_grupo(): void
    {
        $this->actingAs(User::factory()->create())->get('/groups')
            ->assertOk()
            ->assertSee('Você ainda não participa de nenhum grupo.')
            ->assertSee('Use o botão Criar grupo para criar o primeiro');
    }

    public function test_cria_grupo_e_ele_aparece_na_listagem(): void
    {
        $ana = User::factory()->create();

        $this->actingAs($ana)
            ->post('/groups', ['name' => 'República'])
            ->assertRedirect(route('web.groups.index'));

        $group = Group::where('name', 'República')->firstOrFail();

        $this->assertSame($ana->id, $group->user_id);
        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'user_id' => $ana->id]);

        $this->actingAs($ana)->get('/groups')
            ->assertSee('República')
            ->assertSee('Grupo criado com sucesso.')
            ->assertSee('1 participante');
    }

    public function test_nome_invalido_nao_cria_grupo_e_mostra_a_mensagem(): void
    {
        $ana = User::factory()->create();

        $this->actingAs($ana)
            ->from('/groups')
            ->post('/groups', ['name' => 'ab'])
            ->assertRedirect('/groups')
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('groups', 0);

        $this->actingAs($ana)
            ->from('/groups')
            ->followingRedirects()
            ->post('/groups', ['name' => 'ab'])
            ->assertSee('O nome do grupo deve ter pelo menos 3 caracteres.')
            ->assertSee('value="ab"', false)
            ->assertSee('openModal();', false);
    }

    public function test_participante_ve_os_detalhes_do_grupo(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $this->actingAs($bruno)->get('/groups/'.$group->id)
            ->assertOk()
            ->assertSee('Viagem da turma')
            ->assertSee('Criado por '.$ana->name)
            ->assertSee($ana->email)
            ->assertSee($bruno->email)
            ->assertSee('Dono do grupo');
    }

    public function test_quem_nao_participa_nao_ve_os_detalhes(): void
    {
        $group = $this->makeGroup(User::factory()->create(), 'Viagem da turma');

        $this->actingAs(User::factory()->create())
            ->get('/groups/'.$group->id)
            ->assertForbidden();
    }
}

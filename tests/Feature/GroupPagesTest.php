<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Group;
use App\Models\Settlement;
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

    public function test_detalhes_mostram_total_gasto_saldo_e_despesas(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Teste']);
        $bruno = User::factory()->create(['name' => 'Bruno Teste']);
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        Expense::create(['description' => 'Hotel', 'amount' => 600, 'paid_by' => $ana->id, 'group_id' => $group->id]);

        $this->actingAs($bruno)->get('/groups/'.$group->id)
            ->assertOk()
            ->assertSee('Total gasto')
            ->assertSee('R$ 600,00')
            ->assertSee('Seu saldo')
            ->assertSee('Hotel')
            ->assertSee('Pago por Ana Teste')
            ->assertSee('Nova despesa')
            ->assertSee('id="membros-modal"', false);
    }

    public function test_detalhes_mostram_saldos_e_quem_paga_quem(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Teste']);
        $bruno = User::factory()->create(['name' => 'Bruno Teste']);
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', [
            'description' => 'Hotel',
            'amount' => 100,
            'paid_by' => $ana->id,
        ]);

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('+ R$ 50,00')
            ->assertSee('- R$ 50,00')
            ->assertSee('Bruno Teste &rarr; Ana Teste', false)
            ->assertSee('action="'.route('web.groups.settlements.store', $group).'"', false)
            ->assertSee('Quitar');
    }

    public function test_sem_pendencias_mostra_que_esta_tudo_certo(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('Nenhuma despesa registrada ainda.')
            ->assertSee('ninguém precisa pagar ninguém');
    }

    public function test_cria_despesa_dividida_igualmente_pela_pagina(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $this->actingAs($ana)
            ->post('/groups/'.$group->id.'/expenses', ['description' => 'Jantar', 'amount' => '80.50', 'paid_by' => $ana->id])
            ->assertRedirect(route('web.groups.show', $group))
            ->assertSessionHas('status', 'Despesa registrada.');

        $expense = Expense::where('description', 'Jantar')->firstOrFail();

        $this->assertSame($group->id, $expense->group_id);
        $this->assertSame('equal', $expense->split_type);
        $this->assertDatabaseCount('expense_splits', 2);
    }

    public function test_despesa_invalida_nao_e_criada_e_reabre_o_modal(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->post('/groups/'.$group->id.'/expenses', ['description' => 'ab', 'amount' => '0', 'paid_by' => $ana->id])
            ->assertSessionHasErrors(['description', 'amount']);

        $this->assertDatabaseCount('expenses', 0);

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->followingRedirects()
            ->post('/groups/'.$group->id.'/expenses', ['description' => 'ab', 'amount' => '0', 'paid_by' => $ana->id])
            ->assertSee('A descrição deve ter pelo menos 3 caracteres.')
            ->assertSee("openDialog('nova-despesa-modal');", false);
    }

    public function test_pagador_de_fora_do_grupo_e_recusado(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');
        $estranho = User::factory()->create();

        $this->actingAs($ana)
            ->post('/groups/'.$group->id.'/expenses', ['description' => 'Jantar', 'amount' => 50, 'paid_by' => $estranho->id])
            ->assertSessionHasErrors('paid_by');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_quem_nao_participa_nao_cria_despesa_nem_quitacao(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');
        $estranho = User::factory()->create();

        $this->actingAs($estranho)
            ->post('/groups/'.$group->id.'/expenses', ['description' => 'Jantar', 'amount' => 50, 'paid_by' => $ana->id])
            ->assertForbidden();

        $this->actingAs($estranho)
            ->post('/groups/'.$group->id.'/settlements', ['payer_id' => $ana->id, 'payee_id' => $estranho->id, 'amount' => 10])
            ->assertForbidden();

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('settlements', 0);
    }

    public function test_quitar_registra_o_pagamento_e_zera_a_pendencia(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Teste']);
        $bruno = User::factory()->create(['name' => 'Bruno Teste']);
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        Expense::create(['description' => 'Hotel', 'amount' => 100, 'paid_by' => $ana->id, 'group_id' => $group->id]);
        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', ['description' => 'Jantar', 'amount' => 100, 'paid_by' => $ana->id]);

        $this->actingAs($bruno)
            ->post('/groups/'.$group->id.'/settlements', ['payer_id' => $bruno->id, 'payee_id' => $ana->id, 'amount' => '50.00'])
            ->assertRedirect(route('web.groups.show', $group).'#saldos')
            ->assertSessionHas('status', 'Pagamento registrado.');

        $this->assertSame(1, Settlement::count());
        $this->assertDatabaseHas('settlements', ['group_id' => $group->id, 'paid_by' => $bruno->id, 'paid_to' => $ana->id]);
    }

    public function test_visitante_nao_cria_despesa_nem_quitacao(): void
    {
        $group = $this->makeGroup(User::factory()->create(), 'Viagem');

        $this->post('/groups/'.$group->id.'/expenses', [])->assertRedirect('/');
        $this->post('/groups/'.$group->id.'/settlements', [])->assertRedirect('/');
    }

    public function test_formulario_de_despesa_oferece_os_tres_modos_de_divisao(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Teste']);
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('name="split_type"', false)
            ->assertSee('Igualmente')
            ->assertSee('Por valor exato')
            ->assertSee('Por percentual')
            ->assertSee('name="participants[]"', false)
            ->assertSee('name="splits[0][amount]"', false)
            ->assertSee('name="splits[0][percentage]"', false)
            ->assertSee('id="divisao-modal"', false)
            ->assertSee('data-open-modal="divisao-modal"', false)
            ->assertSee('Configurar divisão')
            ->assertSee('form="despesa-form"', false);
    }

    public function test_divisao_igual_entre_participantes_escolhidos(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $carla = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno, $carla]);

        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', [
            'description' => 'Jantar',
            'amount' => '90',
            'paid_by' => $ana->id,
            'split_type' => 'equal',
            'participants' => [$ana->id, $bruno->id],
        ])->assertRedirect(route('web.groups.show', $group));

        $expense = Expense::where('description', 'Jantar')->firstOrFail();

        $this->assertSame(2, $expense->splits()->count());
        $this->assertDatabaseMissing('expense_splits', ['expense_id' => $expense->id, 'user_id' => $carla->id]);
    }

    public function test_divisao_por_valor_exato(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', [
            'description' => 'Jantar',
            'amount' => '100',
            'paid_by' => $ana->id,
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $ana->id, 'amount' => '30'],
                ['user_id' => $bruno->id, 'amount' => '70'],
            ],
        ])->assertRedirect(route('web.groups.show', $group));

        $expense = Expense::where('description', 'Jantar')->firstOrFail();

        $this->assertSame('exact', $expense->split_type);
        $this->assertDatabaseHas('expense_splits', ['expense_id' => $expense->id, 'user_id' => $bruno->id, 'amount' => '70.00']);
    }

    public function test_divisao_por_percentual(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', [
            'description' => 'Jantar',
            'amount' => '200',
            'paid_by' => $ana->id,
            'split_type' => 'percentage',
            'splits' => [
                ['user_id' => $ana->id, 'percentage' => '25'],
                ['user_id' => $bruno->id, 'percentage' => '75'],
            ],
        ])->assertRedirect(route('web.groups.show', $group));

        $expense = Expense::where('description', 'Jantar')->firstOrFail();

        $this->assertDatabaseHas('expense_splits', ['expense_id' => $expense->id, 'user_id' => $ana->id, 'amount' => '50.00']);
        $this->assertDatabaseHas('expense_splits', ['expense_id' => $expense->id, 'user_id' => $bruno->id, 'amount' => '150.00']);
    }

    public function test_soma_dos_valores_diferente_do_total_mostra_o_erro_e_reabre_o_modal(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $payload = [
            'description' => 'Jantar',
            'amount' => '100',
            'paid_by' => $ana->id,
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $ana->id, 'amount' => '30'],
                ['user_id' => $bruno->id, 'amount' => '40'],
            ],
        ];

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->post('/groups/'.$group->id.'/expenses', $payload)
            ->assertSessionHasErrors('splits');

        $this->assertDatabaseCount('expenses', 0);

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->followingRedirects()
            ->post('/groups/'.$group->id.'/expenses', $payload)
            ->assertSee('é diferente do valor da despesa')
            ->assertSee("openDialog('nova-despesa-modal');", false)
            ->assertSee('value="exact" selected', false);
    }

    public function test_percentuais_que_nao_somam_100_sao_recusados(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', [
            'description' => 'Jantar',
            'amount' => '100',
            'paid_by' => $ana->id,
            'split_type' => 'percentage',
            'splits' => [['user_id' => $ana->id, 'percentage' => '60']],
        ])->assertSessionHasErrors('splits');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_participante_de_fora_do_grupo_na_divisao_e_recusado(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');
        $estranho = User::factory()->create();

        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', [
            'description' => 'Jantar',
            'amount' => '100',
            'paid_by' => $ana->id,
            'split_type' => 'equal',
            'participants' => [$ana->id, $estranho->id],
        ])->assertSessionHasErrors('participants.1');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_modal_de_participantes_tem_o_campo_de_convite_por_email(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('action="'.route('web.groups.members.store', $group).'"', false)
            ->assertSee('Convidar por e-mail')
            ->assertSee('max-w-lg', false)
            ->assertDontSee("openDialog('membros-modal');", false);
    }

    public function test_convida_participante_por_email(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create(['name' => 'Bruno Teste']);
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)
            ->post('/groups/'.$group->id.'/members', ['email' => $bruno->email])
            ->assertRedirect(route('web.groups.show', $group))
            ->assertSessionHas('status', 'Bruno Teste foi adicionado ao grupo.');

        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'user_id' => $bruno->id]);
    }

    public function test_participante_tambem_pode_convidar(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $carla = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $this->actingAs($bruno)
            ->post('/groups/'.$group->id.'/members', ['email' => $carla->email])
            ->assertRedirect(route('web.groups.show', $group));

        $this->assertTrue($group->hasMember($carla));
    }

    public function test_convite_com_email_desconhecido_ou_repetido_mostra_o_erro_e_reabre_o_modal(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->post('/groups/'.$group->id.'/members', ['email' => 'ninguem@example.com'])
            ->assertSessionHasErrors(['email' => 'Nenhum usuário encontrado com este e-mail.']);

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->followingRedirects()
            ->post('/groups/'.$group->id.'/members', ['email' => $ana->email])
            ->assertSee('Este usuário já faz parte do grupo.')
            ->assertSee("openDialog('membros-modal');", false);

        $this->assertSame(1, $group->members()->count());
    }

    public function test_quem_nao_participa_nao_convida(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');
        $estranho = User::factory()->create();

        $this->actingAs($estranho)
            ->post('/groups/'.$group->id.'/members', ['email' => $estranho->email])
            ->assertForbidden();

        $this->assertFalse($group->hasMember($estranho));
    }

    public function test_divisao_so_fica_disponivel_depois_de_informar_o_valor(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('Informe o valor da despesa para escolher como dividir.')
            ->assertSee('name="split_type"'."\n".'                    disabled', false);

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->followingRedirects()
            ->post('/groups/'.$group->id.'/expenses', ['description' => 'ab', 'amount' => '50', 'paid_by' => $ana->id])
            ->assertDontSee('name="split_type"'."\n".'                    disabled', false);
    }

    public function test_lista_de_despesas_tem_acoes_de_editar_e_excluir(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');
        $expense = Expense::create(['description' => 'Hotel', 'amount' => 600, 'paid_by' => $ana->id, 'group_id' => $group->id]);

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('data-edit-expense="'.$expense->id.'"', false)
            ->assertSee('data-delete-expense="'.$expense->id.'"', false)
            ->assertSee('update_url', false)
            ->assertSee('delete_url', false)
            ->assertSee('id="excluir-despesa-modal"', false);
    }

    public function test_descricao_com_script_nao_quebra_a_pagina(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');
        Expense::create(['description' => '</script><script>alert(1)</script>', 'amount' => 10, 'paid_by' => $ana->id, 'group_id' => $group->id]);

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_edita_a_despesa_e_recalcula_a_divisao(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', ['description' => 'Jantar', 'amount' => '100', 'paid_by' => $ana->id]);
        $expense = Expense::where('description', 'Jantar')->firstOrFail();

        $this->actingAs($ana)->put('/expenses/'.$expense->id, [
            'description' => 'Jantar japonês',
            'amount' => '200',
            'paid_by' => $bruno->id,
            'split_type' => 'exact',
            'splits' => [
                ['user_id' => $ana->id, 'amount' => '50'],
                ['user_id' => $bruno->id, 'amount' => '150'],
            ],
        ])->assertRedirect(route('web.groups.show', $group))
            ->assertSessionHas('status', 'Despesa atualizada.');

        $expense->refresh();

        $this->assertSame('Jantar japonês', $expense->description);
        $this->assertSame($bruno->id, $expense->paid_by);
        $this->assertSame('exact', $expense->split_type);
        $this->assertSame(2, $expense->splits()->count());
        $this->assertDatabaseHas('expense_splits', ['expense_id' => $expense->id, 'user_id' => $bruno->id, 'amount' => '150.00']);
    }

    public function test_edicao_invalida_reabre_o_modal_em_modo_de_edicao(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');
        $expense = Expense::create(['description' => 'Hotel', 'amount' => 100, 'paid_by' => $ana->id, 'group_id' => $group->id]);

        $payload = ['description' => 'ab', 'amount' => '100', 'paid_by' => $ana->id, 'expense_id' => $expense->id, '_method' => 'PUT'];

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->put('/expenses/'.$expense->id, $payload)
            ->assertSessionHasErrors('description');

        $this->assertSame('Hotel', $expense->fresh()->description);

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->followingRedirects()
            ->put('/expenses/'.$expense->id, $payload)
            ->assertSee('A descrição deve ter pelo menos 3 caracteres.')
            ->assertSee('action="'.route('web.expenses.update', $expense).'"', false)
            ->assertSee('name="_method" value="PUT" data-method', false)
            ->assertSee('Salvar alterações')
            ->assertSee("openDialog('nova-despesa-modal');", false);
    }

    public function test_exclui_a_despesa_e_as_parcelas(): void
    {
        $ana = User::factory()->create();
        $bruno = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        $this->actingAs($ana)->post('/groups/'.$group->id.'/expenses', ['description' => 'Jantar', 'amount' => '100', 'paid_by' => $ana->id]);
        $expense = Expense::where('description', 'Jantar')->firstOrFail();

        $this->actingAs($bruno)->delete('/expenses/'.$expense->id)
            ->assertRedirect(route('web.groups.show', $group))
            ->assertSessionHas('status', 'Despesa excluída.');

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_splits', 0);
    }

    public function test_quem_nao_participa_nao_edita_nem_exclui_despesa(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');
        $expense = Expense::create(['description' => 'Hotel', 'amount' => 100, 'paid_by' => $ana->id, 'group_id' => $group->id]);
        $estranho = User::factory()->create();

        $this->actingAs($estranho)
            ->put('/expenses/'.$expense->id, ['description' => 'Invadido', 'amount' => '1', 'paid_by' => $ana->id])
            ->assertForbidden();

        $this->actingAs($estranho)->delete('/expenses/'.$expense->id)->assertForbidden();

        $this->assertSame('Hotel', $expense->fresh()->description);
    }

    public function test_visitante_nao_edita_nem_exclui_despesa(): void
    {
        $this->put('/expenses/1', [])->assertRedirect('/');
        $this->delete('/expenses/1')->assertRedirect('/');
    }

    public function test_historico_lista_os_pagamentos_entre_pessoas(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Teste']);
        $bruno = User::factory()->create(['name' => 'Bruno Teste']);
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        Settlement::create(['group_id' => $group->id, 'paid_by' => $bruno->id, 'paid_to' => $ana->id, 'amount' => '80.00', 'settled_at' => now()]);

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('id="aba-historico"', false)
            ->assertSee('Pagamentos entre pessoas')
            ->assertSee('Bruno Teste &rarr; Ana Teste', false)
            ->assertSee('R$ 80,00')
            ->assertSee(now()->format('d/m/Y'))
            ->assertDontSee('Nenhum pagamento registrado ainda.');
    }

    public function test_historico_vazio_orienta_a_usar_o_quitar(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('Nenhum pagamento registrado ainda.');
    }

    public function test_painel_da_direita_mostra_os_ultimos_pagamentos_e_o_link_do_historico_completo(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Teste']);
        $bruno = User::factory()->create(['name' => 'Bruno Teste']);
        $group = $this->makeGroup($ana, 'Viagem da turma', [$bruno]);

        foreach (range(1, 6) as $dia) {
            Settlement::create([
                'group_id' => $group->id,
                'paid_by' => $bruno->id,
                'paid_to' => $ana->id,
                'amount' => $dia * 10,
                'settled_at' => now()->subDays(10 - $dia),
            ]);
        }

        $html = $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('Histórico de pagamentos')
            ->assertSee('Ver histórico completo')
            ->assertSee('data-go-tab="historico"', false)
            ->getContent();

        $this->assertSame(2, substr_count($html, 'Histórico de pagamentos'));
        $this->assertSame(3, substr_count($html, 'R$ 60,00'));
        $this->assertSame(1, substr_count($html, 'R$ 10,00'));
    }

    public function test_painel_da_direita_sem_excesso_nao_mostra_o_link_do_historico_completo(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->get('/groups/'.$group->id)
            ->assertSee('Histórico de pagamentos')
            ->assertDontSee('Ver histórico completo');
    }

    public function test_convite_sem_email_mostra_apenas_a_mensagem_em_portugues(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->post('/groups/'.$group->id.'/members', ['email' => ''])
            ->assertSessionHasErrors(['email' => 'Informe um e-mail válido.'])
            ->assertSessionDoesntHaveErrors('user_id');

        $this->actingAs($ana)->from('/groups/'.$group->id)
            ->followingRedirects()
            ->post('/groups/'.$group->id.'/members', ['email' => ''])
            ->assertSee('Informe um e-mail válido.')
            ->assertDontSee('ID do usuário')
            ->assertDontSee('user id')
            ->assertSee("openDialog('membros-modal');", false);

        $this->assertSame(1, $group->members()->count());
    }

    public function test_convite_com_email_invalido_mostra_a_mesma_mensagem(): void
    {
        $ana = User::factory()->create();
        $group = $this->makeGroup($ana, 'Viagem da turma');

        $this->actingAs($ana)
            ->post('/groups/'.$group->id.'/members', ['email' => 'isso-nao-e-email'])
            ->assertSessionHasErrors(['email' => 'Informe um e-mail válido.']);
    }

}

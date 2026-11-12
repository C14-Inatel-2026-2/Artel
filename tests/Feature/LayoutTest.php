<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    public function test_pagina_que_usa_o_layout_renderiza_estrutura_base(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('<html lang="pt-BR">', false);
        $response->assertSee('<title>Entrar - Artel</title>', false);
        $response->assertDontSee('<footer', false);
        $response->assertDontSee('<header', false);
        $response->assertDontSee('<nav', false);
    }

    public function test_mensagem_de_status_aparece_como_notificacao_flutuante(): void
    {
        $response = $this->withSession(['status' => 'Conta criada.'])->get('/login');

        $response->assertSee('Conta criada.');
        $response->assertSee('role="status"', false);
        $response->assertSee('data-timeout="5000"', false);
    }

    public function test_notificacao_fica_no_canto_superior_direito_com_titulo_e_barra_de_tempo(): void
    {
        $sucesso = $this->withSession(['status' => 'Conta criada.'])->get('/login');

        $sucesso->assertSee('fixed right-0 top-0', false);
        $sucesso->assertSee('Tudo certo');
        $sucesso->assertSee('animate-toast-in', false);
        $sucesso->assertSee('animate-toast-progress', false);
        $sucesso->assertSee('animation-duration: 5000ms', false);
        $sucesso->assertSee('aria-label="Fechar notificação"', false);

        $this->withViewErrors(['email' => 'Erro de teste.']);

        $erro = Blade::render('<x-toasts />');

        $this->assertStringContainsString('Algo deu errado', $erro);
        $this->assertStringContainsString('role="alert"', $erro);
        $this->assertStringContainsString('animation-duration: 8000ms', $erro);
        $this->assertStringContainsString('Erro de teste.', $erro);
    }

    public function test_sem_mensagens_nenhuma_notificacao_e_renderizada(): void
    {
        $this->get('/login')->assertDontSee('data-toast', false);
    }

    public function test_componente_button_tem_ponteiro_e_transicao_suave_de_cor(): void
    {
        $html = Blade::render('<x-button type="submit" data-teste="sim">Salvar</x-button>');

        $this->assertStringContainsString('<button type="submit"', $html);
        $this->assertStringContainsString('data-teste="sim"', $html);
        $this->assertStringContainsString('Salvar', $html);
        $this->assertStringContainsString('cursor-pointer', $html);
        $this->assertStringContainsString('transition-colors', $html);
        $this->assertStringContainsString('duration-300', $html);
        $this->assertStringContainsString('hover:bg-indigo-800', $html);
    }

    public function test_componente_button_aceita_variante_secundaria_e_link(): void
    {
        $secundario = Blade::render('<x-button variant="secondary">Cancelar</x-button>');
        $link = Blade::render('<x-button href="/groups">Ir</x-button>');

        $this->assertStringContainsString('hover:bg-slate-100', $secundario);
        $this->assertStringNotContainsString('bg-indigo-600', $secundario);
        $this->assertStringContainsString('<a href="/groups"', $link);
        $this->assertStringContainsString('cursor-pointer', $link);
    }

    public function test_componente_button_aceita_tamanho_pequeno_e_variante_de_exclusao(): void
    {
        $pequeno = Blade::render('<x-button size="sm">Editar</x-button>');
        $perigo = Blade::render('<x-button variant="danger">Excluir</x-button>');

        $this->assertStringContainsString('min-h-9', $pequeno);
        $this->assertStringNotContainsString('min-h-11', $pequeno);
        $this->assertStringContainsString('bg-red-600', $perigo);
        $this->assertStringContainsString('hover:bg-red-800', $perigo);
        $this->assertStringContainsString('min-h-11', $perigo);
    }

    public function test_layout_disponibiliza_areas_reutilizaveis(): void
    {
        $this->withViewErrors([]);

        $html = Blade::render(<<<'BLADE'
            @extends('layouts.app')
            @section('title', 'Meus Grupos')
            @section('content') <p>conteudo-teste</p> @endsection
            @push('scripts') <script>script-teste</script> @endpush
        BLADE);

        $this->assertStringContainsString('<title>Meus Grupos</title>', $html);
        $this->assertStringContainsString('conteudo-teste', $html);
        $this->assertStringContainsString('script-teste', $html);
    }
}

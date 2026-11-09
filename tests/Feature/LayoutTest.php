<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    public function test_pagina_que_usa_o_layout_renderiza_estrutura_base(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<html lang="pt-BR">', false);
        $response->assertSee('<title>Artel</title>', false);
        $response->assertSee('Aplicação de divisão de despesas em grupo.');
    }

    public function test_layout_disponibiliza_areas_reutilizaveis(): void
    {
        $this->withViewErrors([]);

        $html = Blade::render(<<<'BLADE'
            @extends('layouts.app')
            @section('title', 'Meus Grupos')
            @section('nav') <span>menu-teste</span> @endsection
            @section('content') <p>conteudo-teste</p> @endsection
            @push('scripts') <script>script-teste</script> @endpush
        BLADE);

        $this->assertStringContainsString('<title>Meus Grupos</title>', $html);
        $this->assertStringContainsString('menu-teste', $html);
        $this->assertStringContainsString('conteudo-teste', $html);
        $this->assertStringContainsString('script-teste', $html);
    }
}

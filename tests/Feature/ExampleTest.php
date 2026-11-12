<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_a_raiz_nao_tem_pagina_e_manda_o_visitante_para_o_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}

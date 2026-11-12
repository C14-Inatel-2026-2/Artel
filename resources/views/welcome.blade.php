@extends('layouts.app')

@section('title', 'Artel')

@section('content')
    <section class="py-12 text-center">
        <h1 class="text-3xl font-bold text-slate-900">Artel</h1>
        <p class="mt-3 text-slate-600">Aplicação de divisão de despesas em grupo.</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            @auth
                <x-button href="{{ route('web.groups.index') }}">Ir para meus grupos</x-button>
            @else
                <x-button href="{{ route('login') }}">Entrar</x-button>
                <x-button href="{{ route('register') }}" variant="secondary">Criar conta</x-button>
            @endauth
        </div>
    </section>
@endsection

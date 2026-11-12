@extends('layouts.app')

@section('title', 'Criar conta - Artel')

@section('nav')
    <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Entrar</a>
@endsection

@section('content')
    <x-auth.shell>
        <h1 class="text-2xl font-bold text-slate-900">Criar conta</h1>
        <p class="mt-1 text-lg font-medium text-indigo-900">Comece a dividir as contas do seu grupo.</p>

        <form method="POST" action="{{ url('/user/register') }}" class="mt-8 space-y-5" novalidate>
            @csrf

            <x-form.field name="name" label="Nome" autocomplete="name" autofocus />
            <x-form.field name="email" label="E-mail" type="email" autocomplete="email" />
            <x-form.field
                name="password"
                label="Senha"
                type="password"
                autocomplete="new-password"
                hint="Use pelo menos 8 caracteres."
            />
            <x-form.field
                name="password_confirmation"
                label="Confirmar senha"
                type="password"
                autocomplete="new-password"
            />

            <x-button type="submit" class="w-full">Criar conta</x-button>
        </form>

        <p class="mt-6 text-sm text-slate-600">
            Já tem conta?
            <a href="{{ route('login') }}" class="font-medium text-indigo-700 hover:underline">Entrar</a>
        </p>
    </x-auth.shell>
@endsection

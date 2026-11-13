@extends('layouts.app')

@section('title', 'Entrar - Artel')

@section('content')
    <x-auth.shell>
        <h1 class="text-2xl font-bold text-slate-900">Entrar</h1>
        <p class="mt-1 text-lg font-medium text-indigo-900">Cada um paga a sua parte.</p>

        <form method="POST" action="{{ url('/user/login') }}" class="mt-8 space-y-5" novalidate>
            @csrf

            <x-form.field name="email" label="E-mail" type="email" autocomplete="email" autofocus />
            <x-form.field name="password" label="Senha" type="password" autocomplete="current-password" />

            <x-button type="submit" class="w-full">Entrar</x-button>
        </form>

        <p class="mt-6 text-sm text-slate-600">
            Ainda não tem conta?
            <a href="{{ route('register') }}" class="font-medium text-indigo-700 hover:underline">Cadastre-se</a>
        </p>
    </x-auth.shell>
@endsection

@extends('layouts.app')

@section('title', 'Entrar - Artel')

@section('nav')
    <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Criar conta</a>
@endsection

@section('content')
    <x-auth.shell>
        <h1 class="text-2xl font-bold text-slate-900">Entrar</h1>
        <p class="mt-1 text-lg font-medium text-indigo-900">Cada um paga a sua parte.</p>

        <form method="POST" action="{{ url('/user/login') }}" class="mt-8 space-y-5" novalidate>
            @csrf

            <x-auth.field name="email" label="E-mail" type="email" autocomplete="email" autofocus />
            <x-auth.field name="password" label="Senha" type="password" autocomplete="current-password" />

            <button
                type="submit"
                class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2.5 text-base font-semibold text-white transition-colors hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
            >
                Entrar
            </button>
        </form>

        <p class="mt-6 text-sm text-slate-600">
            Ainda não tem conta?
            <a href="{{ route('register') }}" class="font-medium text-indigo-700 hover:underline">Cadastre-se</a>
        </p>
    </x-auth.shell>
@endsection

@extends('layouts.app')

@section('title', $group->name.' - Artel')

@section('nav')
    <a href="{{ route('web.groups.index') }}" class="text-indigo-600 hover:underline">Meus grupos</a>
@endsection

@section('content')
    <div class="mb-8">
        <a href="{{ route('web.groups.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">
            <span aria-hidden="true">&lsaquo;</span> Voltar para meus grupos
        </a>

        <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $group->name }}</h1>
        <p class="mt-1 text-slate-600">Criado por {{ $group->owner->name }}.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <section aria-labelledby="participantes" class="rounded-xl bg-white p-6 lg:col-span-2">
            <h2 id="participantes" class="text-lg font-semibold text-slate-900">Participantes</h2>

            <ul class="mt-4 space-y-3">
                @foreach ($group->members as $member)
                    <li class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $member->name }}</p>
                            <p class="truncate text-sm text-slate-600">{{ $member->email }}</p>
                        </div>

                        @if ($member->id === $group->user_id)
                            <span class="shrink-0 text-sm font-medium text-indigo-700">Dono do grupo</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="resumo" class="rounded-xl bg-indigo-50 p-6 lg:self-start">
            <h2 id="resumo" class="text-lg font-semibold text-slate-900">Resumo</h2>

            <dl class="mt-4 space-y-3">
                <div class="flex items-baseline justify-between">
                    <dt class="text-slate-600">Participantes</dt>
                    <dd class="text-xl font-semibold text-slate-900">{{ $group->members->count() }}</dd>
                </div>
                <div class="flex items-baseline justify-between">
                    <dt class="text-slate-600">Despesas</dt>
                    <dd class="text-xl font-semibold text-slate-900">{{ $group->expenses_count }}</dd>
                </div>
            </dl>
        </section>
    </div>
@endsection

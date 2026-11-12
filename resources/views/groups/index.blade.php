@extends('layouts.app')

@section('title', 'Meus grupos - Artel')

@php
    $colors = ['bg-indigo-600', 'bg-teal-600', 'bg-amber-500', 'bg-orange-600', 'bg-pink-600', 'bg-sky-600'];
@endphp

@section('content')
    <div class="flex items-center gap-3">
        <span class="flex size-12 shrink-0 items-center justify-center rounded-full text-lg font-semibold text-white {{ $colors[auth()->id() % count($colors)] }}" aria-hidden="true">
            {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
        </span>

        <p class="min-w-0 truncate text-lg font-semibold text-slate-900">{{ auth()->user()->name }}</p>
    </div>

    <div class="mt-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 id="meus-grupos" class="text-2xl font-bold text-slate-900">Meus grupos</h1>
            <p class="mt-1 text-slate-600">Escolha um grupo para ver os detalhes.</p>
        </div>

        <x-button data-open-new-group>Criar grupo</x-button>
    </div>

    <section aria-labelledby="meus-grupos" class="mt-6">
        @if ($groups->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-8 text-center">
                <p class="font-semibold text-slate-900">Você ainda não participa de nenhum grupo.</p>
                <p class="mt-1 text-slate-600">Use o botão Criar grupo para criar o primeiro e convidar as pessoas.</p>
            </div>
        @else
            <ul class="space-y-3">
                @foreach ($groups as $group)
                    <li>
                        <a
                            href="{{ route('web.groups.show', $group) }}"
                            class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4 transition-colors hover:border-indigo-300 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        >
                            <span class="size-3 shrink-0 rounded-full {{ $colors[$group->id % count($colors)] }}" aria-hidden="true"></span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-base font-semibold text-slate-900">{{ $group->name }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">
                                    {{ $group->user_id === auth()->id() ? 'Você criou este grupo' : 'Criado por '.$group->owner->name }}
                                </p>
                                <p class="text-sm text-slate-600">
                                    {{ $group->members_count }} {{ $group->members_count === 1 ? 'participante' : 'participantes' }}
                                    &middot;
                                    {{ $group->expenses_count }} {{ $group->expenses_count === 1 ? 'despesa' : 'despesas' }}
                                </p>
                            </div>

                            <div class="hidden shrink-0 items-center sm:flex" aria-hidden="true">
                                @foreach ($group->members->take(4) as $member)
                                    <span class="flex size-7 items-center justify-center rounded-full text-xs font-semibold text-white ring-2 ring-white {{ $colors[$member->id % count($colors)] }} {{ $loop->first ? '' : '-ml-1.5' }}">
                                        {{ str($member->name)->substr(0, 1)->upper() }}
                                    </span>
                                @endforeach

                                @if ($group->members_count > 4)
                                    <span class="-ml-1.5 flex size-7 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700 ring-2 ring-white">
                                        +{{ $group->members_count - 4 }}
                                    </span>
                                @endif
                            </div>

                            <span class="shrink-0 text-xl text-indigo-700" aria-hidden="true">&rsaquo;</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <dialog
        id="novo-grupo-modal"
        aria-labelledby="novo-grupo-titulo"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl p-0 text-slate-800 shadow-xl backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm"
    >
        <div class="relative p-6">
            <button
                type="button"
                data-close-new-group
                aria-label="Fechar"
                class="absolute right-3 top-3 flex size-9 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
            >
                <span class="text-2xl leading-none" aria-hidden="true">&times;</span>
            </button>

            <h2 id="novo-grupo-titulo" class="pr-10 text-lg font-semibold text-slate-900">Novo grupo</h2>

            <form method="POST" action="{{ route('web.groups.store') }}" class="mt-5 space-y-5" novalidate>
                @csrf

                <x-form.field name="name" label="Nome do grupo" autocomplete="off" hint="Use pelo menos 3 caracteres." autofocus />

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" data-close-new-group>Cancelar</x-button>

                    <x-button type="submit">Criar grupo</x-button>
                </div>
            </form>
        </div>
    </dialog>

    @push('scripts')
        <script>
            var dialog = document.getElementById('novo-grupo-modal');

            function openModal() {
                dialog.showModal();
            }

            function closeModal() {
                dialog.close();
            }

            document.querySelectorAll('[data-open-new-group]').forEach(function (button) {
                button.addEventListener('click', openModal);
            });

            document.querySelectorAll('[data-close-new-group]').forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) {
                    closeModal();
                }
            });

            @if ($errors->has('name'))
                openModal();
            @endif
        </script>
    @endpush
@endsection

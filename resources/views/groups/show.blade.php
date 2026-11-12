@extends('layouts.app')

@section('title', $group->name.' - Artel')

@section('nav')
    <span class="text-slate-600">{{ auth()->user()->name }}</span>
@endsection

@php
    $colors = ['bg-indigo-600', 'bg-teal-600', 'bg-amber-500', 'bg-orange-600', 'bg-pink-600', 'bg-sky-600'];
    $money = fn (float $value) => 'R$ '.number_format(abs($value), 2, ',', '.');
    $signed = fn (float $value) => ($value > 0 ? '+ ' : ($value < 0 ? '- ' : '')).'R$ '.number_format(abs($value), 2, ',', '.');
    $tone = fn (float $value) => $value > 0 ? 'text-emerald-700' : ($value < 0 ? 'text-red-600' : 'text-slate-700');
    $membersCount = $group->members->count();
    $hasExpenseErrors = $errors->hasAny(['description', 'amount', 'paid_by', 'split_type', 'participants', 'participants.*', 'splits', 'splits.*']);
    $hasMemberErrors = $errors->hasAny(['email', 'user_id']);
    $splitError = collect(['split_type', 'splits', 'splits.*', 'participants', 'participants.*'])
        ->map(fn ($key) => $errors->first($key))
        ->first(fn ($message) => $message !== '');
    $oldSplitType = old('split_type', 'equal');
    $editingId = (int) old('expense_id');
    $isEditing = $editingId > 0;
    $amountFilled = (float) old('amount') > 0;
    $oldSplits = collect(old('splits', []))->keyBy('user_id');
    $included = old('split_type') === null
        ? $group->members->pluck('id')->all()
        : (old('split_type') === 'equal'
            ? array_map('intval', (array) old('participants', []))
            : $oldSplits->keys()->map(fn ($id) => (int) $id)->all());
    $inputClass = 'min-h-9 w-24 rounded-md border border-slate-300 px-2 py-1 text-right text-base focus:outline-none focus:ring-2 focus:ring-indigo-500';
@endphp

@section('content')
    <a href="{{ route('web.groups.index') }}" class="text-sm font-medium text-indigo-700 hover:underline">
        <span aria-hidden="true">&lsaquo;</span> Meus grupos
    </a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="flex items-center gap-3 text-2xl font-bold text-slate-900">
                <span class="size-3 shrink-0 rounded-full {{ $colors[$group->id % count($colors)] }}" aria-hidden="true"></span>
                <span class="truncate">{{ $group->name }}</span>
            </h1>
            <p class="mt-1 text-slate-600">
                {{ $group->user_id === auth()->id() ? 'Você criou este grupo' : 'Criado por '.$group->owner->name }}
            </p>
        </div>

        <button
            type="button"
            data-open-modal="membros-modal"
            aria-label="Ver os {{ $membersCount }} {{ $membersCount === 1 ? 'participante' : 'participantes' }}"
            class="inline-flex cursor-pointer items-center rounded-full border border-slate-300 bg-white py-1 pl-2 pr-3 transition-colors duration-300 ease-in-out hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
        >
            @foreach ($group->members->take(4) as $member)
                <span class="flex size-7 items-center justify-center rounded-full text-xs font-semibold text-white ring-2 ring-white {{ $colors[$member->id % count($colors)] }} {{ $loop->first ? '' : '-ml-2' }}" aria-hidden="true">
                    {{ str($member->name)->substr(0, 1)->upper() }}
                </span>
            @endforeach

            @if ($membersCount > 4)
                <span class="-ml-2 flex size-7 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700 ring-2 ring-white" aria-hidden="true">
                    +{{ $membersCount - 4 }}
                </span>
            @endif

            <span class="ml-2 text-lg leading-none text-indigo-700" aria-hidden="true">&rsaquo;</span>
        </button>
    </div>

    <dl class="mt-6 grid grid-cols-2 gap-4 rounded-xl border border-indigo-200 bg-indigo-50 p-5">
        <div>
            <dt class="text-sm text-slate-600">Total gasto</dt>
            <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $money($balance['total_expenses']) }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-600">Seu saldo</dt>
            <dd class="mt-1 text-2xl font-bold {{ $tone($myBalance) }}">{{ $signed($myBalance) }}</dd>
        </div>
    </dl>

    <div class="mt-6 flex flex-wrap items-end justify-between gap-x-4 border-b border-slate-200">
        <div role="tablist" aria-label="Seções do grupo" class="flex gap-5">
            <button type="button" role="tab" id="aba-despesas" data-tab="despesas" aria-controls="painel-despesas" aria-selected="true" class="-mb-px cursor-pointer border-b-2 border-indigo-600 pb-3 text-base font-semibold text-indigo-700 transition-colors duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Despesas</button>
            <button type="button" role="tab" id="aba-saldos" data-tab="saldos" aria-controls="painel-saldos" aria-selected="false" class="-mb-px cursor-pointer border-b-2 border-transparent pb-3 text-base font-semibold text-slate-600 transition-colors duration-300 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Saldos</button>
            <button type="button" role="tab" id="aba-historico" data-tab="historico" aria-controls="painel-historico" aria-selected="false" class="-mb-px cursor-pointer border-b-2 border-transparent pb-3 text-base font-semibold text-slate-600 transition-colors duration-300 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Histórico</button>
        </div>

        <x-button data-open-modal="nova-despesa-modal" data-new-expense class="mb-2 ml-auto">Nova despesa</x-button>
    </div>

    <div id="painel-despesas" role="tabpanel" aria-labelledby="aba-despesas" class="mt-6 grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:items-start">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-base font-semibold text-slate-900">Despesas</h2>

            @forelse ($expenses as $expense)
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-slate-100 py-3 first:border-t-0 sm:flex-nowrap">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white {{ $colors[$expense->paid_by % count($colors)] }}" aria-hidden="true">
                        {{ str($expense->payer->name)->substr(0, 1)->upper() }}
                    </span>

                    <div class="min-w-0 flex-1 basis-40">
                        <div class="flex items-baseline gap-2">
                            <p class="truncate font-medium text-slate-900">{{ $expense->description }}</p>
                            <p class="shrink-0 font-semibold text-indigo-700">{{ $money((float) $expense->amount) }}</p>
                        </div>
                        <p class="truncate text-sm text-slate-600">Pago por {{ $expense->payer->name }} &middot; {{ $expense->created_at->format('d/m/Y') }}</p>
                    </div>

                    <div class="flex shrink-0 gap-2 max-sm:w-full max-sm:pl-11">
                        <x-button size="sm" variant="secondary" data-edit-expense="{{ $expense->id }}" aria-label="Editar {{ $expense->description }}">Editar</x-button>
                        <x-button size="sm" variant="secondary" data-delete-expense="{{ $expense->id }}" data-name="{{ $expense->description }}" aria-label="Excluir {{ $expense->description }}">Excluir</x-button>
                    </div>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-600">Nenhuma despesa registrada ainda. Use o botão Nova despesa para começar.</p>
            @endforelse
        </section>

        <div class="space-y-6">
            @include('groups.partials.transfers')
            @include('groups.partials.history')
        </div>
    </div>

    <div id="painel-saldos" role="tabpanel" aria-labelledby="aba-saldos" hidden class="mt-6 grid grid-cols-[minmax(0,1fr)] gap-6 md:grid-cols-2 md:items-start">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-base font-semibold text-slate-900">Saldo de cada pessoa</h2>

            @foreach ($balance['balances'] as $row)
                <div class="flex items-center gap-3 border-t border-slate-100 py-3 first:border-t-0">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white {{ $colors[$row['user']['id'] % count($colors)] }}" aria-hidden="true">
                        {{ str($row['user']['name'])->substr(0, 1)->upper() }}
                    </span>

                    <p class="min-w-0 flex-1 truncate font-medium text-slate-900">
                        {{ $row['user']['name'] }}{{ $row['user']['id'] === auth()->id() ? ' (você)' : '' }}
                    </p>

                    <p class="shrink-0 font-semibold {{ $tone($row['net_balance']) }}">{{ $signed($row['net_balance']) }}</p>
                </div>
            @endforeach
        </section>

        <div class="space-y-6">
            @include('groups.partials.transfers')
            @include('groups.partials.history')
        </div>
    </div>

    <div id="painel-historico" role="tabpanel" aria-labelledby="aba-historico" hidden class="mt-6">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-base font-semibold text-slate-900">Pagamentos entre pessoas</h2>

            @forelse ($settlements as $settlement)
                <div class="flex items-center gap-3 border-t border-slate-100 py-3 first:border-t-0">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white {{ $colors[$settlement->paid_by % count($colors)] }}" aria-hidden="true">
                        {{ str($settlement->payer->name)->substr(0, 1)->upper() }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-900">{{ $settlement->payer->name }} &rarr; {{ $settlement->payee->name }}</p>
                        <p class="truncate text-sm text-slate-600">{{ $settlement->settled_at->format('d/m/Y') }}</p>
                    </div>

                    <p class="shrink-0 font-semibold text-slate-900">{{ $money((float) $settlement->amount) }}</p>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-600">Nenhum pagamento registrado ainda. Eles aparecem aqui quando alguém usa o botão Quitar.</p>
            @endforelse
        </section>
    </div>

    <x-modal id="membros-modal" title="Participantes" size="lg">
        <p class="mt-1 text-sm text-slate-600">{{ $membersCount }} {{ $membersCount === 1 ? 'pessoa' : 'pessoas' }} no grupo</p>

        <ul class="mt-4 max-h-80 divide-y divide-slate-100 overflow-y-auto">
            @foreach ($group->members as $member)
                <li class="flex items-center gap-3 py-3">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white {{ $colors[$member->id % count($colors)] }}" aria-hidden="true">
                        {{ str($member->name)->substr(0, 1)->upper() }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-900">{{ $member->name }}</p>
                        <p class="truncate text-sm text-slate-600">{{ $member->email }}</p>
                    </div>

                    @if ($member->id === $group->user_id)
                        <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">Dono do grupo</span>
                    @endif
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('web.groups.members.store', $group) }}" class="mt-4 border-t border-slate-100 pt-4" novalidate>
            @csrf

            <div class="flex items-start gap-3">
                <div class="min-w-0 flex-1">
                    <x-form.field name="email" label="Convidar por e-mail" type="email" autocomplete="off" hint="A pessoa precisa já ter uma conta no Artel." />
                </div>

                <x-button type="submit" class="mt-[1.625rem] shrink-0">Adicionar</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal id="nova-despesa-modal" :title="$isEditing ? 'Editar despesa' : 'Nova despesa'" size="lg">
        <form id="despesa-form" method="POST" action="{{ $isEditing ? route('web.expenses.update', $editingId) : route('web.groups.expenses.store', $group) }}" class="mt-5 space-y-5" novalidate>
            @csrf
            <input type="hidden" name="_method" value="PUT" data-method @disabled(! $isEditing)>
            <input type="hidden" name="expense_id" value="{{ $isEditing ? $editingId : '' }}" data-expense-id @disabled(! $isEditing)>

            <x-form.field name="description" label="Descrição" autocomplete="off" hint="Use pelo menos 3 caracteres." autofocus />

            <x-form.field name="amount" label="Valor (R$)" type="number" step="0.01" min="0.01" inputmode="decimal" autocomplete="off" />

            <div>
                <label for="paid_by" class="block text-sm font-medium text-slate-800">Quem pagou</label>
                <select
                    id="paid_by"
                    name="paid_by"
                    class="mt-1.5 block min-h-11 w-full cursor-pointer rounded-md border bg-white px-3 py-2.5 text-base text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('paid_by') ? 'border-red-400' : 'border-slate-300' }}"
                >
                    @foreach ($group->members as $member)
                        <option value="{{ $member->id }}" @selected((int) old('paid_by', auth()->id()) === $member->id)>
                            {{ $member->name }}{{ $member->id === auth()->id() ? ' (você)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('paid_by')
                    <p id="paid_by-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="split_type" class="block text-sm font-medium text-slate-800">Como dividir</label>
                <select
                    id="split_type"
                    name="split_type"
                    @disabled(! $amountFilled)
                    class="mt-1.5 block min-h-11 w-full cursor-pointer rounded-md border bg-white px-3 py-2.5 text-base text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 {{ $splitError ? 'border-red-400' : 'border-slate-300' }}"
                >
                    <option value="equal" @selected($oldSplitType === 'equal')>Igualmente</option>
                    <option value="exact" @selected($oldSplitType === 'exact')>Por valor exato</option>
                    <option value="percentage" @selected($oldSplitType === 'percentage')>Por percentual</option>
                </select>
                <p data-split-hint class="mt-1.5 text-sm text-slate-600" @if ($amountFilled) hidden @endif>Informe o valor da despesa para escolher como dividir.</p>
                <p data-split-error class="mt-1.5 text-sm font-medium text-red-700" @if (! $splitError) hidden @endif>{{ $splitError }}</p>
            </div>

            <div class="flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="min-w-0">
                    <p data-split-title class="font-medium text-slate-900"></p>
                    <p data-split-summary class="text-sm text-slate-600"></p>
                </div>

                <x-button data-open-modal="divisao-modal" :disabled="! $amountFilled" class="shrink-0">Editar</x-button>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-button variant="secondary" data-close-modal>Cancelar</x-button>

                <x-button type="submit" data-submit-expense>{{ $isEditing ? 'Salvar alterações' : 'Salvar despesa' }}</x-button>
            </div>
        </form>

        <x-modal id="divisao-modal" title="Configurar divisão">
            <p data-split-help class="mt-1 text-sm text-slate-600"></p>

            <ul class="mt-4 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-md border border-slate-200">
                @foreach ($group->members as $member)
                    <li data-split-row data-user-id="{{ $member->id }}" class="flex items-center gap-3 px-3 py-2">
                        <input type="checkbox" data-include id="divide-{{ $member->id }}" @checked(in_array($member->id, $included, true)) class="size-4 shrink-0 cursor-pointer accent-indigo-600">

                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white {{ $colors[$member->id % count($colors)] }}" aria-hidden="true">
                            {{ str($member->name)->substr(0, 1)->upper() }}
                        </span>

                        <label for="divide-{{ $member->id }}" class="min-w-0 flex-1 cursor-pointer truncate text-slate-900">{{ $member->name }}{{ $member->id === auth()->id() ? ' (você)' : '' }}</label>

                        <input type="hidden" form="despesa-form" name="participants[]" value="{{ $member->id }}" data-equal-input>
                        <input type="hidden" form="despesa-form" name="splits[{{ $loop->index }}][user_id]" value="{{ $member->id }}" data-user>
                        <input type="number" form="despesa-form" step="0.01" min="0.01" inputmode="decimal" name="splits[{{ $loop->index }}][amount]" value="{{ data_get($oldSplits->get($member->id), 'amount') }}" aria-label="Valor de {{ $member->name }}" data-exact class="{{ $inputClass }}">
                        <input type="number" form="despesa-form" step="0.01" min="0.01" max="100" inputmode="decimal" name="splits[{{ $loop->index }}][percentage]" value="{{ data_get($oldSplits->get($member->id), 'percentage') }}" aria-label="Percentual de {{ $member->name }}" data-percentage class="{{ $inputClass }}">
                    </li>
                @endforeach
            </ul>

            <p data-split-summary class="mt-2 text-sm text-slate-600"></p>

            <div class="mt-5 flex justify-end">
                <x-button data-close-modal>Concluir</x-button>
            </div>
        </x-modal>
    </x-modal>

    <x-modal id="excluir-despesa-modal" title="Excluir despesa">
        <p class="mt-2 text-slate-600">
            Excluir <span class="font-semibold text-slate-900" data-delete-name></span>? Os saldos do grupo serão recalculados e essa ação não pode ser desfeita.
        </p>

        <form id="excluir-despesa-form" method="POST" action="" class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            @csrf
            @method('DELETE')

            <x-button variant="secondary" data-close-modal>Cancelar</x-button>

            <x-button type="submit" variant="danger">Excluir despesa</x-button>
        </form>
    </x-modal>

    @push('scripts')
        <script>
            function openDialog(id) {
                document.getElementById(id).showModal();
            }

            document.querySelectorAll('[data-open-modal]').forEach(function (button) {
                button.addEventListener('click', function () {
                    openDialog(button.dataset.openModal);
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach(function (button) {
                button.addEventListener('click', function () {
                    button.closest('dialog').close();
                });
            });

            document.querySelectorAll('dialog').forEach(function (dialog) {
                dialog.addEventListener('click', function (event) {
                    if (event.target === dialog) {
                        dialog.close();
                    }
                });
            });

            var expensesData = {{ Illuminate\Support\Js::from($expensesData) }};
            var storeUrl = {{ Illuminate\Support\Js::from(route('web.groups.expenses.store', $group)) }};
            var currentUserId = {{ auth()->id() }};
            var expenseForm = document.getElementById('despesa-form');
            var splitModal = document.getElementById('divisao-modal');
            var splitType = document.getElementById('split_type');
            var amountInput = document.getElementById('amount');
            var splitTitle = expenseForm.querySelector('[data-split-title]');
            var splitHelp = splitModal.querySelector('[data-split-help]');
            var splitSummaries = document.querySelectorAll('[data-split-summary]');
            var splitError = expenseForm.querySelector('[data-split-error]');
            var splitHint = expenseForm.querySelector('[data-split-hint]');
            var editSplitButton = expenseForm.querySelector('[data-open-modal="divisao-modal"]');

            function money(value) {
                return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function people(count) {
                return count + (count === 1 ? ' pessoa' : ' pessoas');
            }

            function syncSplit() {
                var mode = splitType.value;
                var total = parseFloat(amountInput.value) || 0;
                var count = 0;
                var sum = 0;

                splitType.disabled = ! (total > 0);
                editSplitButton.disabled = ! (total > 0);
                splitHint.hidden = total > 0;

                document.querySelectorAll('[data-split-row]').forEach(function (row) {
                    var on = row.querySelector('[data-include]').checked;
                    var exact = row.querySelector('[data-exact]');
                    var percentage = row.querySelector('[data-percentage]');

                    row.querySelector('[data-equal-input]').disabled = ! (on && mode === 'equal');
                    row.querySelector('[data-user]').disabled = ! (on && mode !== 'equal');
                    exact.disabled = ! (on && mode === 'exact');
                    exact.hidden = mode !== 'exact';
                    percentage.disabled = ! (on && mode === 'percentage');
                    percentage.hidden = mode !== 'percentage';

                    if (on) {
                        count++;
                        sum += parseFloat((mode === 'exact' ? exact : percentage).value) || 0;
                    }
                });

                var text;
                var matches = true;

                if (mode === 'equal') {
                    matches = count > 0;
                    text = people(count) + (count > 0 && total > 0 ? ' · ' + money(total / count) + ' cada' : '') + '.';
                    splitTitle.textContent = 'Divisão igual';
                    splitHelp.textContent = 'Marque as pessoas que participam. O valor é dividido igualmente entre elas.';
                } else if (mode === 'exact') {
                    matches = Math.abs(sum - total) < 0.005;
                    text = people(count) + ' · Soma: ' + money(sum) + ' de ' + money(total) + '.';
                    splitTitle.textContent = 'Divisão por valor exato';
                    splitHelp.textContent = 'Informe quanto cada pessoa paga. A soma deve ser igual ao valor da despesa.';
                } else {
                    matches = Math.abs(sum - 100) < 0.005;
                    text = people(count) + ' · Soma: ' + sum.toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + '% de 100%.';
                    splitTitle.textContent = 'Divisão por percentual';
                    splitHelp.textContent = 'Informe o percentual de cada pessoa. A soma deve ser 100%.';
                }

                splitSummaries.forEach(function (element) {
                    element.textContent = text;
                    element.classList.toggle('text-red-600', ! matches);
                    element.classList.toggle('text-slate-600', matches);
                });
            }

            expenseForm.addEventListener('input', syncSplit);
            expenseForm.addEventListener('change', syncSplit);
            splitModal.addEventListener('input', syncSplit);
            splitModal.addEventListener('change', syncSplit);

            splitType.addEventListener('change', function () {
                splitModal.showModal();
            });

            splitModal.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && event.target.type !== 'checkbox' && event.target.tagName === 'INPUT') {
                    event.preventDefault();
                    splitModal.close();
                }
            });

            expenseForm.addEventListener('submit', function (event) {
                syncSplit();

                if (! document.querySelector('[data-split-row] [data-include]:checked')) {
                    event.preventDefault();
                    splitError.textContent = 'Selecione pelo menos um participante.';
                    splitError.hidden = false;
                }
            });

            var methodInput = expenseForm.querySelector('[data-method]');
            var expenseIdInput = expenseForm.querySelector('[data-expense-id]');
            var submitExpense = expenseForm.querySelector('[data-submit-expense]');
            var expenseTitle = document.getElementById('nova-despesa-modal-titulo');

            function clearExpenseErrors() {
                expenseForm.querySelectorAll('[id$="-error"]').forEach(function (element) {
                    element.remove();
                });

                expenseForm.querySelectorAll('input, select').forEach(function (field) {
                    field.removeAttribute('aria-invalid');
                    field.classList.remove('border-red-400');

                    if (field.tagName === 'SELECT' || field.type === 'text' || field.type === 'number') {
                        field.classList.add('border-slate-300');
                    }
                });

                splitError.hidden = true;
            }

            function fillExpenseForm(expense) {
                var editing = expense !== null;

                clearExpenseErrors();

                expenseForm.action = editing ? expense.update_url : storeUrl;
                methodInput.disabled = ! editing;
                expenseIdInput.disabled = ! editing;
                expenseIdInput.value = editing ? expense.id : '';
                expenseTitle.textContent = editing ? 'Editar despesa' : 'Nova despesa';
                submitExpense.textContent = editing ? 'Salvar alterações' : 'Salvar despesa';

                document.getElementById('description').value = editing ? expense.description : '';
                amountInput.value = editing ? expense.amount : '';
                document.getElementById('paid_by').value = editing ? expense.paid_by : currentUserId;
                splitType.value = editing ? expense.split_type : 'equal';

                document.querySelectorAll('[data-split-row]').forEach(function (row) {
                    var userId = parseInt(row.dataset.userId, 10);
                    var split = editing ? expense.splits.find(function (item) {
                        return item.user_id === userId;
                    }) : null;

                    row.querySelector('[data-include]').checked = editing ? !! split : true;
                    row.querySelector('[data-exact]').value = split ? split.amount : '';
                    row.querySelector('[data-percentage]').value = split ? split.percentage : '';
                });

                syncSplit();
            }

            document.querySelectorAll('[data-new-expense]').forEach(function (button) {
                button.addEventListener('click', function () {
                    fillExpenseForm(null);
                });
            });

            document.querySelectorAll('[data-edit-expense]').forEach(function (button) {
                button.addEventListener('click', function () {
                    fillExpenseForm(expensesData[button.dataset.editExpense]);
                    openDialog('nova-despesa-modal');
                });
            });

            document.querySelectorAll('[data-delete-expense]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('excluir-despesa-form').action = expensesData[button.dataset.deleteExpense].delete_url;
                    document.querySelector('[data-delete-name]').textContent = button.dataset.name;
                    openDialog('excluir-despesa-modal');
                });
            });

            syncSplit();

            var tabs = document.querySelectorAll('[data-tab]');

            function selectTab(name) {
                tabs.forEach(function (tab) {
                    var active = tab.dataset.tab === name;

                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    tab.classList.toggle('border-indigo-600', active);
                    tab.classList.toggle('text-indigo-700', active);
                    tab.classList.toggle('border-transparent', ! active);
                    tab.classList.toggle('text-slate-600', ! active);
                    document.getElementById('painel-' + tab.dataset.tab).hidden = ! active;
                });
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    selectTab(tab.dataset.tab);
                });
            });

            document.querySelectorAll('[data-go-tab]').forEach(function (button) {
                button.addEventListener('click', function () {
                    selectTab(button.dataset.goTab);
                });
            });

            if (['#saldos', '#historico'].indexOf(location.hash) !== -1) {
                selectTab(location.hash.slice(1));
            }

            @if ($hasExpenseErrors)
                openDialog('nova-despesa-modal');
            @endif

            @if ($hasMemberErrors)
                openDialog('membros-modal');
            @endif
        </script>
    @endpush
@endsection

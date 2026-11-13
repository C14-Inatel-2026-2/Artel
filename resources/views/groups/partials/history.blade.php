<section class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="flex items-baseline justify-between gap-2">
        <h2 class="min-w-0 text-base font-semibold text-slate-900">Histórico de pagamentos</h2>

        @if ($settlements->count() > 5)
            <button type="button" data-go-tab="historico" class="shrink-0 cursor-pointer whitespace-nowrap text-xs font-medium text-indigo-700 transition-colors duration-300 hover:text-indigo-900 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Ver histórico completo</button>
        @endif
    </div>

    @forelse ($settlements->take(5) as $settlement)
        <div class="flex items-center gap-3 border-t border-slate-100 py-3 first:border-t-0">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white {{ $colors[$settlement->paid_by % count($colors)] }}" aria-hidden="true">
                {{ str($settlement->payer->name)->substr(0, 1)->upper() }}
            </span>

            <div class="min-w-0 flex-1">
                <p class="truncate font-medium text-slate-900">{{ $settlement->payer->name }} &rarr; {{ $settlement->payee->name }}</p>
                <p class="truncate text-sm text-slate-600">{{ $settlement->settled_at->format('d/m/Y') }}</p>
            </div>

            <p class="shrink-0 font-semibold text-indigo-700">{{ $money((float) $settlement->amount) }}</p>
        </div>
    @empty
        <p class="mt-3 text-sm text-slate-600">Nenhum pagamento registrado ainda.</p>
    @endforelse
</section>

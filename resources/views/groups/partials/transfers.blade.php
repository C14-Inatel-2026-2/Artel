<section class="rounded-xl border border-slate-200 bg-white p-5">
    <h2 class="text-base font-semibold text-slate-900">Quem paga quem</h2>

    @forelse ($balance['suggested_transfers'] as $transfer)
        <form
            method="POST"
            action="{{ route('web.groups.settlements.store', $group) }}"
            class="flex items-center justify-between gap-3 border-t border-slate-100 py-3 first:border-t-0"
        >
            @csrf
            <input type="hidden" name="payer_id" value="{{ $transfer['from']['id'] }}">
            <input type="hidden" name="payee_id" value="{{ $transfer['to']['id'] }}">
            <input type="hidden" name="amount" value="{{ number_format($transfer['amount'], 2, '.', '') }}">

            <div class="min-w-0">
                <p class="truncate font-medium text-slate-900">{{ $transfer['from']['name'] }} &rarr; {{ $transfer['to']['name'] }}</p>
                <p class="text-sm text-slate-600">R$ {{ number_format($transfer['amount'], 2, ',', '.') }}</p>
            </div>

            <x-button type="submit" variant="secondary">Quitar</x-button>
        </form>
    @empty
        <p class="mt-3 text-sm text-slate-600">Tudo certo: ninguém precisa pagar ninguém.</p>
    @endforelse
</section>

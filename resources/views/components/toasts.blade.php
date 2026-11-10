@php
    $toasts = [];

    if (session('status')) {
        $toasts[] = ['type' => 'success', 'messages' => [session('status')]];
    }

    if ($errors->any()) {
        $toasts[] = ['type' => 'error', 'messages' => $errors->all()];
    }
@endphp

@if ($toasts)
    <div class="pointer-events-none fixed inset-x-0 top-20 z-50 flex flex-col items-center gap-3 px-4 sm:items-end sm:px-6">
        @foreach ($toasts as $toast)
            @php($isError = $toast['type'] === 'error')

            <div
                data-toast
                data-timeout="{{ $isError ? 8000 : 5000 }}"
                role="{{ $isError ? 'alert' : 'status' }}"
                @class([
                    'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg transition-opacity duration-200',
                    'bg-red-700' => $isError,
                    'bg-green-700' => ! $isError,
                ])
            >
                <div class="flex-1">
                    @if (count($toast['messages']) > 1)
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($toast['messages'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p>{{ $toast['messages'][0] }}</p>
                    @endif
                </div>

                <button
                    type="button"
                    hidden
                    data-toast-close
                    aria-label="Fechar notificação"
                    class="-my-1 -mr-2 rounded px-2 text-xl leading-none hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white"
                >&times;</button>
            </div>
        @endforeach
    </div>

    @once
        @push('scripts')
            <script>
                document.querySelectorAll('[data-toast]').forEach(function (toast) {
                    var close = toast.querySelector('[data-toast-close]');

                    var dismiss = function () {
                        toast.classList.add('opacity-0');
                        setTimeout(function () { toast.remove(); }, 200);
                    };

                    close.hidden = false;
                    close.addEventListener('click', dismiss);

                    if (toast.dataset.timeout) {
                        setTimeout(dismiss, Number(toast.dataset.timeout));
                    }
                });
            </script>
        @endpush
    @endonce
@endif

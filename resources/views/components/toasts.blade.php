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
    <div class="pointer-events-none fixed right-0 top-0 z-50 flex w-full max-w-sm flex-col gap-3 p-4 sm:p-6">
        @foreach ($toasts as $toast)
            @php
                $isError = $toast['type'] === 'error';
                $timeout = $isError ? 8000 : 5000;
            @endphp

            <div
                data-toast
                data-timeout="{{ $timeout }}"
                role="{{ $isError ? 'alert' : 'status' }}"
                @class([
                    'pointer-events-auto relative overflow-hidden rounded-xl bg-white shadow-lg ring-1 transition duration-200 animate-toast-in motion-reduce:animate-none',
                    'ring-red-200' => $isError,
                    'ring-emerald-200' => ! $isError,
                ])
            >
                <div class="flex items-start gap-3 p-4">
                    <span
                        @class([
                            'flex size-8 shrink-0 items-center justify-center rounded-full',
                            'bg-red-100 text-red-700' => $isError,
                            'bg-emerald-100 text-emerald-700' => ! $isError,
                        ])
                        aria-hidden="true"
                    >
                        @if ($isError)
                            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                        @else
                            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                        @endif
                    </span>

                    <div class="min-w-0 flex-1 pt-0.5">
                        <p class="text-sm font-semibold text-slate-900">{{ $isError ? 'Algo deu errado' : 'Tudo certo' }}</p>

                        @if (count($toast['messages']) > 1)
                            <ul class="mt-1 list-inside list-disc space-y-1 text-sm text-slate-600">
                                @foreach ($toast['messages'] as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-0.5 text-sm text-slate-600">{{ $toast['messages'][0] }}</p>
                        @endif
                    </div>

                    <button
                        type="button"
                        hidden
                        data-toast-close
                        aria-label="Fechar notificação"
                        class="-mr-1 -mt-1 flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-xl leading-none text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >&times;</button>
                </div>

                <div
                    @class([
                        'h-1 animate-toast-progress motion-reduce:animate-none',
                        'bg-red-500' => $isError,
                        'bg-emerald-500' => ! $isError,
                    ])
                    style="animation-duration: {{ $timeout }}ms"
                    aria-hidden="true"
                ></div>
            </div>
        @endforeach
    </div>

    @once
        @push('scripts')
            <script>
                document.querySelectorAll('[data-toast]').forEach(function (toast) {
                    var close = toast.querySelector('[data-toast-close]');

                    var dismiss = function () {
                        toast.classList.add('opacity-0', 'translate-x-4');
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

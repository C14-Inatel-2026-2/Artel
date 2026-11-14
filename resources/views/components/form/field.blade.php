@props([
    'name',
    'label',
    'type' => 'text',
    'autocomplete' => null,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
    $isPassword = $type === 'password';
    $describedBy = collect([$hint ? "{$id}-hint" : null, $error ? "{$id}-error" : null])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-800">{{ $label }}</label>

    <div class="relative mt-1.5">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @unless ($isPassword) value="{{ old($name) }}" @endunless
            required
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('id')->class([
                'block min-h-11 w-full rounded-md border bg-white px-3 py-2.5 text-base text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                'border-red-400' => $error,
                'border-slate-300' => ! $error,
                'pr-20' => $isPassword,
            ]) }}
        >

        @if ($isPassword)
            <button
                type="button"
                hidden
                data-toggle-password="{{ $id }}"
                aria-controls="{{ $id }}"
                aria-label="Mostrar senha"
                class="absolute inset-y-0 right-0 rounded-md px-3 text-sm font-medium text-indigo-700 hover:text-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >Mostrar</button>
        @endif
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-sm text-slate-600">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $error }}</p>
    @endif
</div>

@if ($isPassword)
    @once
        @push('scripts')
            <script>
                document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
                    var input = document.getElementById(button.dataset.togglePassword);

                    button.hidden = false;
                    button.addEventListener('click', function () {
                        var show = input.type === 'password';

                        input.type = show ? 'text' : 'password';
                        button.textContent = show ? 'Ocultar' : 'Mostrar';
                        button.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
                    });
                });
            </script>
        @endpush
    @endonce
@endif

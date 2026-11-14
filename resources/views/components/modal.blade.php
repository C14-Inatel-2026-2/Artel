@props([
    'id',
    'title',
    'size' => 'md',
])

@php
    $width = match ($size) {
        'lg' => 'max-w-lg',
        default => 'max-w-md',
    };
@endphp

<dialog
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-titulo"
    class="m-auto w-[calc(100%-2rem)] {{ $width }} rounded-xl p-0 text-slate-800 shadow-xl backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm"
>
    <div class="relative p-6">
        <button type="button" data-close-modal aria-label="Fechar" class="absolute right-3 top-3 flex size-9 cursor-pointer items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            <span class="text-2xl leading-none" aria-hidden="true">&times;</span>
        </button>

        <h2 id="{{ $id }}-titulo" class="pr-10 text-lg font-semibold text-slate-900">{{ $title }}</h2>

        {{ $slot }}
    </div>
</dialog>

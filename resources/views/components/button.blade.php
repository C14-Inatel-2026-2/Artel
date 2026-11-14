@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex cursor-pointer items-center justify-center rounded-md font-semibold transition-colors duration-300 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';

    $sizes = match ($size) {
        'sm' => 'min-h-9 px-3 py-1.5 text-sm',
        default => 'min-h-11 px-5 py-2.5 text-base',
    };

    $colors = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 active:bg-slate-200',
        'danger' => 'bg-red-600 text-white hover:bg-red-800 active:bg-red-900',
        default => 'bg-indigo-600 text-white hover:bg-indigo-800 active:bg-indigo-900',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base.' '.$sizes.' '.$colors]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base.' '.$sizes.' '.$colors]) }}>{{ $slot }}</button>
@endif

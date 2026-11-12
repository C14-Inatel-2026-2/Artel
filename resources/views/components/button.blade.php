@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex min-h-11 cursor-pointer items-center justify-center rounded-md px-5 py-2.5 text-base font-semibold transition-colors duration-300 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2';

    $colors = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 active:bg-slate-200',
        default => 'bg-indigo-600 text-white hover:bg-indigo-800 active:bg-indigo-900',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base.' '.$colors]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base.' '.$colors]) }}>{{ $slot }}</button>
@endif

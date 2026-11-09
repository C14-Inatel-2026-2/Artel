<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Artel')</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased flex flex-col">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <a href="{{ url('/') }}" class="text-xl font-semibold text-indigo-600">Artel</a>

            <nav class="flex items-center gap-4 text-sm">
                @yield('nav')
            </nav>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col px-4 py-8">
        @yield('content')
    </main>

    <x-toasts />

    @stack('scripts')
</body>
</html>

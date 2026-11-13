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
    <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col px-4 py-8">
        @yield('content')
    </main>

    <x-toasts />

    @stack('scripts')
</body>
</html>

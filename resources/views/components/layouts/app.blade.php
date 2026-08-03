<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CEO Stat' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    @auth
    <div class="mx-auto max-w-7xl px-4 pt-5 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-stone-200 bg-white px-4 py-3 shadow-sm">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-green-950 font-extrabold text-white">CS</span>
                <span><strong class="block leading-tight">CEO Stat</strong><small class="text-stone-500">{{ auth()->user()->role->label() }}</small></span>
            </a>
            <nav class="flex flex-wrap items-center gap-2 text-sm font-bold">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}" href="{{ route('dashboard') }}">Календарь</a>
                @if (in_array(auth()->user()->role->value, ['admin', 'operator'], true))
                    <a class="nav-link {{ request()->routeIs('robots.*') ? 'nav-link-active' : '' }}" href="{{ route('robots.index') }}">Роботы</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link">Выйти</button></form>
            </nav>
        </header>
    </div>
    @endauth
    {{ $slot }}
    @livewireScripts
</body>
</html>

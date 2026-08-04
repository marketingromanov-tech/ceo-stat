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
<body class="min-h-screen bg-[#fafafb] text-[#030229] antialiased">
    @auth
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-[218px] flex-col bg-white lg:flex">
        <a href="{{ route('dashboard') }}" class="flex h-28 items-center gap-3 px-7"><span class="grid size-11 place-items-center rounded-full bg-[#605bff] font-extrabold text-white">CS</span><strong class="text-xl">CEO Stat</strong></a>
        <nav class="mt-4 space-y-2 text-sm font-bold">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}" href="{{ route('dashboard') }}"><span class="grid size-6 grid-cols-2 gap-1">@for($i=0;$i<4;$i++)<i class="rounded-sm bg-current opacity-70"></i>@endfor</span>Обзор</a>
            <a class="nav-link {{ request()->routeIs('robots.*') ? 'nav-link-active' : '' }}" href="{{ route('robots.index') }}"><span class="grid size-6 place-items-center rounded-md bg-current/10 text-xs">R</span>{{ auth()->user()->role->value === 'viewer' ? 'Мои роботы' : 'Роботы' }}</a>
            @if (auth()->user()->role->value === 'admin')<a class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}" href="{{ route('users.index') }}"><span class="grid size-6 place-items-center rounded-md bg-current/10 text-xs">U</span>Пользователи</a>@endif
        </nav>
        <div class="mt-auto border-t border-[#030229]/5 p-6"><p class="text-sm font-bold">{{ auth()->user()->name }}</p><p class="mt-0.5 text-xs text-[#030229]/40">{{ auth()->user()->role->label() }}</p><form class="mt-4" method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-bold text-[#030229]/50 hover:text-[#605bff]">Выйти →</button></form></div>
    </aside>
    <header class="sticky top-0 z-40 flex items-center justify-between border-b border-[#030229]/5 bg-white px-4 py-3 lg:hidden"><a href="{{ route('dashboard') }}" class="flex items-center gap-2"><span class="grid size-9 place-items-center rounded-full bg-[#605bff] text-xs font-extrabold text-white">CS</span><strong>CEO Stat</strong></a><nav class="flex items-center gap-1 text-xs font-bold"><a class="rounded-lg px-2 py-2" href="{{ route('dashboard') }}">Обзор</a><a class="rounded-lg px-2 py-2" href="{{ route('robots.index') }}">Роботы</a>@if(auth()->user()->role->value === 'admin')<a class="rounded-lg px-2 py-2" href="{{ route('users.index') }}">Люди</a>@endif</nav></header>
    <div class="lg:pl-[218px]">
    @endauth
    {{ $slot }}
    @auth</div>@endauth
    @livewireScripts
</body>
</html>

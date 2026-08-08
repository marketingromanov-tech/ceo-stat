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
            <a class="nav-link {{ request()->routeIs('portfolio.*') ? 'nav-link-active' : '' }}" href="{{ route('portfolio.statistics') }}"><span class="grid size-6 place-items-center rounded-md bg-current/10 text-xs">P</span>Портфель</a>
            <a class="nav-link {{ request()->routeIs('robots.*') ? 'nav-link-active' : '' }}" href="{{ route('robots.index') }}"><span class="grid size-6 place-items-center rounded-md bg-current/10 text-xs">R</span>{{ auth()->user()->role->value === 'viewer' ? 'Мои роботы' : 'Роботы' }}</a>
            @if (auth()->user()->role->value === 'admin')<a class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}" href="{{ route('users.index') }}"><span class="grid size-6 place-items-center rounded-md bg-current/10 text-xs">U</span>Пользователи</a>@endif
        </nav>
        <div class="mt-auto border-t border-[#030229]/5 p-6"><p class="text-sm font-bold">{{ auth()->user()->name }}</p><p class="mt-0.5 text-xs text-[#030229]/40">{{ auth()->user()->role->label() }}</p><form class="mt-4" method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-bold text-[#030229]/50 hover:text-[#605bff]">Выйти →</button></form></div>
    </aside>
    <header class="sticky top-0 z-40 flex items-center justify-between border-b border-[#030229]/5 bg-white/95 px-4 py-3 backdrop-blur lg:hidden"><a href="{{ route('dashboard') }}" class="flex items-center gap-2"><span class="grid size-9 place-items-center rounded-full bg-[#605bff] text-xs font-extrabold text-white">CS</span><strong>CEO Stat</strong></a><div class="text-right"><p class="max-w-32 truncate text-xs font-bold">{{ auth()->user()->name }}</p><p class="text-[10px] text-[#030229]/40">{{ auth()->user()->role->label() }}</p></div></header>
    <nav class="fixed inset-x-0 bottom-0 z-50 grid {{ auth()->user()->role->value === 'admin' ? 'grid-cols-5' : 'grid-cols-4' }} border-t border-[#030229]/5 bg-white/95 px-2 pb-[max(.5rem,env(safe-area-inset-bottom))] pt-2 shadow-[0_-8px_30px_rgba(3,2,41,.08)] backdrop-blur lg:hidden">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 rounded-lg py-1.5 text-[10px] font-bold {{ request()->routeIs('dashboard') ? 'text-[#605bff]' : 'text-[#030229]/45' }}"><span class="grid size-5 grid-cols-2 gap-0.5">@for($i=0;$i<4;$i++)<i class="rounded-[2px] bg-current"></i>@endfor</span>Обзор</a>
        <a href="{{ route('portfolio.statistics') }}" class="flex flex-col items-center gap-1 rounded-lg py-1.5 text-[10px] font-bold {{ request()->routeIs('portfolio.*') ? 'text-[#605bff]' : 'text-[#030229]/45' }}"><span class="grid size-5 place-items-center rounded-md bg-current/10 text-[10px]">P</span>Портфель</a>
        <a href="{{ route('robots.index') }}" class="flex flex-col items-center gap-1 rounded-lg py-1.5 text-[10px] font-bold {{ request()->routeIs('robots.*') ? 'text-[#605bff]' : 'text-[#030229]/45' }}"><span class="grid size-5 place-items-center rounded-md bg-current/10 text-[10px]">R</span>{{ auth()->user()->role->value === 'viewer' ? 'Мои роботы' : 'Роботы' }}</a>
        @if(auth()->user()->role->value === 'admin')<a href="{{ route('users.index') }}" class="flex flex-col items-center gap-1 rounded-lg py-1.5 text-[10px] font-bold {{ request()->routeIs('users.*') ? 'text-[#605bff]' : 'text-[#030229]/45' }}"><span class="grid size-5 place-items-center rounded-md bg-current/10 text-[10px]">U</span>Люди</a>@endif
        <form method="POST" action="{{ route('logout') }}" class="contents">@csrf<button class="group flex flex-col items-center gap-1 rounded-lg py-1.5 text-[10px] font-bold text-[#030229]/45 transition-colors hover:text-red-500" aria-label="Выйти из аккаунта"><svg class="size-5 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M14 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg><span>Выход</span></button></form>
    </nav>
    @if(in_array(auth()->user()->role->value, ['admin', 'operator'], true))
        <livewire:quick-result-entry :context-robot-id="request()->routeIs('robots.show') ? request()->route('robot')->getKey() : null" />
    @endif
    <div class="pb-20 lg:pb-0 lg:pl-[218px]">
    @endauth
    {{ $slot }}
    @auth</div>@endauth
    @livewireScripts
</body>
</html>

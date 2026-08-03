<x-layouts.app title="Обзор — CEO Stat">
    <div class="mx-auto min-h-screen max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
        <header class="mb-7 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-2xl bg-green-950 font-extrabold text-white">CS</span><div><h1 class="text-xl font-extrabold">CEO Stat</h1><p class="text-sm text-stone-500">{{ auth()->user()->role->label() }}</p></div></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-xl border border-stone-200 bg-white px-4 py-2 text-sm font-bold hover:bg-stone-100">Выйти</button></form>
        </header>
        <section class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl bg-green-950 p-5 text-white"><p class="text-xs font-bold uppercase tracking-wider text-white/60">Результат за месяц</p><p class="mt-2 text-3xl font-extrabold">{{ number_format($monthProfit, 2, ',', ' ') }}</p></article>
            <article class="stat-card"><p class="stat-label">Доходность</p><p class="stat-value {{ $monthPercent >= 0 ? 'text-green-800' : 'text-red-700' }}">{{ number_format($monthPercent, 2, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Среднее за день</p><p class="stat-value">{{ number_format($dailyAverage, 2, ',', ' ') }}</p></article>
            <article class="stat-card"><p class="stat-label">Депозит</p><p class="stat-value">{{ number_format($deposit, 2, ',', ' ') }}</p></article>
        </section>
        <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="rounded-2xl border border-stone-200 bg-white p-5"><div class="mb-5 flex items-center justify-between"><div><h2 class="font-extrabold">Торговые роботы</h2><p class="text-sm text-stone-500">{{ $robotsCount }} роботов · {{ $accountsCount }} счетов</p></div><span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-900">Онлайн</span></div><div class="grid min-h-80 place-items-center rounded-xl border border-dashed border-stone-300 bg-stone-50 text-center text-stone-500"><div><p class="font-bold text-stone-700">Календарь результатов</p><p class="mt-1 text-sm">Появится после добавления первого торгового счёта</p></div></div></div>
            <aside class="rounded-2xl border border-stone-200 bg-white p-5"><h2 class="mb-4 font-extrabold">Последние записи</h2><div class="space-y-3">@forelse($recentResults as $result)<div class="flex items-center justify-between rounded-xl bg-stone-50 p-3"><div><p class="text-sm font-bold">{{ $result->account->robot->name }}</p><p class="text-xs text-stone-500">{{ $result->traded_at->format('d.m.Y') }}</p></div><span class="font-extrabold {{ $result->amount >= 0 ? 'text-green-800' : 'text-red-700' }}">{{ $result->amount >= 0 ? '+' : '' }}{{ number_format($result->amount, 2, ',', ' ') }}</span></div>@empty<p class="rounded-xl bg-stone-50 p-4 text-sm text-stone-500">Записей пока нет.</p>@endforelse</div></aside>
        </section>
    </div>
</x-layouts.app>

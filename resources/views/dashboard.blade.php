<x-layouts.app title="Обзор — CEO Stat">
    <div class="mx-auto min-h-screen max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
        <header class="mb-7"><h1 class="text-2xl font-extrabold">Результаты торговли</h1><p class="mt-1 text-sm text-stone-500">Ежедневная статистика роботов и торговых счетов</p></header>
        <section class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl bg-amber-900 p-5 text-white"><p class="text-xs font-bold uppercase tracking-wider text-white/65">Итого за всё время</p><p class="mt-2 text-3xl font-extrabold">{{ number_format($allTimeProfit, 2, ',', ' ') }}</p></article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><p class="stat-label">% за всё время</p><p class="stat-value {{ $allTimePercent >= 0 ? 'text-amber-900' : 'text-red-700' }}">{{ number_format($allTimePercent, 3, ',', ' ') }}%</p></article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><p class="stat-label">% в день за всё время</p><p class="stat-value {{ $allTimeDayPercent >= 0 ? 'text-amber-900' : 'text-red-700' }}">{{ number_format($allTimeDayPercent, 3, ',', ' ') }}%</p></article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><p class="stat-label">Среднее в день за всё время</p><p class="stat-value">{{ number_format($allTimeDailyAverage, 2, ',', ' ') }}</p></article>
        </section>
        <section class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl bg-green-950 p-5 text-white"><p class="text-xs font-bold uppercase tracking-wider text-white/60">Итого за месяц</p><p class="mt-2 text-3xl font-extrabold">{{ number_format($monthProfit, 2, ',', ' ') }}</p></article>
            <article class="stat-card"><p class="stat-label">% за месяц</p><p class="stat-value {{ $monthPercent >= 0 ? 'text-green-800' : 'text-red-700' }}">{{ number_format($monthPercent, 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">% за день</p><p class="stat-value {{ $dayPercent >= 0 ? 'text-green-800' : 'text-red-700' }}">{{ number_format($dayPercent, 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Среднее в день</p><p class="stat-value">{{ number_format($dailyAverage, 2, ',', ' ') }}</p></article>
        </section>
        <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <livewire:result-calendar :read-only="true" />
            <aside class="h-fit rounded-2xl border border-stone-200 bg-white p-5"><h2 class="mb-4 font-extrabold">Последние записи</h2><div class="max-h-[36rem] space-y-3 overflow-y-auto pr-2">@forelse($recentResults as $result)<div class="flex items-center justify-between rounded-xl bg-stone-50 p-3"><div><p class="text-sm font-bold">{{ $result->account->robot->name }}</p><p class="text-xs text-stone-500">{{ $result->traded_at->format('d.m.Y') }}</p></div><span class="font-extrabold {{ $result->amount >= 0 ? 'text-green-800' : 'text-red-700' }}">{{ $result->amount >= 0 ? '+' : '' }}{{ number_format($result->amount, 2, ',', ' ') }}</span></div>@empty<p class="rounded-xl bg-stone-50 p-4 text-sm text-stone-500">Записей пока нет.</p>@endforelse</div></aside>
        </section>
    </div>
</x-layouts.app>

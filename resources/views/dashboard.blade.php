<x-layouts.app title="Обзор — CEO Stat">
    <div class="mx-auto min-h-screen max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
        <header class="mb-7"><h1 class="text-2xl font-extrabold">Результаты торговли</h1><p class="mt-1 text-sm text-stone-500">Ежедневная статистика роботов и торговых счетов</p></header>
        <section class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[10px] bg-[#605bff] p-5 text-white"><p class="text-xs font-bold uppercase tracking-wider text-white/65">Итого за всё время</p><p class="mt-2 text-3xl font-extrabold">{{ number_format($allTimeProfit, 2, ',', ' ') }}</p></article>
            <article class="stat-card"><p class="stat-label">% за всё время</p><p class="stat-value {{ $allTimePercent >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($allTimePercent, 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">% в день за всё время</p><p class="stat-value {{ $allTimeDayPercent >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($allTimeDayPercent, 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Среднее в день за всё время</p><p class="stat-value">{{ number_format($allTimeDailyAverage, 2, ',', ' ') }}</p></article>
        </section>
        <section class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[10px] bg-[#030229] p-5 text-white"><p class="text-xs font-bold uppercase tracking-wider text-white/60">Итого за месяц</p><p class="mt-2 text-3xl font-extrabold">{{ number_format($monthProfit, 2, ',', ' ') }}</p></article>
            <article class="stat-card"><p class="stat-label">% за месяц</p><p class="stat-value {{ $monthPercent >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($monthPercent, 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">% за день</p><p class="stat-value {{ $dayPercent >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($dayPercent, 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Среднее в день</p><p class="stat-value">{{ number_format($dailyAverage, 2, ',', ' ') }}</p></article>
        </section>
        <section class="mb-7 grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
            <div class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <article class="relative overflow-hidden rounded-[10px] bg-white p-5"><div class="relative z-10 flex items-start justify-between gap-3"><div><p class="text-sm text-[#030229]/50">Результат за месяц</p><p class="mt-2 text-2xl font-extrabold">{{ number_format($monthProfit, 2, ',', ' ') }}</p></div><span class="rounded-lg bg-[#5b93ff]/10 px-2 py-1 text-xs font-bold text-[#5b93ff]">{{ number_format($monthPercent, 3, ',', ' ') }}%</span></div><div class="mt-3 h-24"><canvas id="daily-results-chart"></canvas></div></article>
                    <article class="relative overflow-hidden rounded-[10px] bg-white p-5"><div class="relative z-10 flex items-start justify-between gap-3"><div><p class="text-sm text-[#030229]/50">Накоплено за всё время</p><p class="mt-2 text-2xl font-extrabold">{{ number_format($allTimeProfit, 2, ',', ' ') }}</p></div><span class="rounded-lg bg-[#ffd66b]/20 px-2 py-1 text-xs font-bold text-[#b58100]">{{ number_format($allTimePercent, 3, ',', ' ') }}%</span></div><div class="mt-3 h-24"><canvas id="cumulative-results-chart"></canvas></div></article>
                </div>
                <article class="rounded-[10px] bg-white p-5"><div class="mb-5 flex items-start justify-between gap-4"><div><h2 class="text-lg font-bold">Динамика торговли</h2><p class="mt-1 text-sm text-[#030229]/45">Результаты по дням выбранного месяца</p></div><span class="text-2xl text-[#030229]/30">•••</span></div><div class="h-80"><canvas id="trading-dynamics-chart"></canvas></div></article>
            </div>
            <div class="space-y-5">
                <article class="rounded-[10px] bg-white p-5"><div class="mb-5"><h2 class="text-lg font-bold">Результаты по месяцам</h2><p class="mt-1 text-sm text-[#030229]/45">Последние 12 месяцев</p></div><div class="h-72"><canvas id="monthly-results-chart"></canvas></div></article>
                <article class="rounded-[10px] bg-white p-5"><div class="mb-2 flex items-start justify-between"><div><h2 class="text-lg font-bold">Вклад роботов</h2><p class="mt-1 text-sm text-[#030229]/45">Доля в общем результате</p></div><span class="text-2xl text-[#030229]/30">•••</span></div><div class="mx-auto h-64 max-w-72"><canvas id="robot-results-chart"></canvas></div></article>
            </div>
        </section>
        <script id="dashboard-chart-data" type="application/json">@json($chartData)</script>
        <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <livewire:result-calendar :read-only="true" />
            <aside class="h-fit rounded-[10px] bg-white p-5"><h2 class="mb-4 font-extrabold">Последние записи</h2><div class="max-h-[36rem] space-y-3 overflow-y-auto pr-2">@forelse($recentResults as $result)<div class="flex items-center justify-between rounded-lg bg-[#fafafb] p-3"><div><p class="text-sm font-bold">{{ $result->account->robot->name }}</p><p class="text-xs text-[#030229]/40">{{ $result->traded_at->format('d.m.Y') }}</p></div><span class="font-extrabold {{ $result->amount >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ $result->amount >= 0 ? '+' : '' }}{{ number_format($result->amount, 2, ',', ' ') }}</span></div>@empty<p class="rounded-xl bg-stone-50 p-4 text-sm text-stone-500">Записей пока нет.</p>@endforelse</div></aside>
        </section>
    </div>
</x-layouts.app>

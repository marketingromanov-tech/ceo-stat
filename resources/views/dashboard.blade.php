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
        <section class="mb-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <article class="relative h-[220px] overflow-hidden rounded-[10px] bg-white p-5">
                <div class="relative z-10 flex items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#5b93ff]/10 text-xl text-[#5b93ff]">↗</span><div><p class="text-sm text-[#030229]/55">Динамика по дням</p><p class="mt-0.5 text-2xl font-extrabold">{{ number_format($monthProfit, 2, ',', ' ') }}</p></div></div><span class="text-right text-xs font-bold {{ $monthPercent >= 0 ? 'text-[#605bff]' : 'text-red-600' }}">{{ $monthPercent >= 0 ? '+' : '' }}{{ number_format($monthPercent, 3, ',', ' ') }}% за месяц</span></div>
                <div class="absolute inset-x-0 bottom-0 h-[105px]"><canvas id="daily-results-chart"></canvas></div>
            </article>
            <article class="relative h-[220px] overflow-hidden rounded-[10px] bg-white p-5">
                <div class="relative z-10 flex items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#ffd66b]/20 text-xl text-[#c28a00]">∑</span><div><p class="text-sm text-[#030229]/55">Накопительный результат</p><p class="mt-0.5 text-2xl font-extrabold">{{ number_format($allTimeProfit, 2, ',', ' ') }}</p></div></div><span class="text-right text-xs font-bold {{ $allTimePercent >= 0 ? 'text-[#605bff]' : 'text-red-600' }}">{{ $allTimePercent >= 0 ? '+' : '' }}{{ number_format($allTimePercent, 3, ',', ' ') }}% за всё время</span></div>
                <div class="absolute inset-x-0 bottom-0 h-[105px]"><canvas id="cumulative-results-chart"></canvas></div>
            </article>
            <article class="relative h-[220px] overflow-hidden rounded-[10px] bg-white p-5">
                <div class="relative z-10 flex items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#605bff]/10 text-xl text-[#605bff]">◎</span><div><p class="text-sm text-[#030229]/55">Сравнение роботов</p><p class="mt-0.5 text-2xl font-extrabold">{{ $robotsCount }}</p></div></div><span class="text-right text-xs font-bold text-[#605bff]">Активных роботов</span></div>
                <div class="absolute inset-x-0 bottom-0 h-[105px]"><canvas id="robot-results-chart"></canvas></div>
            </article>
            <article class="relative h-[220px] overflow-hidden rounded-[10px] bg-white p-5">
                <div class="relative z-10 flex items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#ff8f6b]/10 text-xl text-[#ff8f6b]">▥</span><div><p class="text-sm text-[#030229]/55">Результаты по месяцам</p><p class="mt-0.5 text-2xl font-extrabold">{{ number_format($allTimeProfit, 2, ',', ' ') }}</p></div></div><span class="text-right text-xs font-bold text-[#ff8f6b]">{{ count($chartData['monthly']['labels']) }} мес. с данными</span></div>
                <div class="absolute inset-x-0 bottom-0 h-[105px]"><canvas id="monthly-results-chart"></canvas></div>
            </article>
        </section>
        <script id="dashboard-chart-data" type="application/json">@json($chartData)</script>
        <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <livewire:result-calendar :read-only="true" />
            <aside class="h-fit rounded-[10px] bg-white p-5"><h2 class="mb-4 font-extrabold">Последние записи</h2><div class="max-h-[36rem] space-y-3 overflow-y-auto pr-2">@forelse($recentResults as $result)<div class="flex items-center justify-between rounded-lg bg-[#fafafb] p-3"><div><p class="text-sm font-bold">{{ $result->account->robot->name }}</p><p class="text-xs text-[#030229]/40">{{ $result->traded_at->format('d.m.Y') }}</p></div><span class="font-extrabold {{ $result->amount >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ $result->amount >= 0 ? '+' : '' }}{{ number_format($result->amount, 2, ',', ' ') }}</span></div>@empty<p class="rounded-xl bg-stone-50 p-4 text-sm text-stone-500">Записей пока нет.</p>@endforelse</div></aside>
        </section>
    </div>
</x-layouts.app>

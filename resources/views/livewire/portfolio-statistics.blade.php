<main class="mx-auto max-w-7xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
    @php
        $periodLabels = ['all' => 'Всё время', '7_days' => '7 дней', '30_days' => '30 дней', '3_months' => '3 месяца', '6_months' => '6 месяцев', 'current_year' => 'Текущий год', 'custom' => 'Свой период'];
        $periodFrom = $statistics['period']['from'] ? \Carbon\CarbonImmutable::parse($statistics['period']['from']) : null;
        $periodTo = \Carbon\CarbonImmutable::parse($statistics['period']['to']);
        $rangeLabel = $statisticsPeriod === 'all'
            ? 'За всё время'.($periodFrom ? ' • с '.$periodFrom->format('d.m.Y') : '')
            : ($periodFrom ? $periodFrom->format('d.m.Y').' — '.$periodTo->format('d.m.Y') : 'До '.$periodTo->format('d.m.Y'));
    @endphp

    <section class="space-y-6" aria-labelledby="portfolio-statistics-title">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#605bff]">Портфель</p>
                <h1 id="portfolio-statistics-title" class="mt-1 text-2xl font-extrabold tracking-tight text-[#030229] sm:text-3xl">Общая статистика</h1>
                <p class="mt-1.5 text-sm text-[#030229]/45">{{ $statistics['period']['has_data'] ? $rangeLabel : 'За выбранный период данных нет' }}</p>
            </div>

            <div class="w-full xl:max-w-3xl">
                <select wire:change="selectStatisticsPeriod($event.target.value)" class="form-input sm:hidden" aria-label="Период статистики портфеля">
                    @foreach($periodLabels as $value => $label)<option value="{{ $value }}" @selected($statisticsPeriod === $value)>{{ $label }}</option>@endforeach
                </select>
                @if($statisticsPeriod !== 'all')<button type="button" wire:click="resetStatisticsPeriod" class="mt-2 text-xs font-extrabold text-[#605bff] sm:hidden">Сбросить</button>@endif

                <div class="hidden flex-wrap justify-end gap-2 sm:flex">
                    @foreach($periodLabels as $value => $label)
                        <button type="button" wire:click="selectStatisticsPeriod('{{ $value }}')" class="rounded-lg px-3 py-2 text-xs font-extrabold transition {{ $statisticsPeriod === $value ? 'bg-[#605bff] text-white' : 'bg-white text-[#030229]/60 hover:bg-[#605bff]/10 hover:text-[#605bff]' }}">{{ $label }}</button>
                    @endforeach
                    @if($statisticsPeriod !== 'all')<button type="button" wire:click="resetStatisticsPeriod" class="px-2 py-2 text-xs font-extrabold text-[#605bff]">Сбросить</button>@endif
                </div>

                @if($statisticsPeriod === 'custom')
                    <form wire:submit="applyCustomStatisticsPeriod" class="mt-3 grid gap-3 rounded-xl bg-white p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start">
                        <div><label class="form-label">С даты</label><input type="date" wire:model="statisticsStartDate" max="{{ now()->format('Y-m-d') }}" class="form-input">@error('statisticsStartDate')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label">По дату</label><input type="date" wire:model="statisticsEndDate" max="{{ now()->format('Y-m-d') }}" class="form-input">@error('statisticsEndDate')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <button class="btn-primary sm:mt-[22px]">Применить</button>
                    </form>
                @endif
            </div>
        </div>

        @php($kpi = $statistics['kpi'])
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <article class="relative overflow-hidden rounded-[10px] bg-[#605bff] p-5 text-white shadow-[0_6px_24px_rgba(3,2,41,0.05)]"><span class="absolute -right-5 -top-5 size-24 rounded-full bg-white/10"></span><p class="text-xs font-bold uppercase tracking-wider text-white/60">{{ $statisticsPeriod === 'all' ? 'Прибыль за всё время' : 'Прибыль за период' }}</p><p class="mt-2 text-2xl font-extrabold">{{ number_format($kpi['profit'], 2, ',', ' ') }}</p></article>
            <article class="stat-card"><p class="stat-label">Доходность</p><p class="mt-2 text-2xl font-extrabold {{ $kpi['return_percent'] >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($kpi['return_percent'], 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Средний % в день</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($kpi['average_daily_percent'], 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Рабочих дней</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ $kpi['working_days'] }}</p></article>
            <article class="stat-card"><p class="stat-label">Прибыльных дней</p><p class="mt-2 text-2xl font-extrabold text-[#605bff]">{{ $kpi['profitable_days'] }}</p></article>
            <article class="stat-card"><p class="stat-label">Процент прибыльных</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($kpi['profitable_days_percent'], 1, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Максимальная просадка</p><p class="mt-2 text-2xl font-extrabold text-red-700">{{ number_format($kpi['max_drawdown'], 2, ',', ' ') }}</p><p class="mt-1 text-xs font-bold text-red-700/70">{{ number_format($kpi['max_drawdown_percent'], 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Profit Factor</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ $kpi['profit_factor_state'] === 'infinite' ? '∞' : ($kpi['profit_factor_state'] === 'undefined' ? '—' : number_format($kpi['profit_factor'], 3, ',', ' ')) }}</p><p class="mt-1 text-[10px] text-[#030229]/40">Прибыль {{ number_format($kpi['gross_profit'], 2, ',', ' ') }} / убыток {{ number_format($kpi['gross_loss'], 2, ',', ' ') }}</p></article>
        </div>

        @php($quality = $statistics['quality'])
        <section class="rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]" aria-labelledby="portfolio-quality-title">
            <h2 id="portfolio-quality-title" class="text-lg font-extrabold text-[#030229]">Качество торговли</h2>
            <dl class="mt-4 grid grid-cols-2 gap-x-5 gap-y-4 sm:grid-cols-3 lg:grid-cols-5">
                <div><dt class="stat-label">Прибыльные дни</dt><dd class="mt-1 font-extrabold text-[#605bff]">{{ $quality['profitable_days'] }}</dd></div>
                <div><dt class="stat-label">Убыточные дни</dt><dd class="mt-1 font-extrabold text-red-700">{{ $quality['losing_days'] }}</dd></div>
                <div><dt class="stat-label">Нулевые дни</dt><dd class="mt-1 font-extrabold">{{ $quality['zero_days'] }}</dd></div>
                <div><dt class="stat-label">Средняя прибыль</dt><dd class="mt-1 font-extrabold text-[#605bff]">{{ number_format($quality['average_profitable_day'], 2, ',', ' ') }}</dd></div>
                <div><dt class="stat-label">Средний убыток</dt><dd class="mt-1 font-extrabold text-red-700">{{ number_format($quality['average_losing_day'], $quality['average_losing_day'] != 0 && abs($quality['average_losing_day']) < 0.01 ? 4 : 2, ',', ' ') }}</dd></div>
                <div><dt class="stat-label">Лучший день</dt><dd class="mt-1 font-extrabold">{{ number_format($quality['best_day'], 2, ',', ' ') }}</dd></div>
                <div><dt class="stat-label">Худший день</dt><dd class="mt-1 font-extrabold">{{ number_format($quality['worst_day'], 2, ',', ' ') }}</dd></div>
                <div><dt class="stat-label">Макс. серия +</dt><dd class="mt-1 font-extrabold">{{ $quality['max_winning_streak'] }}</dd></div>
                <div><dt class="stat-label">Макс. серия −</dt><dd class="mt-1 font-extrabold">{{ $quality['max_losing_streak'] }}</dd></div>
                <div><dt class="stat-label">Текущая серия</dt><dd class="mt-1 font-extrabold">{{ $quality['current_streak']['type'] === 'winning' ? '+' : ($quality['current_streak']['type'] === 'losing' ? '−' : '0') }}{{ $quality['current_streak']['length'] }}</dd></div>
            </dl>
        </section>

        <section class="grid gap-4 md:grid-cols-2" aria-label="Графики портфельной статистики">
            @foreach([
                ['portfolio-growth-chart', 'Рост портфеля', 'growth'],
                ['portfolio-daily-chart', 'Результат портфеля по дням', 'daily'],
                ['portfolio-monthly-chart', 'Прибыль по месяцам', 'monthly'],
                ['portfolio-robots-chart', 'Вклад роботов', 'robots'],
            ] as [$chartId, $chartTitle, $chartKey])
                @php($hasChartData = $chartKey === 'robots' ? count(array_filter($statistics['charts']['robots']['profit_values'], fn ($value) => (float) $value !== 0.0)) > 0 : count($statistics['charts'][$chartKey]['values']) > 0)
                <article class="rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]">
                    <h2 class="font-extrabold text-[#030229]">{{ $chartTitle }}</h2>
                    <div class="mt-4 h-64">
                        @if($hasChartData)
                            <canvas id="{{ $chartId }}"></canvas>
                        @else
                            <div class="grid h-full place-items-center rounded-lg bg-[#fafafb] px-4 text-center text-sm text-[#030229]/40">За выбранный период данных нет</div>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>
        <script id="portfolio-statistics-chart-data" type="application/json">@json($statistics['charts'])</script>

        <section class="overflow-hidden rounded-xl bg-white shadow-[0_6px_24px_rgba(3,2,41,0.05)]" aria-labelledby="portfolio-robots-title">
            <div class="flex items-center justify-between gap-3 border-b border-[#030229]/5 p-5"><div><p class="text-[10px] font-extrabold uppercase tracking-wider text-[#605bff]">Состав портфеля</p><h2 id="portfolio-robots-title" class="mt-1 text-lg font-extrabold text-[#030229]">Роботы</h2></div><span class="rounded-lg bg-[#605bff]/10 px-3 py-1.5 text-xs font-extrabold text-[#605bff]">{{ $statistics['portfolio']['robots_count'] }}</span></div>
            <div class="divide-y divide-[#030229]/5 sm:hidden">
                @forelse($robots as $robot)<article class="p-4"><div class="flex items-center justify-between gap-3"><div><p class="font-extrabold text-[#030229]">{{ $robot->name }}</p><p class="mt-1 text-xs text-[#030229]/40">{{ $robot->account?->name ?? 'Счёт не настроен' }}</p></div><span class="rounded-lg px-2.5 py-1 text-[10px] font-extrabold uppercase {{ $robot->is_active ? 'bg-[#605bff]/10 text-[#605bff]' : 'bg-stone-200 text-stone-600' }}">{{ $robot->is_active ? 'Активен' : 'Отключён' }}</span></div><p class="mt-3 text-xs font-bold text-[#030229]/50">{{ $robot->account?->broker ?: 'Брокер не указан' }} • {{ $robot->account?->currency ?? '—' }}</p></article>@empty<p class="p-5 text-sm text-[#030229]/45">Доступных роботов нет.</p>@endforelse
            </div>
            <div class="hidden overflow-x-auto sm:block"><table class="w-full min-w-[640px] text-left"><thead class="bg-[#fafafb] text-[10px] font-extrabold uppercase tracking-wider text-[#030229]/40"><tr><th class="px-5 py-3">Робот</th><th class="px-5 py-3">Торговый счёт</th><th class="px-5 py-3">Брокер</th><th class="px-5 py-3">Валюта</th><th class="px-5 py-3 text-right">Статус</th></tr></thead><tbody class="divide-y divide-[#030229]/5">@forelse($robots as $robot)<tr><td class="px-5 py-4 font-extrabold text-[#030229]">{{ $robot->name }}</td><td class="px-5 py-4 text-sm text-[#030229]/65">{{ $robot->account?->name ?? 'Не настроен' }}</td><td class="px-5 py-4 text-sm text-[#030229]/65">{{ $robot->account?->broker ?: '—' }}</td><td class="px-5 py-4 text-sm font-bold text-[#030229]">{{ $robot->account?->currency ?? '—' }}</td><td class="px-5 py-4 text-right"><span class="rounded-lg px-2.5 py-1 text-[10px] font-extrabold uppercase {{ $robot->is_active ? 'bg-[#605bff]/10 text-[#605bff]' : 'bg-stone-200 text-stone-600' }}">{{ $robot->is_active ? 'Активен' : 'Отключён' }}</span></td></tr>@empty<tr><td colspan="5" class="px-5 py-8 text-center text-sm text-[#030229]/45">Доступных роботов нет.</td></tr>@endforelse</tbody></table></div>
        </section>
    </section>
</main>

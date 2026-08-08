<main class="mx-auto max-w-7xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 sm:mb-7 sm:gap-4"><div><a href="{{ route('robots.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-bold text-[#605bff] transition hover:text-[#4f4ae8]">← Все роботы</a><h1 class="text-2xl font-extrabold tracking-tight text-[#030229] sm:text-3xl">{{ $robot->name }}</h1><p class="mt-1.5 text-sm text-[#030229]/45">{{ $robot->description ?: 'Ручной учёт показателей торгового робота' }}</p></div><div class="flex w-full items-center justify-between gap-1 rounded-[10px] bg-white p-1.5 sm:w-auto"><button type="button" wire:click="previousMonth" class="grid size-9 place-items-center rounded-lg text-sm font-extrabold text-[#605bff] transition hover:bg-[#605bff]/5" aria-label="Предыдущий месяц">←</button><p class="min-w-28 flex-1 text-center text-sm font-extrabold capitalize text-[#030229] sm:flex-none">{{ \Carbon\CarbonImmutable::createFromFormat('Y-m', $calendarMonth)->translatedFormat('F Y') }}</p><button type="button" wire:click="nextMonth" class="grid size-9 place-items-center rounded-lg text-sm font-extrabold text-[#605bff] transition hover:bg-[#605bff]/5" aria-label="Следующий месяц">→</button></div></div>

    @include('livewire.partials.robot-current-status', ['periods' => $statusPeriods])
    <section class="mobile-summary-list mb-5 space-y-3 sm:hidden">
        <details class="group overflow-hidden rounded-xl bg-[#605bff] text-white"><summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4"><div><p class="text-[10px] font-bold uppercase tracking-wider text-white/60">Итого за всё время</p><p class="mt-1 text-2xl font-extrabold">{{ number_format($allTimeProfit, 2, ',', ' ') }}</p></div><span class="text-xl transition group-open:rotate-180">⌄</span></summary><div class="grid grid-cols-4 gap-2 border-t border-white/10 px-4 py-3 text-center"><div><p class="text-[9px] text-white/55">Доходность</p><strong class="text-xs">{{ number_format($allTimePercent, 3, ',', ' ') }}%</strong></div><div><p class="text-[9px] text-white/55">% в день</p><strong class="text-xs">{{ number_format($allTimeDayPercent, 3, ',', ' ') }}%</strong></div><div><p class="text-[9px] text-white/55">Среднее</p><strong class="text-xs">{{ number_format($allTimeDailyAverage, 2, ',', ' ') }}</strong></div><div><p class="text-[9px] text-white/55">Рабочих дней</p><strong class="text-xs">{{ $allTimeWorkingDays }}</strong></div></div></details>
        <details class="group overflow-hidden rounded-xl bg-[#030229] text-white"><summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4"><div><p class="text-[10px] font-bold uppercase tracking-wider text-white/60">Итого за {{ \Carbon\CarbonImmutable::createFromFormat('Y-m', $calendarMonth)->translatedFormat('F Y') }}</p><p class="mt-1 text-2xl font-extrabold">{{ number_format($monthProfit, 2, ',', ' ') }}</p></div><span class="text-xl transition group-open:rotate-180">⌄</span></summary><div class="grid grid-cols-3 gap-2 border-t border-white/10 px-4 py-3 text-center"><div><p class="text-[9px] text-white/55">Доходность</p><strong class="text-xs">{{ number_format($monthPercent, 3, ',', ' ') }}%</strong></div><div><p class="text-[9px] text-white/55">% в день</p><strong class="text-xs">{{ number_format($dayPercent, 3, ',', ' ') }}%</strong></div><div><p class="text-[9px] text-white/55">Среднее</p><strong class="text-xs">{{ number_format($dailyAverage, 2, ',', ' ') }}</strong></div></div></details>
        <details class="group overflow-hidden rounded-xl bg-white"><summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4"><div><p class="text-[10px] font-bold uppercase tracking-wider text-[#030229]/45">Итого за сегодня</p><p class="mt-1 text-2xl font-extrabold {{ $todayProfit < 0 ? 'text-red-700' : 'text-[#605bff]' }}">{{ number_format($todayProfit, 2, ',', ' ') }}</p></div><span class="text-xl text-[#605bff] transition group-open:rotate-180">⌄</span></summary><div class="grid grid-cols-3 gap-2 border-t border-[#030229]/5 px-4 py-3 text-center"><div><p class="text-[9px] text-[#030229]/40">Доходность</p><strong class="text-xs">{{ number_format($todayPercent, 3, ',', ' ') }}%</strong></div><div><p class="text-[9px] text-[#030229]/40">Операций</p><strong class="text-xs">{{ $todayOperations }}</strong></div><div><p class="text-[9px] text-[#030229]/40">Среднее</p><strong class="text-xs">{{ number_format($todayAverage, 2, ',', ' ') }}</strong></div></div></details>
    </section>
    <section class="mb-4 hidden gap-4 sm:grid sm:grid-cols-2 xl:grid-cols-5">
        <article class="relative min-w-[78vw] snap-start overflow-hidden rounded-[10px] bg-[#605bff] p-5 text-white sm:min-w-0"><span class="absolute -right-5 -top-5 size-24 rounded-full bg-white/10"></span><p class="text-xs font-bold uppercase tracking-wider text-white/60">Итого за всё время</p><p class="mt-3 text-3xl font-extrabold tracking-tight">{{ number_format($allTimeProfit, 2, ',', ' ') }}</p><p class="mt-2 text-xs text-white/60">Общий финансовый результат</p></article>
        <article class="stat-card min-w-[78vw] snap-start sm:min-w-0"><p class="stat-label">% за всё время</p><p class="stat-value {{ $allTimePercent >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($allTimePercent, 3, ',', ' ') }}%</p></article>
        <article class="stat-card min-w-[78vw] snap-start sm:min-w-0"><p class="stat-label">% в день за всё время</p><p class="stat-value {{ $allTimeDayPercent >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($allTimeDayPercent, 3, ',', ' ') }}%</p></article>
        <article class="stat-card min-w-[78vw] snap-start sm:min-w-0"><p class="stat-label">Среднее в день за всё время</p><p class="stat-value">{{ number_format($allTimeDailyAverage, 2, ',', ' ') }}</p></article>
        <article class="stat-card min-w-[78vw] snap-start sm:min-w-0"><p class="stat-label">Рабочих дней</p><p class="stat-value text-[#605bff]">{{ $allTimeWorkingDays }}</p><p class="mt-1 text-[10px] text-stone-400">Записано дней: {{ $allTimeRecordedDays }}</p></article>
    </section>
    <section class="mb-6 hidden gap-4 sm:grid sm:grid-cols-2 xl:grid-cols-4">
        <article class="relative min-w-[78vw] snap-start overflow-hidden rounded-[10px] bg-[#030229] p-5 text-white sm:min-w-0"><span class="absolute -right-5 -top-5 size-24 rounded-full bg-white/10"></span><p class="text-xs font-bold uppercase tracking-wider text-white/60">Итого за месяц</p><p class="mt-3 text-3xl font-extrabold tracking-tight">{{ number_format($monthProfit, 2, ',', ' ') }}</p><p class="mt-2 text-xs text-white/60">Финансовый результат</p></article>
        <article class="min-w-[78vw] snap-start rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)] sm:min-w-0"><div class="mb-4 size-10 rounded-full bg-blue-50"></div><p class="text-xs font-bold uppercase tracking-wider text-stone-400">% за месяц</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($monthPercent, 3, ',', ' ') }}%</p></article>
        <article class="min-w-[78vw] snap-start rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)] sm:min-w-0"><div class="mb-4 size-10 rounded-full bg-amber-50"></div><p class="text-xs font-bold uppercase tracking-wider text-stone-400">% за день</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($dayPercent, 3, ',', ' ') }}%</p></article>
        <article class="min-w-[78vw] snap-start rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)] sm:min-w-0"><div class="mb-4 size-10 rounded-full bg-violet-50"></div><p class="text-xs font-bold uppercase tracking-wider text-stone-400">Среднее в день</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($dailyAverage, 2, ',', ' ') }}</p></article>
    </section>

    @if(auth()->user()->role->value === 'viewer')
        <details class="group mb-5 overflow-hidden rounded-xl bg-white sm:hidden"><summary class="flex cursor-pointer list-none items-center justify-between p-4"><div><p class="text-[10px] font-extrabold uppercase tracking-wider text-[#030229]/40">История</p><h2 class="mt-1 font-extrabold">Последние записи</h2></div><span class="text-xl text-[#605bff] transition group-open:rotate-180">⌄</span></summary><div class="max-h-96 space-y-2 overflow-y-auto border-t border-[#030229]/5 p-3">@forelse($recentResults as $result)<div class="flex items-center justify-between rounded-lg bg-[#fafafb] p-3"><div><p class="text-sm font-bold">{{ $robot->name }}</p><p class="text-xs text-[#030229]/40">{{ $result->traded_at->format('d.m.Y') }}</p></div><span class="font-extrabold {{ $result->amount >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ $result->amount >= 0 ? '+' : '' }}{{ number_format((float) $result->amount, 2, ',', ' ') }}</span></div>@empty<p class="p-3 text-sm text-stone-500">Записей пока нет.</p>@endforelse</div></details>
    @endif
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="hidden min-w-0 sm:block"><livewire:result-calendar :robot-id="$robot->id" :read-only="auth()->user()->role->value === 'viewer'" :key="'calendar-'.$robot->id.'-'.$accountRevision" /></div>
        @if(in_array(auth()->user()->role->value, ['admin', 'operator'], true))<form wire:submit="saveAccount" class="h-fit rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)] xl:sticky xl:top-5"><div class="mb-5"><p class="text-[10px] font-extrabold uppercase tracking-wider text-stone-400">Настройки</p><h2 class="mt-1 text-lg font-extrabold text-[#030229]">Торговый счёт</h2></div>@if(session('account-status'))<p class="mb-4 rounded-lg bg-[#605bff]/10 p-3 text-sm font-bold text-[#605bff]">{{ session('account-status') }}</p>@endif
            <div class="space-y-4"><div><label class="form-label">Название</label><input wire:model="name" class="form-input" placeholder="Основной счёт">@error('name')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div><div><label class="form-label">Брокер</label><input wire:model="broker" class="form-input"></div><div class="grid grid-cols-2 gap-3"><div><label class="form-label">Платформа</label><select wire:model="platform" class="form-input"><option value="manual">Ручной</option><option value="mt4">MT4</option><option value="mt5">MT5</option></select></div><div><label class="form-label">Валюта</label><input wire:model="currency" maxlength="3" class="form-input"></div></div><div><label class="form-label">Логин счёта</label><input wire:model="externalLogin" class="form-input"></div><div><label class="form-label">Депозит</label><input wire:model="initialDeposit" inputmode="decimal" class="form-input"></div><label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="isActive" class="size-4 rounded"> Счёт активен</label></div>
            <button class="btn-primary mt-5 w-full">Сохранить счёт</button>
        </form>
        @else
            <div class="relative hidden min-h-96 sm:block xl:min-h-0" style="align-self: stretch;">
                <aside class="flex h-full min-h-0 flex-col overflow-hidden rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)] xl:absolute xl:inset-0">
                    <div class="mb-4 shrink-0"><p class="text-[10px] font-extrabold uppercase tracking-wider text-stone-400">История</p><h2 class="mt-1 text-lg font-extrabold text-[#030229]">Последние записи</h2><p class="mt-1 text-xs text-[#030229]/40">До 100 последних операций</p></div>
                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-2">
                        @forelse($recentResults as $result)
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-[#fafafb] p-3">
                                <div><p class="text-sm font-bold text-[#030229]">{{ $robot->name }}</p><p class="text-xs text-[#030229]/40">{{ $result->traded_at->format('d.m.Y') }}</p></div>
                                <span class="font-extrabold {{ $result->amount >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ $result->amount >= 0 ? '+' : '' }}{{ number_format((float) $result->amount, 2, ',', ' ') }}</span>
                            </div>
                        @empty
                            <p class="rounded-lg bg-[#fafafb] p-4 text-sm text-[#030229]/45">Записей пока нет.</p>
                        @endforelse
                    </div>
                </aside>
            </div>
        @endif
    </div>

    <div class="mt-6">
        @include('livewire.partials.robot-finance')
    </div>

    <div class="mt-6">
        @include('livewire.partials.robot-status-periods')
    </div>

    <section class="mt-6 space-y-6" aria-labelledby="robot-statistics-title">
        @php
            $statisticsPeriodLabels = ['all' => 'Всё время', '7_days' => '7 дней', '30_days' => '30 дней', '3_months' => '3 месяца', '6_months' => '6 месяцев', 'current_year' => 'Текущий год', 'custom' => 'Свой период'];
            $periodFrom = $statistics['period']['from'] ? \Carbon\CarbonImmutable::parse($statistics['period']['from']) : null;
            $periodTo = \Carbon\CarbonImmutable::parse($statistics['period']['to']);
            $statisticsRangeLabel = $statisticsPeriod === 'all'
                ? 'За всё время'.($periodFrom ? ' • с '.$periodFrom->format('d.m.Y') : '')
                : ($periodFrom ? $periodFrom->format('d.m.Y').' — '.$periodTo->format('d.m.Y') : 'До '.$periodTo->format('d.m.Y'));
        @endphp
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#605bff]">Аналитика</p>
                <h2 id="robot-statistics-title" class="mt-1 text-xl font-extrabold text-[#030229]">Статистика робота</h2>
                <p class="mt-1 text-sm text-[#030229]/45">{{ $statistics['period']['has_data'] ? $statisticsRangeLabel : 'За выбранный период данных нет' }}</p>
            </div>
            <div class="w-full xl:max-w-3xl">
                <select wire:change="selectStatisticsPeriod($event.target.value)" class="form-input sm:hidden" aria-label="Период статистики">
                    @foreach($statisticsPeriodLabels as $value => $label)<option value="{{ $value }}" @selected($statisticsPeriod === $value)>{{ $label }}</option>@endforeach
                </select>
                @if($statisticsPeriod !== 'all')<button type="button" wire:click="resetStatisticsPeriod" class="mt-2 text-xs font-extrabold text-[#605bff] sm:hidden">Сбросить</button>@endif
                <div class="hidden flex-wrap justify-end gap-2 sm:flex">
                    @foreach($statisticsPeriodLabels as $value => $label)
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
            <article class="stat-card"><p class="stat-label">{{ $statisticsPeriod === 'all' ? 'Прибыль за всё время' : 'Прибыль за период' }}</p><p class="mt-2 text-2xl font-extrabold {{ $kpi['profit'] >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($kpi['profit'], 2, ',', ' ') }}</p></article>
            <article class="stat-card"><p class="stat-label">Доходность</p><p class="mt-2 text-2xl font-extrabold {{ $kpi['return_percent'] >= 0 ? 'text-[#605bff]' : 'text-red-700' }}">{{ number_format($kpi['return_percent'], 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Средний % в рабочий день</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($kpi['average_daily_percent'], 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Рабочих дней</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ $kpi['working_days'] }}</p></article>
            <article class="stat-card"><p class="stat-label">Прибыльных дней</p><p class="mt-2 text-2xl font-extrabold text-[#605bff]">{{ $kpi['profitable_days'] }}</p></article>
            <article class="stat-card"><p class="stat-label">Процент прибыльных дней</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($kpi['profitable_days_percent'], 1, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Максимальная просадка</p><p class="mt-2 text-2xl font-extrabold text-red-700">{{ number_format($kpi['max_drawdown'], 2, ',', ' ') }}</p><p class="mt-1 text-xs font-bold text-red-700/70">{{ number_format($kpi['max_drawdown_percent'], 3, ',', ' ') }}%</p></article>
            <article class="stat-card"><p class="stat-label">Profit Factor</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ $kpi['profit_factor_state'] === 'infinite' ? '∞' : ($kpi['profit_factor_state'] === 'undefined' ? '—' : number_format($kpi['profit_factor'], 3, ',', ' ')) }}</p><p class="mt-1 text-[10px] text-[#030229]/40">Прибыль {{ number_format($kpi['gross_profit'], 2, ',', ' ') }} / убыток {{ number_format($kpi['gross_loss'], 2, ',', ' ') }}</p></article>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @php($quality = $statistics['quality'])
            <article class="rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]">
                <h3 class="text-lg font-extrabold text-[#030229]">Качество торговли</h3>
                <dl class="mt-4 grid grid-cols-2 gap-x-5 gap-y-4 sm:grid-cols-3">
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
            </article>

            @php($last30 = $statistics['last_30'])
            @php($last30DaysTitle = $last30['days'] === 1 ? 'Последний 1 рабочий день' : 'Последние '.$last30['days'].' рабочих '.(in_array($last30['days'] % 10, [2, 3, 4], true) && !in_array($last30['days'] % 100, [12, 13, 14], true) ? 'дня' : 'дней'))
            <article class="rounded-xl bg-[#030229] p-5 text-white shadow-[0_6px_24px_rgba(3,2,41,0.12)]">
                <h3 class="text-lg font-extrabold">{{ $last30DaysTitle }}</h3>
                <dl class="mt-4 grid grid-cols-2 gap-x-5 gap-y-5 sm:grid-cols-3">
                    <div><dt class="text-[10px] font-bold uppercase tracking-wider text-white/50">Прибыль</dt><dd class="mt-1 text-lg font-extrabold">{{ number_format($last30['profit'], 2, ',', ' ') }}</dd></div>
                    <div><dt class="text-[10px] font-bold uppercase tracking-wider text-white/50">Средний результат</dt><dd class="mt-1 text-lg font-extrabold">{{ number_format($last30['average_result'], 2, ',', ' ') }}</dd></div>
                    <div><dt class="text-[10px] font-bold uppercase tracking-wider text-white/50">Средний % в день</dt><dd class="mt-1 text-lg font-extrabold">{{ number_format($last30['average_daily_percent'], 3, ',', ' ') }}%</dd></div>
                    <div><dt class="text-[10px] font-bold uppercase tracking-wider text-white/50">Прибыльные дни</dt><dd class="mt-1 text-lg font-extrabold">{{ $last30['profitable_days'] }}</dd></div>
                    <div><dt class="text-[10px] font-bold uppercase tracking-wider text-white/50">Убыточные дни</dt><dd class="mt-1 text-lg font-extrabold">{{ $last30['losing_days'] }}</dd></div>
                    <div><dt class="text-[10px] font-bold uppercase tracking-wider text-white/50">Процент прибыльных</dt><dd class="mt-1 text-lg font-extrabold">{{ number_format($last30['profitable_days_percent'], 1, ',', ' ') }}%</dd></div>
                </dl>
            </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            @foreach([['robot-growth-chart', 'Рост счёта', 'growth'], ['robot-daily-chart', 'Результат по дням', 'daily'], ['robot-monthly-chart', 'Прибыль по месяцам', 'monthly']] as [$chartId, $chartTitle, $chartKey])
                <article class="rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]"><h3 class="font-extrabold text-[#030229]">{{ $chartTitle }}</h3><div class="mt-4 h-64">@if(count($statistics['charts'][$chartKey]['values']))<canvas id="{{ $chartId }}"></canvas>@else<div class="grid h-full place-items-center rounded-lg bg-[#fafafb] px-4 text-center text-sm text-[#030229]/40">За выбранный период данных нет</div>@endif</div></article>
            @endforeach
        </div>
        <script id="robot-statistics-chart-data" type="application/json">@json($statistics['charts'])</script>
    </section>
</main>

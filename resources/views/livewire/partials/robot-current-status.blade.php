@php
    $compact = $compact ?? false;
    $today = \Carbon\CarbonImmutable::today();
    $currentPeriod = $periods
        ->first(fn ($period) => $period->starts_at->lte($today) && (! $period->ends_at || $period->ends_at->gte($today)));
    $isPaused = $currentPeriod?->status === \App\Models\RobotStatusPeriod::STATUS_PAUSED;
    $isStopped = in_array($currentPeriod?->status, [
        \App\Models\RobotStatusPeriod::STATUS_DIAGNOSTICS,
        \App\Models\RobotStatusPeriod::STATUS_MAINTENANCE,
    ], true);
    $label = $isPaused ? 'На паузе' : ($isStopped ? 'Остановлен' : 'Активен');
    $displayLabel = $compact && $isPaused ? 'Пауза' : $label;
    $icon = $isPaused ? '🟡' : ($isStopped ? '🔴' : '🟢');
    $badgeClass = $isPaused ? 'bg-amber-50 text-amber-800' : ($isStopped ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700');
@endphp

@if($compact)
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-extrabold {{ $badgeClass }}">{{ $displayLabel }} {{ $icon }}</span>
        @if($currentPeriod)
            <span class="text-[#030229]/45">с {{ $currentPeriod->starts_at->format('d.m.Y') }}@if($currentPeriod->ends_at) по {{ $currentPeriod->ends_at->format('d.m.Y') }}@endif</span>
        @endif
    </div>
@else
    <section class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white p-4 shadow-[0_6px_24px_rgba(3,2,41,0.05)] sm:mb-6 sm:p-5" aria-label="Текущий статус робота">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#030229]/40">Текущий статус</p>
            <p class="mt-1 text-lg font-extrabold text-[#030229]">{{ $label }} {{ $icon }}</p>
        </div>
        @if($currentPeriod)
            <div class="text-left text-sm sm:text-right">
                <p class="font-bold text-[#030229]">Начало: {{ $currentPeriod->starts_at->format('d.m.Y') }}</p>
                @if($currentPeriod->ends_at)<p class="mt-1 text-[#030229]/50">Окончание: {{ $currentPeriod->ends_at->format('d.m.Y') }}</p>@endif
            </div>
        @else
            <p class="text-sm text-[#030229]/45">Действующий период статуса не задан.</p>
        @endif
    </section>
@endif

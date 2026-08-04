<div class="min-w-0 rounded-xl bg-white p-4 shadow-[0_6px_24px_rgba(3,2,41,0.05)] sm:p-6">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-[10px] font-extrabold uppercase tracking-wider text-stone-400">Статистика</p><h2 class="mt-1 text-lg font-extrabold text-[#030229]">{{ $readOnly && !$robotId ? 'Общий календарь' : 'Календарь робота' }}</h2><p class="mt-1 text-sm text-stone-500">{{ $readOnly ? ($robotId ? 'Режим только для просмотра' : 'Сумма результатов доступных роботов по дням') : 'Выберите день для ручного ввода' }}</p></div>
        @if(!$readOnly)<span class="rounded-lg bg-[#605bff]/10 px-3 py-1.5 text-xs font-bold text-[#605bff]">{{ $account ? 'Счёт настроен' : 'Счёт не настроен' }}</span>@endif
    </div>
    <div class="mb-4 flex items-center justify-between rounded-lg bg-[#fafafb] p-2">
        <button wire:click="previousMonth" class="btn-secondary px-3" aria-label="Предыдущий месяц">←</button>
        <strong class="capitalize">{{ $monthLabel }}</strong>
        <button wire:click="nextMonth" class="btn-secondary px-3" aria-label="Следующий месяц">→</button>
    </div>
    <div class="overflow-x-auto pb-1"><div class="grid min-w-[600px] grid-cols-7 gap-2 text-center text-xs font-bold text-stone-400">
        @foreach(['Пн','Вт','Ср','Чт','Пт','Сб','Вс'] as $index => $weekday)<div class="py-2 {{ $index >= 5 ? 'text-amber-900' : '' }}">{{ $weekday }}</div>@endforeach
        @for($i = 0; $i < $leadingBlanks; $i++)<div></div>@endfor
        @foreach($days as $day)
            @php($result = $results->get($day->format('Y-m-d')))
            @php($robotsForDay = $robotBreakdown->get($day->format('Y-m-d'), collect()))
            @php($isLocked = !$readOnly && $trackingStartedAt && $day->format('Y-m-d') < $trackingStartedAt)
            <button wire:click="selectDate('{{ $day->format('Y-m-d') }}')" @disabled((!$readOnly && !$accountId) || $isLocked)
                class="group relative min-h-20 rounded-lg border p-2 text-left transition sm:min-h-24 {{ $selectedDate === $day->format('Y-m-d') ? 'border-[#605bff] ring-2 ring-[#605bff]/10' : 'border-[#030229]/8' }} {{ $isLocked ? 'cursor-not-allowed bg-[#030229]/5 opacity-60' : (($day->isToday() || $day->isWeekend()) ? 'bg-[#605bff]/5 hover:border-[#605bff]/25' : 'bg-white hover:border-[#605bff]/25') }}">
                <span class="text-xs font-extrabold {{ $isLocked ? 'text-stone-400' : 'text-stone-700' }}">{{ $day->day }}</span>
                @if($isLocked)<span class="mt-2 block text-[10px] font-bold text-stone-400">До начала учёта</span>@endif
                @if($result)
                    <span class="mt-2 block truncate text-xs font-extrabold {{ $result->amount >= 0 ? 'text-green-800' : 'text-red-700' }}">{{ $result->amount > 0 ? '+' : '' }}{{ number_format((float)$result->amount, 0, ',', ' ') }}</span>
                    @if($result->daily_percent !== null)
                        <span class="mt-0.5 block truncate text-[10px] font-bold {{ $result->daily_percent >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $result->daily_percent > 0 ? '+' : '' }}{{ number_format((float)$result->daily_percent, 3, ',', ' ') }}%</span>
                    @endif
                @endif
                @if($readOnly && $robotsForDay->isNotEmpty())
                    <span class="pointer-events-none absolute bottom-[calc(100%+0.5rem)] left-1/2 z-30 hidden w-56 -translate-x-1/2 rounded-xl bg-stone-950 p-3 text-left text-white shadow-xl group-hover:block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-white/60">{{ $day->format('d.m.Y') }}</span>
                        @foreach($robotsForDay as $robotResult)<span class="flex items-center justify-between gap-3 py-1 text-xs"><span class="truncate font-bold">{{ $robotResult->robot_name }}</span><span class="font-extrabold {{ $robotResult->amount < 0 ? 'text-red-300' : 'text-green-300' }}">{{ $robotResult->amount > 0 ? '+' : '' }}{{ number_format((float)$robotResult->amount, 2, ',', ' ') }}</span></span>@endforeach
                    </span>
                @endif
            </button>
        @endforeach
    </div></div>
    @if(session('calendar-status'))<p class="mt-4 rounded-xl bg-green-100 px-4 py-3 text-sm font-bold text-green-900">{{ session('calendar-status') }}</p>@endif
    @if(!$readOnly && $selectedDate && in_array(auth()->user()->role->value, ['admin', 'operator'], true))
        <div class="mt-5 rounded-2xl bg-stone-50 p-4">
            <div class="mb-4 flex items-center justify-between"><div><p class="form-label">История за день</p><strong>{{ \Carbon\CarbonImmutable::parse($selectedDate)->format('d.m.Y') }}</strong></div><span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-stone-500">{{ $dayResults->count() }} операций</span></div>
        <form wire:submit="save" class="mb-4 grid gap-4 rounded-xl bg-white p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <div><label class="form-label" for="amount">Сумма</label><input id="amount" wire:model="amount" class="form-input" inputmode="decimal" placeholder="Например, 125.50">@error('amount')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
            <div><label class="form-label" for="comment">Комментарий</label><input id="comment" wire:model="comment" class="form-input" placeholder="Необязательно"></div>
            <div class="flex gap-2"><button class="btn-primary">{{ $editingResultId ? 'Обновить' : 'Добавить' }}</button>@if($editingResultId)<button type="button" wire:click="selectDate('{{ $selectedDate }}')" class="btn-secondary">Отмена</button>@endif</div>
        </form>
            <div class="space-y-2">@forelse($dayResults as $entry)<div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white p-3"><div class="flex items-center gap-3"><span class="grid size-7 place-items-center rounded-lg bg-stone-100 text-xs font-extrabold">{{ $entry->sequence }}</span><div><strong class="{{ $entry->amount >= 0 ? 'text-green-800' : 'text-red-700' }}">{{ $entry->amount > 0 ? '+' : '' }}{{ number_format((float)$entry->amount, 2, ',', ' ') }}</strong>@if($entry->comment)<small class="ml-2 text-stone-500">{{ $entry->comment }}</small>@endif</div></div><div class="flex gap-2"><button type="button" wire:click="editResult({{ $entry->id }})" class="btn-secondary">Изменить</button><button type="button" wire:click="deleteResult({{ $entry->id }})" wire:confirm="Удалить эту операцию?" class="btn-secondary text-red-700">Удалить</button></div></div>@empty<p class="rounded-xl bg-white p-3 text-sm text-stone-500">Операций пока нет. Если поступлений не было, добавьте 0.</p>@endforelse</div>
        </div>
    @endif
</div>

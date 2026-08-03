<div class="rounded-2xl border border-stone-200 bg-white p-4 sm:p-5">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="font-extrabold">Календарь результатов</h2><p class="text-sm text-stone-500">Выберите день для ручного ввода</p></div>
        <select wire:model.live="accountId" class="form-input w-auto min-w-56">
            @forelse($accounts as $account)<option value="{{ $account->id }}">{{ $account->robot->name }} · {{ $account->name }}</option>@empty<option value="">Сначала добавьте счёт</option>@endforelse
        </select>
    </div>
    <div class="mb-4 flex items-center justify-between rounded-xl bg-stone-50 p-2">
        <button wire:click="previousMonth" class="btn-secondary px-3" aria-label="Предыдущий месяц">←</button>
        <strong class="capitalize">{{ $monthLabel }}</strong>
        <button wire:click="nextMonth" class="btn-secondary px-3" aria-label="Следующий месяц">→</button>
    </div>
    <div class="grid grid-cols-7 gap-1.5 text-center text-xs font-bold text-stone-400">
        @foreach(['Пн','Вт','Ср','Чт','Пт','Сб','Вс'] as $weekday)<div class="py-2">{{ $weekday }}</div>@endforeach
        @for($i = 0; $i < $leadingBlanks; $i++)<div></div>@endfor
        @foreach($days as $day)
            @php($result = $results->get($day->format('Y-m-d')))
            <button wire:click="selectDate('{{ $day->format('Y-m-d') }}')" @disabled(!$accountId)
                class="relative min-h-16 rounded-xl border p-1.5 text-left transition sm:min-h-20 {{ $selectedDate === $day->format('Y-m-d') ? 'border-green-900 ring-2 ring-green-900/15' : 'border-stone-200 hover:border-stone-400' }} {{ $day->isToday() ? 'bg-amber-50' : 'bg-white' }}">
                <span class="text-xs font-extrabold text-stone-700">{{ $day->day }}</span>
                @if($result)<span class="mt-2 block truncate text-xs font-extrabold {{ $result->amount >= 0 ? 'text-green-800' : 'text-red-700' }}">{{ $result->amount >= 0 ? '+' : '' }}{{ number_format((float)$result->amount, 0, ',', ' ') }}</span>@endif
            </button>
        @endforeach
    </div>
    @if(session('calendar-status'))<p class="mt-4 rounded-xl bg-green-100 px-4 py-3 text-sm font-bold text-green-900">{{ session('calendar-status') }}</p>@endif
    @if($selectedDate && in_array(auth()->user()->role->value, ['admin', 'operator'], true))
        <form wire:submit="save" class="mt-5 grid gap-4 rounded-2xl bg-stone-50 p-4 sm:grid-cols-[160px_1fr_auto] sm:items-end">
            <div><label class="form-label">Дата</label><p class="form-input bg-stone-100">{{ \Carbon\CarbonImmutable::parse($selectedDate)->format('d.m.Y') }}</p></div>
            <div><label class="form-label" for="amount">Результат</label><input id="amount" wire:model="amount" class="form-input" inputmode="decimal" placeholder="Например, 125.50">@error('amount')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
            <div class="flex gap-2"><button class="btn-primary">Сохранить</button>@if($results->has($selectedDate))<button type="button" wire:click="delete" wire:confirm="Удалить результат за этот день?" class="btn-secondary text-red-700">Удалить</button>@endif</div>
            <div class="sm:col-span-3"><label class="form-label" for="comment">Комментарий</label><textarea id="comment" wire:model="comment" class="form-input" rows="2" placeholder="Необязательное примечание"></textarea></div>
        </form>
    @endif
</div>

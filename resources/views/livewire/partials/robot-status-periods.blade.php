<section class="mb-6 rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-stone-400">Состояние робота</p>
            <h2 class="mt-1 text-lg font-extrabold text-[#030229]">Ремонт, диагностика и пауза</h2>
        </div>
        <div class="rounded-lg bg-[#605bff]/10 px-3 py-2 text-right">
            <p class="text-[10px] font-bold uppercase tracking-wide text-[#605bff]/70">Рабочих дней</p>
            <p class="text-xl font-extrabold text-[#605bff]">{{ $allTimeWorkingDays }}</p>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-bold text-stone-500">
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span>Работал</span>
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-red-500"></span>Ремонт / диагностика</span>
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-orange-400"></span>Пауза</span>
    </div>

    <p class="mb-4 text-sm text-[#030229]/55">День с записью результата, включая 0, считается рабочим. Дни ремонта, диагностики и паузы исключаются из расчёта и отмечаются в календаре.</p>

    @if(session('status_period_status'))
        <div class="mb-4 rounded-lg bg-[#605bff]/10 px-4 py-3 text-sm font-semibold text-[#605bff]">{{ session('status_period_status') }}</div>
    @endif

    @if(in_array(auth()->user()->role->value, ['admin', 'operator'], true))
        <form wire:submit="saveStatusPeriod" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="form-label">Статус
                <select wire:model="statusType" class="form-input">
                    <option value="diagnostics">Диагностика</option>
                    <option value="maintenance">Ремонт</option>
                    <option value="paused">Пауза</option>
                </select>
            </label>
            <label class="form-label">Начало
                <input wire:model="statusStartDate" type="date" class="form-input">
            </label>
            <label class="form-label">Окончание
                <input wire:model="statusEndDate" type="date" class="form-input">
                <span class="mt-1 block text-[11px] font-normal text-stone-400">Оставь пустым, если период ещё продолжается.</span>
            </label>
            <label class="form-label">Комментарий
                <input wire:model="statusComment" type="text" maxlength="1000" class="form-input" placeholder="Необязательно">
            </label>
            <div class="md:col-span-2 xl:col-span-4">
                <button class="btn-primary" type="submit">Добавить период</button>
            </div>
        </form>
    @endif

    <div class="mt-6 space-y-2 border-t border-stone-100 pt-5">
        @forelse($statusPeriods as $period)
            @php($isPause = $period->status === 'paused')
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-[#fafafb] p-3">
                <div class="flex items-start gap-3">
                    <span class="mt-1 size-2.5 shrink-0 rounded-full {{ $isPause ? 'bg-orange-400' : 'bg-red-500' }}"></span>
                    <div>
                        <p class="text-sm font-bold text-[#030229]">
                            @switch($period->status)
                                @case('diagnostics') Диагностика @break
                                @case('maintenance') Ремонт @break
                                @case('paused') Пауза @break
                                @default {{ $period->status }}
                            @endswitch
                        </p>
                        <p class="text-xs text-stone-400">
                            {{ $period->starts_at->format('d.m.Y') }} — {{ $period->ends_at?->format('d.m.Y') ?? 'по настоящее время' }}
                            @if($period->comment) · {{ $period->comment }} @endif
                        </p>
                    </div>
                </div>
                @if(in_array(auth()->user()->role->value, ['admin', 'operator'], true))
                    <button type="button" wire:click="deleteStatusPeriod({{ $period->id }})" wire:confirm="Удалить период простоя?" class="text-xs font-bold text-red-600 hover:underline">Удалить</button>
                @endif
            </div>
        @empty
            <p class="rounded-lg bg-[#fafafb] p-4 text-sm text-stone-500">Периодов ремонта, диагностики или паузы пока нет.</p>
        @endforelse
    </div>
</section>

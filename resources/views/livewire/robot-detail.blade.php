<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4"><div><a href="{{ route('robots.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-bold text-[#605bff] transition hover:text-[#4f4ae8]">← Все роботы</a><div class="flex flex-wrap items-center gap-3"><h1 class="text-3xl font-extrabold tracking-tight text-[#030229]">{{ $robot->name }}</h1><span class="rounded-lg px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide {{ $robot->is_active ? 'bg-[#605bff]/10 text-[#605bff]' : 'bg-stone-200 text-stone-600' }}">{{ $robot->is_active ? 'Активен' : 'Отключён' }}</span></div><p class="mt-1.5 text-sm text-[#030229]/45">{{ $robot->description ?: 'Ручной учёт показателей торгового робота' }}</p></div><div class="rounded-[10px] bg-white px-4 py-3 text-right"><p class="text-[10px] font-extrabold uppercase tracking-wider text-[#030229]/40">Период</p><p class="mt-1 text-sm font-extrabold capitalize text-[#030229]">{{ \Carbon\CarbonImmutable::createFromFormat('Y-m', $calendarMonth)->translatedFormat('F Y') }}</p></div></div>
    <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="relative overflow-hidden rounded-[10px] bg-[#605bff] p-5 text-white"><span class="absolute -right-5 -top-5 size-24 rounded-full bg-white/10"></span><p class="text-xs font-bold uppercase tracking-wider text-white/60">Итого за месяц</p><p class="mt-3 text-3xl font-extrabold tracking-tight">{{ number_format($monthProfit, 2, ',', ' ') }}</p><p class="mt-2 text-xs text-white/60">Финансовый результат</p></article>
        <article class="rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]"><div class="mb-4 size-10 rounded-full bg-blue-50"></div><p class="text-xs font-bold uppercase tracking-wider text-stone-400">% за месяц</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($monthPercent, 3, ',', ' ') }}%</p></article>
        <article class="rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]"><div class="mb-4 size-10 rounded-full bg-amber-50"></div><p class="text-xs font-bold uppercase tracking-wider text-stone-400">% за день</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($dayPercent, 3, ',', ' ') }}%</p></article>
        <article class="rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]"><div class="mb-4 size-10 rounded-full bg-violet-50"></div><p class="text-xs font-bold uppercase tracking-wider text-stone-400">Среднее в день</p><p class="mt-2 text-2xl font-extrabold text-[#030229]">{{ number_format($dailyAverage, 2, ',', ' ') }}</p></article>
    </section>
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <livewire:result-calendar :robot-id="$robot->id" :read-only="auth()->user()->role->value === 'viewer'" :key="'calendar-'.$robot->id.'-'.$accountRevision" />
        @if(in_array(auth()->user()->role->value, ['admin', 'operator'], true))<form wire:submit="saveAccount" class="h-fit rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)] xl:sticky xl:top-5"><div class="mb-5"><p class="text-[10px] font-extrabold uppercase tracking-wider text-stone-400">Настройки</p><h2 class="mt-1 text-lg font-extrabold text-[#030229]">Торговый счёт</h2></div>@if(session('account-status'))<p class="mb-4 rounded-lg bg-[#605bff]/10 p-3 text-sm font-bold text-[#605bff]">{{ session('account-status') }}</p>@endif
            <div class="space-y-4"><div><label class="form-label">Название</label><input wire:model="name" class="form-input" placeholder="Основной счёт">@error('name')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div><div><label class="form-label">Брокер</label><input wire:model="broker" class="form-input"></div><div class="grid grid-cols-2 gap-3"><div><label class="form-label">Платформа</label><select wire:model="platform" class="form-input"><option value="manual">Ручной</option><option value="mt4">MT4</option><option value="mt5">MT5</option></select></div><div><label class="form-label">Валюта</label><input wire:model="currency" maxlength="3" class="form-input"></div></div><div><label class="form-label">Логин счёта</label><input wire:model="externalLogin" class="form-input"></div><div><label class="form-label">Депозит</label><input wire:model="initialDeposit" inputmode="decimal" class="form-input"></div><label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="isActive" class="size-4 rounded"> Счёт активен</label></div>
            <button class="btn-primary mt-5 w-full">Сохранить счёт</button>
        </form>
        @else
            <aside class="h-fit rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)] xl:sticky xl:top-5">
                <div class="mb-4"><p class="text-[10px] font-extrabold uppercase tracking-wider text-stone-400">История</p><h2 class="mt-1 text-lg font-extrabold text-[#030229]">Последние записи</h2></div>
                <div class="max-h-[42rem] space-y-3 overflow-y-auto pr-2">
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
        @endif
    </div>
</main>

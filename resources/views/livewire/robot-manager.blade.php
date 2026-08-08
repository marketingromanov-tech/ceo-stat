<main class="mx-auto max-w-7xl px-3 py-5 sm:px-6 sm:py-7 lg:px-8">
    <div class="mb-6"><h1 class="text-2xl font-extrabold">{{ auth()->user()->role->value === 'viewer' ? 'Мои роботы' : 'Торговые роботы' }}</h1><p class="mt-1 text-sm text-stone-500">{{ auth()->user()->role->value === 'viewer' ? 'Доступные для просмотра роботы' : 'Создание, описание и включение роботов' }}</p></div>
    <div class="grid gap-6 {{ auth()->user()->role->value === 'viewer' ? '' : 'lg:grid-cols-[360px_1fr]' }}">
        @if(auth()->user()->role->value !== 'viewer')
        <form wire:submit="save" class="h-fit rounded-[10px] bg-white p-5">
            <h2 class="mb-4 font-extrabold">{{ $editingId ? 'Редактировать робота' : 'Новый робот' }}</h2>
            <label class="form-label" for="robot-name">Название</label><input id="robot-name" wire:model="name" class="form-input">@error('name')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            <label class="form-label mt-4" for="robot-description">Описание</label><textarea id="robot-description" wire:model="description" class="form-input" rows="4"></textarea>
            <label class="mt-4 flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="isActive" class="size-4 rounded"> Активен</label>
            <div class="mt-5 flex gap-2"><button class="btn-primary">Сохранить</button>@if($editingId)<button type="button" wire:click="cancel" class="btn-secondary">Отмена</button>@endif</div>
        </form>
        @endif
        <section class="min-w-0">
            @if(session('status'))<p class="mb-4 rounded-lg bg-[#605bff]/10 px-4 py-3 text-sm font-bold text-[#605bff]">{{ session('status') }}</p>@endif
            <div class="mb-4 space-y-3 rounded-xl bg-white p-4 shadow-[0_6px_24px_rgba(3,2,41,0.05)] sm:p-5">
                <div class="flex flex-wrap gap-2">
                    @foreach(['all' => 'Все', 'active' => 'Активные', 'paused' => 'На паузе', 'stopped' => 'Остановленные'] as $filter => $label)
                        <button type="button" wire:click="selectStatusFilter('{{ $filter }}')" class="rounded-lg px-3 py-2 text-xs font-extrabold transition {{ $statusFilter === $filter ? 'bg-[#605bff] text-white' : 'bg-[#030229]/5 text-[#030229]/55 hover:bg-[#605bff]/10 hover:text-[#605bff]' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="form-label">Период
                        <select wire:change="selectStatisticsPeriod($event.target.value)" class="form-input">
                            @foreach(['all' => 'Всё время', '7_days' => '7 дней', '30_days' => '30 дней', '3_months' => '3 месяца', '6_months' => '6 месяцев', 'current_year' => 'Текущий год'] as $period => $label)<option value="{{ $period }}" @selected($statisticsPeriod === $period)>{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <label class="form-label">Сортировка
                        <select wire:change="selectSort($event.target.value)" class="form-input">
                            <option value="name" @selected($sortBy === 'name')>По названию</option>
                            <option value="profit" @selected($sortBy === 'profit')>По прибыли</option>
                            <option value="return" @selected($sortBy === 'return')>По доходности</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                @forelse($cards as $card)
                    @php($robot = $card['robot'])
                    @php($lastResult = $card['last_result'])
                    <article class="flex min-w-0 flex-col rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0"><h3 class="truncate text-lg font-extrabold">{{ $robot->name }}</h3><div class="mt-2">@include('livewire.partials.robot-current-status', ['periods' => $robot->statusPeriods, 'compact' => true])</div></div>
                            <p class="rounded-lg bg-[#030229]/5 px-2.5 py-1.5 text-[10px] font-extrabold uppercase tracking-wide text-[#030229]/45">{{ $statisticsPeriodLabel }}</p>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-3">
                            <div class="col-span-2 rounded-lg bg-[#fafafb] p-3"><dt class="text-[10px] font-bold uppercase tracking-wide text-[#030229]/40">Торговый счёт</dt><dd class="mt-1 truncate text-sm font-extrabold">{{ $robot->account?->name ?: 'Не настроен' }}</dd></div>
                            <div class="rounded-lg bg-[#fafafb] p-3"><dt class="text-[10px] font-bold uppercase tracking-wide text-[#030229]/40">Депозит</dt><dd class="mt-1 font-extrabold">{{ number_format((float) ($robot->account?->initial_deposit ?? 0), 2, ',', ' ') }}</dd></div>
                            <div class="rounded-lg bg-[#fafafb] p-3"><dt class="text-[10px] font-bold uppercase tracking-wide text-[#030229]/40">Прибыль</dt><dd class="mt-1 font-extrabold {{ $card['profit'] < 0 ? 'text-red-700' : 'text-[#605bff]' }}">{{ number_format($card['profit'], 2, ',', ' ') }}</dd></div>
                            <div class="rounded-lg bg-[#fafafb] p-3"><dt class="text-[10px] font-bold uppercase tracking-wide text-[#030229]/40">Доходность</dt><dd class="mt-1 font-extrabold {{ $card['return_percent'] < 0 ? 'text-red-700' : 'text-[#605bff]' }}">{{ number_format($card['return_percent'], 3, ',', ' ') }}%</dd></div>
                            <div class="rounded-lg bg-[#fafafb] p-3"><dt class="text-[10px] font-bold uppercase tracking-wide text-[#030229]/40">Последний результат</dt><dd class="mt-1 font-extrabold {{ (float) ($lastResult?->amount ?? 0) < 0 ? 'text-red-700' : 'text-[#030229]' }}">{{ $lastResult ? number_format((float) $lastResult->amount, 2, ',', ' ') : '—' }}</dd><p class="mt-1 text-xs text-[#030229]/40">{{ $lastResult?->traded_at?->format('d.m.Y') ?? 'Записей нет' }}</p></div>
                        </dl>

                        <div class="mt-auto grid grid-cols-2 gap-2 pt-5">
                            <a href="{{ route('robots.show', $robot) }}" class="btn-primary text-center">Открыть робота</a>
                            @if(auth()->user()->role->value !== 'viewer')<button type="button" wire:click="$dispatch('open-quick-result-entry', { robotId: {{ $robot->id }} })" class="btn-secondary">Добавить результат</button>@endif
                        </div>
                        @if(auth()->user()->role->value !== 'viewer')
                            <div class="mt-3 flex flex-wrap gap-3 border-t border-[#030229]/5 pt-3 text-xs font-bold"><button wire:click="edit({{ $robot->id }})" class="text-[#605bff] hover:underline">Изменить</button><button wire:click="toggle({{ $robot->id }})" class="text-[#030229]/45 hover:text-[#605bff]">{{ $robot->is_active ? 'Отключить' : 'Включить' }}</button>@if(auth()->user()->role->value === 'admin')<button wire:click="confirmDelete({{ $robot->id }})" class="text-red-700 hover:underline">Удалить</button>@endif</div>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl bg-white p-5 text-sm text-stone-500 shadow-[0_6px_24px_rgba(3,2,41,0.05)] xl:col-span-2">Роботы с выбранным статусом не найдены.</p>
                @endforelse
            </div>
        </section>
    </div>
    @if($deletingId)
        @php($deletingRobot = $robots->firstWhere('id', $deletingId))
        <div class="fixed inset-0 z-50 grid place-items-center bg-stone-950/60 p-4" wire:keydown.escape="cancelDelete">
            <form wire:submit="delete" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-xl font-extrabold">Удаление робота</h2>
                <p class="mt-2 text-sm text-stone-600">Робот «{{ $deletingRobot?->name }}», его счёт и вся история начислений будут удалены безвозвратно.
                </p>
                <div class="mt-5"><label class="form-label" for="delete-password">Пароль текущего пользователя</label><input id="delete-password" type="password" wire:model="deletePassword" class="form-input" autocomplete="current-password" autofocus>@error('deletePassword')<p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p>@enderror</div>
                <div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="cancelDelete" class="btn-secondary">Отмена</button><button class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-red-800">Удалить навсегда</button></div>
            </form>
        </div>
    @endif
</main>

<main class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
    <div class="mb-6"><h1 class="text-2xl font-extrabold">Торговые счета</h1><p class="mt-1 text-sm text-stone-500">Привязка счетов к роботам и контроль баланса</p></div>
    <div class="grid gap-6 xl:grid-cols-[390px_1fr]">
        <form wire:submit="save" class="h-fit rounded-2xl border border-stone-200 bg-white p-5">
            <h2 class="mb-4 font-extrabold">{{ $editingId ? 'Редактировать счёт' : 'Новый счёт' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                <div><label class="form-label" for="account-robot">Робот</label><select id="account-robot" wire:model="robotId" class="form-input"><option value="">Выберите робота</option>@foreach($robots as $robot)<option value="{{ $robot->id }}">{{ $robot->name }}</option>@endforeach</select>@error('robotId')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="form-label" for="account-name">Название счёта</label><input id="account-name" wire:model="name" class="form-input">@error('name')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="form-label" for="broker">Брокер</label><input id="broker" wire:model="broker" class="form-input"></div>
                <div><label class="form-label" for="platform">Платформа</label><select id="platform" wire:model="platform" class="form-input"><option value="manual">Ручной ввод</option><option value="mt4">MetaTrader 4</option><option value="mt5">MetaTrader 5</option></select></div>
                <div><label class="form-label" for="external-login">Логин счёта</label><input id="external-login" wire:model="externalLogin" class="form-input"></div>
                <div><label class="form-label" for="currency">Валюта</label><input id="currency" wire:model="currency" maxlength="3" class="form-input"></div>
                <div><label class="form-label" for="initial-deposit">Начальный депозит</label><input id="initial-deposit" wire:model="initialDeposit" inputmode="decimal" class="form-input"></div>
                <div><label class="form-label" for="current-balance">Текущий баланс</label><input id="current-balance" wire:model="currentBalance" inputmode="decimal" class="form-input"></div>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="isActive" class="size-4 rounded"> Активен</label>
            <div class="mt-5 flex gap-2"><button class="btn-primary" @disabled($robots->isEmpty())>Сохранить</button>@if($editingId)<button type="button" wire:click="cancel" class="btn-secondary">Отмена</button>@endif</div>
            @if($robots->isEmpty())<p class="mt-3 text-xs text-amber-700">Сначала создайте торгового робота.</p>@endif
        </form>
        <section class="rounded-2xl border border-stone-200 bg-white p-5">
            @if(session('status'))<p class="mb-4 rounded-xl bg-green-100 px-4 py-3 text-sm font-bold text-green-900">{{ session('status') }}</p>@endif
            <div class="overflow-x-auto"><table class="w-full min-w-175 text-left text-sm"><thead><tr class="border-b border-stone-200 text-xs uppercase tracking-wider text-stone-500"><th class="px-3 py-3">Счёт</th><th class="px-3 py-3">Робот</th><th class="px-3 py-3">Платформа</th><th class="px-3 py-3 text-right">Депозит</th><th class="px-3 py-3 text-right">Баланс</th><th class="px-3 py-3"></th></tr></thead><tbody>@forelse($accounts as $account)<tr class="border-b border-stone-100"><td class="px-3 py-4"><strong>{{ $account->name }}</strong><small class="block text-stone-500">{{ $account->broker ?: 'Брокер не указан' }} · {{ $account->currency }}</small></td><td class="px-3 py-4">{{ $account->robot->name }}</td><td class="px-3 py-4 uppercase">{{ $account->platform }}</td><td class="px-3 py-4 text-right font-bold">{{ number_format((float)$account->initial_deposit, 2, ',', ' ') }}</td><td class="px-3 py-4 text-right font-bold">{{ number_format((float)$account->current_balance, 2, ',', ' ') }}</td><td class="px-3 py-4"><div class="flex justify-end gap-2"><button wire:click="edit({{ $account->id }})" class="btn-secondary">Изменить</button><button wire:click="toggle({{ $account->id }})" class="btn-secondary">{{ $account->is_active ? 'Отключить' : 'Включить' }}</button></div></td></tr>@empty<tr><td colspan="6" class="px-3 py-8 text-center text-stone-500">Счетов пока нет.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
</main>

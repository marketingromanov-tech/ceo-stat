<section class="mb-6 rounded-xl bg-white p-5 shadow-[0_6px_24px_rgba(3,2,41,0.05)]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-stone-400">Финансовый учёт</p>
            <h2 class="mt-1 text-lg font-extrabold text-[#030229]">Финансы</h2>
        </div>

        <div class="text-right">
            <p class="text-xs text-stone-400">Текущий капитал</p>
            <p class="text-2xl font-extrabold text-[#605bff]">
                {{ number_format($financeSummary['current_balance'], 2, ',', ' ') }}
                <span class="text-sm">{{ $robot->account?->currency ?? 'USD' }}</span>
            </p>
        </div>
    </div>

    @if(session('finance_status'))
        <div class="mb-4 rounded-lg bg-[#605bff]/10 px-4 py-3 text-sm font-semibold text-[#605bff]">
            {{ session('finance_status') }}
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-[#fafafb] p-4"><p class="text-xs text-stone-400">Стартовый депозит</p><p class="mt-1 text-xl font-extrabold text-[#030229]">{{ number_format($financeSummary['initial_deposit'], 2, ',', ' ') }}</p></div>
        <div class="rounded-xl bg-[#fafafb] p-4"><p class="text-xs text-stone-400">Торговый результат</p><p class="mt-1 text-xl font-extrabold text-[#030229]">{{ number_format($financeSummary['trading_profit'], 2, ',', ' ') }}</p></div>
        <div class="rounded-xl bg-[#fafafb] p-4"><p class="text-xs text-stone-400">Пополнения</p><p class="mt-1 text-xl font-extrabold text-[#030229]">+{{ number_format($financeSummary['deposits'], 2, ',', ' ') }}</p></div>
        <div class="rounded-xl bg-[#fafafb] p-4"><p class="text-xs text-stone-400">Выводы</p><p class="mt-1 text-xl font-extrabold text-[#030229]">-{{ number_format($financeSummary['withdrawals'], 2, ',', ' ') }}</p></div>
        <div class="rounded-xl bg-[#fafafb] p-4"><p class="text-xs text-stone-400">Комиссии</p><p class="mt-1 text-xl font-extrabold text-[#030229]">-{{ number_format($financeSummary['commissions'], 2, ',', ' ') }}</p></div>
        <div class="rounded-xl bg-[#fafafb] p-4"><p class="text-xs text-stone-400">Прочие расходы</p><p class="mt-1 text-xl font-extrabold text-[#030229]">-{{ number_format($financeSummary['expenses'], 2, ',', ' ') }}</p></div>
    </div>

    @if(in_array(auth()->user()->role->value, ['admin', 'operator'], true))
        <form wire:submit="saveFinancialOperation" class="mt-6 border-t border-stone-100 pt-5">
            <h3 class="mb-4 font-extrabold text-[#030229]">Добавить операцию</h3>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="form-label">Тип
                    <select wire:model="financeType" class="form-input">
                        <option value="deposit">Пополнение</option>
                        <option value="withdrawal">Вывод</option>
                        <option value="commission">Комиссия</option>
                        <option value="expense">Прочий расход</option>
                        <option value="adjustment">Корректировка</option>
                    </select>
                </label>
                <label class="form-label">Сумма
                    <input wire:model="financeAmount" type="number" step="0.01" min="0.01" class="form-input">
                    @error('financeAmount')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="form-label">Дата
                    <input wire:model="financeDate" type="date" class="form-input">
                    @error('financeDate')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="form-label">Комментарий
                    <input wire:model="financeComment" type="text" maxlength="1000" class="form-input" placeholder="Необязательно">
                </label>
            </div>
            <button class="btn-primary mt-4" type="submit">Добавить операцию</button>
        </form>
    @endif

    <div class="mt-6 border-t border-stone-100 pt-5">
        <h3 class="font-extrabold text-[#030229]">Журнал операций</h3>
        <div class="mt-3 space-y-2">
            @forelse($financialOperations as $operation)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-[#fafafb] p-3">
                    <div>
                        <p class="text-sm font-bold text-[#030229]">
                            @switch($operation->type)
                                @case('deposit') Пополнение @break
                                @case('withdrawal') Вывод @break
                                @case('commission') Комиссия @break
                                @case('expense') Прочий расход @break
                                @case('adjustment') Корректировка @break
                                @default {{ $operation->type }}
                            @endswitch
                        </p>
                        <p class="text-xs text-stone-400">
                            {{ $operation->operation_date->format('d.m.Y') }}
                            @if($operation->comment) · {{ $operation->comment }} @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <p class="font-extrabold text-[#030229]">
                            {{ in_array($operation->type, ['withdrawal','commission','expense'], true) ? '-' : '+' }}
                            {{ number_format((float) $operation->amount, 2, ',', ' ') }} {{ $operation->currency }}
                        </p>
                        @if(in_array(auth()->user()->role->value, ['admin', 'operator'], true))
                            <button type="button" wire:click="deleteFinancialOperation({{ $operation->id }})" wire:confirm="Удалить финансовую операцию?" class="text-xs font-bold text-red-600 hover:underline">Удалить</button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="rounded-lg bg-[#fafafb] p-4 text-sm text-stone-500">Финансовых операций пока нет.</p>
            @endforelse
        </div>
    </div>
</section>

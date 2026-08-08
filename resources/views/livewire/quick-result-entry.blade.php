<div>
    <button type="button" wire:click="open" class="fixed bottom-[calc(5rem+env(safe-area-inset-bottom))] right-5 z-40 grid size-14 place-items-center rounded-full bg-[#605bff] text-3xl font-light leading-none text-white shadow-[0_12px_30px_rgba(96,91,255,.3)] transition duration-200 hover:scale-105 hover:bg-[#4f4ae8] active:scale-95 lg:bottom-8 lg:right-8" aria-label="Быстрая запись результата">
        <span aria-hidden="true">+</span>
    </button>

    @if(session('quick-result-status'))
        <div class="fixed right-4 top-4 z-[70] rounded-xl bg-[#030229] px-4 py-3 text-sm font-extrabold text-white shadow-2xl" role="status">{{ session('quick-result-status') }}</div>
    @endif

    @if($isOpen)
        <div class="fixed inset-0 z-[60] grid place-items-center bg-[#030229]/65 p-3 backdrop-blur-[2px] sm:p-4" wire:keydown.escape="close">
            <button type="button" wire:click="close" class="absolute inset-0 cursor-default" aria-label="Закрыть окно"></button>
            <form wire:submit="save" x-init="$nextTick(() => $refs.{{ $robotId ? 'amount' : 'robot' }}?.focus())" class="relative z-10 w-full max-w-lg rounded-xl bg-white p-5 shadow-2xl sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-[10px] font-extrabold uppercase tracking-wider text-[#605bff]">Результат торговли</p><h2 class="mt-1 text-xl font-extrabold text-[#030229]">Быстрая запись</h2></div>
                    <button type="button" wire:click="close" class="grid size-9 place-items-center rounded-lg bg-[#030229]/5 text-lg font-bold text-[#030229]/50 transition hover:bg-[#030229]/10" aria-label="Закрыть">×</button>
                </div>

                <div class="mt-5 space-y-4">
                    <div><label class="form-label">Робот</label><select wire:model="robotId" x-ref="robot" x-on:change="$nextTick(() => $refs.amount?.focus())" class="form-input"><option value="">Выберите робота</option>@foreach($robots as $robot)<option value="{{ $robot->id }}">{{ $robot->name }}{{ $robot->account ? '' : ' — счёт не настроен' }}</option>@endforeach</select>@error('robotId')<p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="form-label">Дата</label><input type="date" wire:model="resultDate" max="{{ now()->format('Y-m-d') }}" class="form-input">@error('resultDate')<p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label">Результат</label><input wire:model="amount" x-ref="amount" inputmode="decimal" class="form-input" placeholder="Например, -9,03">@error('amount')<p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="form-label">Комментарий</label><textarea wire:model="comment" rows="3" maxlength="2000" class="form-input resize-y" placeholder="Необязательно"></textarea>@error('comment')<p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p>@enderror</div>
                </div>

                @if($robots->isEmpty())<p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm font-bold text-amber-800">Нет доступных роботов.</p>@endif
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><button type="button" wire:click="close" class="btn-secondary">Отмена</button><button class="btn-primary" wire:loading.attr="disabled" wire:target="save" @disabled($robots->isEmpty())>Сохранить</button></div>
            </form>
        </div>
    @endif
</div>

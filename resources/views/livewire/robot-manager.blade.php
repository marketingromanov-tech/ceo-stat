<main class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
    <div class="mb-6"><h1 class="text-2xl font-extrabold">Торговые роботы</h1><p class="mt-1 text-sm text-stone-500">Создание, описание и включение роботов</p></div>
    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <form wire:submit="save" class="h-fit rounded-2xl border border-stone-200 bg-white p-5">
            <h2 class="mb-4 font-extrabold">{{ $editingId ? 'Редактировать робота' : 'Новый робот' }}</h2>
            <label class="form-label" for="robot-name">Название</label><input id="robot-name" wire:model="name" class="form-input">@error('name')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            <label class="form-label mt-4" for="robot-description">Описание</label><textarea id="robot-description" wire:model="description" class="form-input" rows="4"></textarea>
            <label class="mt-4 flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="isActive" class="size-4 rounded"> Активен</label>
            <div class="mt-5 flex gap-2"><button class="btn-primary">Сохранить</button>@if($editingId)<button type="button" wire:click="cancel" class="btn-secondary">Отмена</button>@endif</div>
        </form>
        <section class="rounded-2xl border border-stone-200 bg-white p-5">
            @if(session('status'))<p class="mb-4 rounded-xl bg-green-100 px-4 py-3 text-sm font-bold text-green-900">{{ session('status') }}</p>@endif
            <div class="space-y-3">@forelse($robots as $robot)<article class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-stone-200 p-4"><div><div class="flex items-center gap-2"><h3 class="font-extrabold">{{ $robot->name }}</h3><span class="rounded-full px-2 py-1 text-xs font-bold {{ $robot->is_active ? 'bg-green-100 text-green-900' : 'bg-stone-100 text-stone-500' }}">{{ $robot->is_active ? 'Активен' : 'Отключён' }}</span></div><p class="mt-1 text-sm text-stone-500">{{ $robot->description ?: 'Без описания' }} · {{ $robot->account?->name ?: 'Счёт не настроен' }} · {{ $robot->tracking_started_at ? 'Учёт с '.$robot->tracking_started_at->format('d.m.Y') : 'Без ограничения по дате' }}</p></div><div class="flex flex-wrap gap-2"><a href="{{ route('robots.show', $robot) }}" class="btn-primary">Открыть</a><button wire:click="edit({{ $robot->id }})" class="btn-secondary">Изменить</button><button wire:click="toggle({{ $robot->id }})" class="btn-secondary">{{ $robot->is_active ? 'Отключить' : 'Включить' }}</button>@if(auth()->user()->role->value === 'admin')<button wire:click="confirmDelete({{ $robot->id }})" class="btn-secondary text-red-700">Удалить</button>@endif</div></article>@empty<p class="rounded-xl bg-stone-50 p-5 text-sm text-stone-500">Роботов пока нет.</p>@endforelse</div>
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

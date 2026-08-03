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
            <div class="space-y-3">@forelse($robots as $robot)<article class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-stone-200 p-4"><div><div class="flex items-center gap-2"><h3 class="font-extrabold">{{ $robot->name }}</h3><span class="rounded-full px-2 py-1 text-xs font-bold {{ $robot->is_active ? 'bg-green-100 text-green-900' : 'bg-stone-100 text-stone-500' }}">{{ $robot->is_active ? 'Активен' : 'Отключён' }}</span></div><p class="mt-1 text-sm text-stone-500">{{ $robot->description ?: 'Без описания' }} · {{ $robot->account?->name ?: 'Счёт не настроен' }} · {{ $robot->tracking_started_at ? 'Учёт с '.$robot->tracking_started_at->format('d.m.Y') : 'Без ограничения по дате' }}</p></div><div class="flex gap-2"><a href="{{ route('robots.show', $robot) }}" class="btn-primary">Открыть</a><button wire:click="edit({{ $robot->id }})" class="btn-secondary">Изменить</button><button wire:click="toggle({{ $robot->id }})" class="btn-secondary">{{ $robot->is_active ? 'Отключить' : 'Включить' }}</button></div></article>@empty<p class="rounded-xl bg-stone-50 p-5 text-sm text-stone-500">Роботов пока нет.</p>@endforelse</div>
        </section>
    </div>
</main>

<x-layouts.app title="Вход — CEO Stat">
    <main class="grid min-h-screen place-items-center px-5 py-12">
        <section class="w-full max-w-md rounded-3xl border border-stone-200 bg-white p-8 shadow-sm">
            <div class="mb-8 flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-2xl bg-green-950 text-lg font-extrabold text-white">CS</span>
                <div><h1 class="text-xl font-extrabold">CEO Stat</h1><p class="text-sm text-stone-500">Статистика торговых роботов</p></div>
            </div>
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf
                <label class="block"><span class="mb-2 block text-sm font-semibold">Электронная почта</span><input class="w-full rounded-xl border border-stone-300 px-4 py-3 outline-none focus:border-green-800" type="email" name="email" value="{{ old('email') }}" required autofocus></label>
                <label class="block"><span class="mb-2 block text-sm font-semibold">Пароль</span><input class="w-full rounded-xl border border-stone-300 px-4 py-3 outline-none focus:border-green-800" type="password" name="password" required></label>
                @error('email')<p class="text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                <label class="flex items-center gap-2 text-sm text-stone-600"><input type="checkbox" name="remember" value="1"> Запомнить меня</label>
                <button class="w-full rounded-xl bg-green-950 px-5 py-3 font-bold text-white transition hover:bg-green-900">Войти</button>
            </form>
        </section>
    </main>
</x-layouts.app>

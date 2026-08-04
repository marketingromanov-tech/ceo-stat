<x-layouts.app title="Вход — CEO Stat">
    <main class="grid min-h-screen place-items-center px-5 py-12">
        <section class="w-full max-w-md rounded-[10px] bg-white p-8 shadow-[0_12px_40px_rgba(3,2,41,0.08)]">
            <div class="mb-8 flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-full bg-[#605bff] text-lg font-extrabold text-white">CS</span>
                <div><h1 class="text-xl font-extrabold">CEO Stat</h1><p class="text-sm text-stone-500">Статистика торговых роботов</p></div>
            </div>
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf
                <label class="block"><span class="mb-2 block text-sm font-semibold">Электронная почта</span><input class="form-input px-4 py-3" type="email" name="email" value="{{ old('email') }}" required autofocus></label>
                <label class="block"><span class="mb-2 block text-sm font-semibold">Пароль</span><input class="form-input px-4 py-3" type="password" name="password" required></label>
                @error('email')<p class="text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                <label class="flex items-center gap-2 text-sm text-stone-600"><input type="checkbox" name="remember" value="1"> Запомнить меня</label>
                <button class="btn-primary w-full py-3">Войти</button>
            </form>
        </section>
    </main>
</x-layouts.app>

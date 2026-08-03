# CEO Stat

Закрытый кабинет статистики торговых роботов.

## Стек

- Laravel 12
- Livewire 4
- Tailwind CSS 4
- MySQL 8
- Laravel Sanctum (будущий API MT4/MT5)

## Первый запуск

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan serve
```

Перед публикацией измените пароль демонстрационного администратора из `DatabaseSeeder`.

## Реализовано в первой версии

- защищённый вход и выход;
- роли `admin`, `operator`, `viewer`;
- модели и миграции роботов, торговых счетов и дневных результатов;
- защита от дублирования записей будущего MT4/MT5 API;
- журнал аудита;
- стартовый адаптивный экран статистики.

## Следующий этап

- управление сотрудниками, роботами и счетами;
- Livewire-календарь ручного ввода;
- детальные графики и отчёты;
- Sanctum API для советников MT4/MT5.

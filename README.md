# CEO Stat

Закрытый кабинет статистики торговых роботов.

## Стек

- Laravel 12
- Livewire 4
- Tailwind CSS 4
- MySQL 8
- Laravel Sanctum (будущий API MT4/MT5)

## Первый запуск

### Docker Desktop (рекомендуется для Windows)

```bash
git clone https://github.com/marketingromanov-tech/ceo-stat.git
cd ceo-stat
git switch agent/calendar-robots-accounts
docker compose up --build -d
```

После запуска откройте `http://localhost:8080`.

Демонстрационный вход: `admin@ceostat.local` / `ChangeMe123!`.

Остановить проект:

```bash
docker compose down
```

Полностью удалить локальную базу и начать заново:

```bash
docker compose down -v
```

### Без Docker

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

## Управление статистикой

- `/` — общий календарь с суммой результатов всех роботов;
- `/robots` — список и настройка торговых роботов;
- `/robots/{robot}` — счёт, показатели и календарь конкретного робота;
- каждому роботу соответствует ровно один торговый счёт;
- ручной результат уникален для сочетания счёта и торгового дня;
- роли `admin` и `operator` могут изменять данные, `viewer` работает в режиме просмотра.

## Следующий этап

- управление сотрудниками;
- детальные графики и отчёты;
- Sanctum API для советников MT4/MT5.

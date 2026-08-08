<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\FinancialOperation;
use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class RobotDetail extends Component
{
    public Robot $robot;
    public string $name = '';
    public string $broker = '';
    public string $platform = 'manual';
    public string $externalLogin = '';
    public string $currency = 'USD';
    public string $initialDeposit = '0';
    public bool $isActive = true;
    public string $calendarMonth;
    public ?string $selectedDate = null;
    public int $accountRevision = 0;

    public string $financeType = 'deposit';
    public string $financeAmount = '';
    public string $financeDate = '';
    public string $financeComment = '';

    public string $statusType = 'diagnostics';
    public string $statusStartDate = '';
    public string $statusEndDate = '';
    public string $statusComment = '';

    public function mount(Robot $robot): void
    {
        if (auth()->user()->role === UserRole::Viewer) {
            abort_unless(auth()->user()->robots()->whereKey($robot->id)->exists(), 403);
        }

        $this->robot = $robot;
        $this->calendarMonth = now()->format('Y-m');
        $this->financeDate = now()->format('Y-m-d');
        $this->statusStartDate = now()->format('Y-m-d');

        $account = $robot->account;
        if ($account) {
            $this->name = $account->name;
            $this->broker = $account->broker ?? '';
            $this->platform = $account->platform;
            $this->externalLogin = $account->external_login ?? '';
            $this->currency = $account->currency;
            $this->initialDeposit = (string) $account->initial_deposit;
            $this->isActive = $account->is_active;
        }
    }

    #[On('calendar-period-changed')]
    public function updateCalendarPeriod(string $month, ?string $selectedDate = null): void
    {
        $this->calendarMonth = $month;
        $this->selectedDate = $selectedDate;
    }

    public function previousMonth(): void
    {
        $this->calendarMonth = CarbonImmutable::createFromFormat('Y-m', $this->calendarMonth)->subMonth()->format('Y-m');
        $this->selectedDate = null;
        $this->dispatch('robot-period-selected', month: $this->calendarMonth);
    }

    public function nextMonth(): void
    {
        $this->calendarMonth = CarbonImmutable::createFromFormat('Y-m', $this->calendarMonth)->addMonth()->format('Y-m');
        $this->selectedDate = null;
        $this->dispatch('robot-period-selected', month: $this->calendarMonth);
    }

    public function saveAccount(): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'broker' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', Rule::in(['manual', 'mt4', 'mt5'])],
            'externalLogin' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'initialDeposit' => ['required', 'numeric', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        $account = $this->robot->account()->firstOrNew();
        $old = $account->exists ? $account->toArray() : null;
        $account->fill([
            'name' => $data['name'],
            'broker' => $data['broker'] ?: null,
            'platform' => $data['platform'],
            'external_login' => $data['externalLogin'] ?: null,
            'currency' => strtoupper($data['currency']),
            'initial_deposit' => $data['initialDeposit'],
            'is_active' => $data['isActive'],
        ])->save();

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $old ? 'account.updated' : 'account.created',
            'auditable_type' => TradingAccount::class,
            'auditable_id' => $account->id,
            'old_values' => $old,
            'new_values' => $account->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->robot->refresh();
        $this->accountRevision++;
        session()->flash('account-status', 'Счёт настроен.');
    }

    public function saveFinancialOperation(AccountBalanceService $balanceService): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);

        $account = $this->robot->account()->first();
        if (! $account) {
            session()->flash('finance_status', 'Сначала настрой торговый счёт.');
            return;
        }

        $validated = $this->validate([
            'financeType' => ['required', Rule::in(FinancialOperation::types())],
            'financeAmount' => ['required', 'numeric', 'min:0.01'],
            'financeDate' => ['required', 'date'],
            'financeComment' => ['nullable', 'string', 'max:1000'],
        ]);

        FinancialOperation::create([
            'trading_account_id' => $account->id,
            'type' => $validated['financeType'],
            'amount' => $validated['financeAmount'],
            'currency' => $account->currency,
            'operation_date' => $validated['financeDate'],
            'source' => 'manual',
            'comment' => $validated['financeComment'] ?: null,
            'created_by' => auth()->id(),
        ]);

        $balanceService->refreshCurrentBalance($account);
        $this->financeType = FinancialOperation::TYPE_DEPOSIT;
        $this->financeAmount = '';
        $this->financeDate = now()->format('Y-m-d');
        $this->financeComment = '';
        $this->robot->refresh();
        $this->accountRevision++;
        session()->flash('finance_status', 'Финансовая операция добавлена.');
    }

    public function deleteFinancialOperation(int $operationId, AccountBalanceService $balanceService): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);

        $account = $this->robot->account()->first();
        if (! $account) {
            return;
        }

        $operation = $account->financialOperations()->whereKey($operationId)->firstOrFail();
        $operation->delete();
        $balanceService->refreshCurrentBalance($account);
        $this->robot->refresh();
        $this->accountRevision++;
        session()->flash('finance_status', 'Финансовая операция удалена.');
    }

    public function saveStatusPeriod(): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);

        $validated = $this->validate([
            'statusType' => ['required', Rule::in(RobotStatusPeriod::nonWorkingStatuses())],
            'statusStartDate' => ['required', 'date'],
            'statusEndDate' => ['nullable', 'date', 'after_or_equal:statusStartDate'],
            'statusComment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->robot->statusPeriods()->create([
            'status' => $validated['statusType'],
            'starts_at' => $validated['statusStartDate'],
            'ends_at' => $validated['statusEndDate'] ?: null,
            'comment' => $validated['statusComment'] ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->statusType = RobotStatusPeriod::STATUS_DIAGNOSTICS;
        $this->statusStartDate = now()->format('Y-m-d');
        $this->statusEndDate = '';
        $this->statusComment = '';
        $this->accountRevision++;
        session()->flash('status_period_status', 'Период простоя добавлен.');
    }

    public function deleteStatusPeriod(int $periodId): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);
        $this->robot->statusPeriods()->whereKey($periodId)->firstOrFail()->delete();
        $this->accountRevision++;
        session()->flash('status_period_status', 'Период простоя удалён.');
    }

    private function nonWorkingDates(): array
    {
        $dates = [];

        foreach ($this->robot->statusPeriods()->whereIn('status', RobotStatusPeriod::nonWorkingStatuses())->get() as $period) {
            $start = CarbonImmutable::parse($period->starts_at)->startOfDay();
            $end = $period->ends_at
                ? CarbonImmutable::parse($period->ends_at)->startOfDay()
                : CarbonImmutable::today();

            for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                $dates[$date->toDateString()] = true;
            }
        }

        return $dates;
    }

    public function render(): View
    {
        $accountId = $this->robot->account?->id;
        $monthStart = CarbonImmutable::createFromFormat('Y-m', $this->calendarMonth)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        $results = TradingResult::query()
            ->withinTrackingPeriod()
            ->when($accountId, fn ($query) => $query->where('trading_account_id', $accountId), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereBetween('traded_at', [$monthStart, $monthEnd]);
        $monthProfit = (float) (clone $results)->sum('amount');
        $accountedDays = (clone $results)->distinct('traded_at')->count('traded_at');
        $deposit = (float) ($this->robot->account?->initial_deposit ?? 0);

        $allTimeResults = TradingResult::query()
            ->withinTrackingPeriod()
            ->when($accountId, fn ($query) => $query->where('trading_account_id', $accountId), fn ($query) => $query->whereRaw('1 = 0'));
        $allTimeProfit = (float) (clone $allTimeResults)->sum('amount');
        $allTimeDays = (clone $allTimeResults)->distinct('traded_at')->count('traded_at');

        $nonWorkingDates = $this->nonWorkingDates();
        $allTimeWorkingDays = (clone $allTimeResults)
            ->pluck('traded_at')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
            ->unique()
            ->reject(fn (string $date) => isset($nonWorkingDates[$date]))
            ->count();

        $todayResults = TradingResult::query()
            ->withinTrackingPeriod()
            ->when($accountId, fn ($query) => $query->where('trading_account_id', $accountId), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereDate('traded_at', today());
        $todayProfit = (float) (clone $todayResults)->sum('amount');
        $todayOperations = (clone $todayResults)->count();

        $account = $this->robot->account()->first();
        $financeSummary = $account
            ? app(AccountBalanceService::class)->summary($account)
            : [
                'initial_deposit' => 0,
                'trading_profit' => 0,
                'deposits' => 0,
                'withdrawals' => 0,
                'commissions' => 0,
                'expenses' => 0,
                'adjustments' => 0,
                'current_balance' => 0,
            ];

        $financialOperations = $account
            ? $account->financialOperations()->latest('operation_date')->latest('id')->limit(50)->get()
            : collect();

        return view('livewire.robot-detail', [
            'monthProfit' => $monthProfit,
            'monthPercent' => $deposit > 0 ? $monthProfit / $deposit * 100 : 0,
            'dayPercent' => $deposit > 0 && $accountedDays > 0 ? ($monthProfit / $accountedDays) / $deposit * 100 : 0,
            'dailyAverage' => $accountedDays > 0 ? $monthProfit / $accountedDays : 0,
            'allTimeProfit' => $allTimeProfit,
            'allTimePercent' => $deposit > 0 ? $allTimeProfit / $deposit * 100 : 0,
            'allTimeDayPercent' => $deposit > 0 && $allTimeWorkingDays > 0 ? ($allTimeProfit / $allTimeWorkingDays) / $deposit * 100 : 0,
            'allTimeDailyAverage' => $allTimeWorkingDays > 0 ? $allTimeProfit / $allTimeWorkingDays : 0,
            'allTimeWorkingDays' => $allTimeWorkingDays,
            'allTimeRecordedDays' => $allTimeDays,
            'todayProfit' => $todayProfit,
            'todayPercent' => $deposit > 0 ? $todayProfit / $deposit * 100 : 0,
            'todayOperations' => $todayOperations,
            'todayAverage' => $todayOperations > 0 ? $todayProfit / $todayOperations : 0,
            'financeSummary' => $financeSummary,
            'financialOperations' => $financialOperations,
            'statusPeriods' => $this->robot->statusPeriods()->latest('starts_at')->latest('id')->get(),
            'recentResults' => $accountId
                ? TradingResult::query()
                    ->where('trading_account_id', $accountId)
                    ->withinTrackingPeriod()
                    ->latest('traded_at')
                    ->latest('sequence')
                    ->limit(100)
                    ->get()
                : collect(),
        ])->layout('components.layouts.app', ['title' => $this->robot->name.' — CEO Stat']);
    }
}

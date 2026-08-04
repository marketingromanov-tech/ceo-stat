<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
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

    public function mount(Robot $robot): void
    {
        if (auth()->user()->role === UserRole::Viewer) {
            abort_unless(auth()->user()->robots()->whereKey($robot->id)->exists(), 403);
        }

        $this->robot = $robot;
        $this->calendarMonth = now()->format('Y-m');
        $account = $robot->account;
        if ($account) {
            $this->name = $account->name; $this->broker = $account->broker ?? ''; $this->platform = $account->platform;
            $this->externalLogin = $account->external_login ?? ''; $this->currency = $account->currency;
            $this->initialDeposit = (string) $account->initial_deposit; $this->isActive = $account->is_active;
        }
    }

    #[On('calendar-period-changed')]
    public function updateCalendarPeriod(string $month, ?string $selectedDate = null): void
    {
        $this->calendarMonth = $month;
        $this->selectedDate = $selectedDate;
    }

    public function saveAccount(): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'], 'broker' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', Rule::in(['manual', 'mt4', 'mt5'])], 'externalLogin' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'], 'initialDeposit' => ['required', 'numeric', 'min:0'],
            'isActive' => ['boolean'],
        ]);
        $account = $this->robot->account()->firstOrNew(); $old = $account->exists ? $account->toArray() : null;
        $account->fill(['name' => $data['name'], 'broker' => $data['broker'] ?: null, 'platform' => $data['platform'], 'external_login' => $data['externalLogin'] ?: null, 'currency' => strtoupper($data['currency']), 'initial_deposit' => $data['initialDeposit'], 'is_active' => $data['isActive']])->save();
        AuditLog::query()->create(['user_id' => auth()->id(), 'action' => $old ? 'account.updated' : 'account.created', 'auditable_type' => TradingAccount::class, 'auditable_id' => $account->id, 'old_values' => $old, 'new_values' => $account->toArray(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
        $this->robot->refresh();
        $this->accountRevision++;
        session()->flash('account-status', 'Счёт настроен.');
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

        return view('livewire.robot-detail', [
            'monthProfit' => $monthProfit,
            'monthPercent' => $deposit > 0 ? $monthProfit / $deposit * 100 : 0,
            'dayPercent' => $deposit > 0 && $accountedDays > 0 ? ($monthProfit / $accountedDays) / $deposit * 100 : 0,
            'dailyAverage' => $accountedDays > 0 ? $monthProfit / $accountedDays : 0,
            'allTimeProfit' => $allTimeProfit,
            'allTimePercent' => $deposit > 0 ? $allTimeProfit / $deposit * 100 : 0,
            'allTimeDayPercent' => $deposit > 0 && $allTimeDays > 0 ? ($allTimeProfit / $allTimeDays) / $deposit * 100 : 0,
            'allTimeDailyAverage' => $allTimeDays > 0 ? $allTimeProfit / $allTimeDays : 0,
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

<?php

namespace App\Livewire;

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
    public string $currentBalance = '0';
    public bool $isActive = true;
    public string $calendarMonth;
    public ?string $selectedDate = null;

    public function mount(Robot $robot): void
    {
        $this->robot = $robot;
        $this->calendarMonth = now()->format('Y-m');
        $account = $robot->account;
        if ($account) {
            $this->name = $account->name; $this->broker = $account->broker ?? ''; $this->platform = $account->platform;
            $this->externalLogin = $account->external_login ?? ''; $this->currency = $account->currency;
            $this->initialDeposit = (string) $account->initial_deposit; $this->currentBalance = (string) $account->current_balance; $this->isActive = $account->is_active;
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
            'currentBalance' => ['required', 'numeric'], 'isActive' => ['boolean'],
        ]);
        $account = $this->robot->account()->firstOrNew(); $old = $account->exists ? $account->toArray() : null;
        $account->fill(['name' => $data['name'], 'broker' => $data['broker'] ?: null, 'platform' => $data['platform'], 'external_login' => $data['externalLogin'] ?: null, 'currency' => strtoupper($data['currency']), 'initial_deposit' => $data['initialDeposit'], 'current_balance' => $data['currentBalance'], 'is_active' => $data['isActive']])->save();
        AuditLog::query()->create(['user_id' => auth()->id(), 'action' => $old ? 'account.updated' : 'account.created', 'auditable_type' => TradingAccount::class, 'auditable_id' => $account->id, 'old_values' => $old, 'new_values' => $account->toArray(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
        $this->robot->refresh(); session()->flash('account-status', 'Торговый счёт сохранён.');
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
        return view('livewire.robot-detail', [
            'monthProfit' => $monthProfit,
            'monthPercent' => $deposit > 0 ? $monthProfit / $deposit * 100 : 0,
            'dayPercent' => $deposit > 0 && $accountedDays > 0 ? ($monthProfit / $accountedDays) / $deposit * 100 : 0,
            'dailyAverage' => $accountedDays > 0 ? $monthProfit / $accountedDays : 0,
        ])->layout('components.layouts.app', ['title' => $this->robot->name.' — CEO Stat']);
    }
}

<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
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

    public function mount(Robot $robot): void
    {
        $this->robot = $robot;
        $account = $robot->account;
        if ($account) {
            $this->name = $account->name; $this->broker = $account->broker ?? ''; $this->platform = $account->platform;
            $this->externalLogin = $account->external_login ?? ''; $this->currency = $account->currency;
            $this->initialDeposit = (string) $account->initial_deposit; $this->currentBalance = (string) $account->current_balance; $this->isActive = $account->is_active;
        }
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
        $results = TradingResult::query()
            ->when($accountId, fn ($query) => $query->where('trading_account_id', $accountId), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereBetween('traded_at', [now()->startOfMonth(), now()->endOfMonth()]);
        return view('livewire.robot-detail', [
            'monthProfit' => (float) (clone $results)->sum('amount'),
            'activeDays' => (clone $results)->distinct('traded_at')->count('traded_at'),
        ])->layout('components.layouts.app', ['title' => $this->robot->name.' — CEO Stat']);
    }
}

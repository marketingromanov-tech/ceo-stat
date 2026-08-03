<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Robot;
use App\Models\TradingAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AccountManager extends Component
{
    public ?int $editingId = null;
    public ?int $robotId = null;
    public string $name = '';
    public string $broker = '';
    public string $platform = 'manual';
    public string $externalLogin = '';
    public string $currency = 'USD';
    public string $initialDeposit = '0';
    public string $currentBalance = '0';
    public bool $isActive = true;

    public function edit(int $id): void
    {
        $account = TradingAccount::query()->findOrFail($id);
        foreach (['robot_id' => 'robotId', 'external_login' => 'externalLogin', 'initial_deposit' => 'initialDeposit', 'current_balance' => 'currentBalance', 'is_active' => 'isActive'] as $column => $property) $this->{$property} = $account->{$column};
        $this->editingId = $account->id; $this->name = $account->name; $this->broker = $account->broker ?? ''; $this->platform = $account->platform; $this->currency = $account->currency;
    }

    public function save(): void
    {
        $data = $this->validate([
            'robotId' => ['required', 'integer', 'exists:robots,id'], 'name' => ['required', 'string', 'max:255'],
            'broker' => ['nullable', 'string', 'max:255'], 'platform' => ['required', Rule::in(['manual', 'mt4', 'mt5'])],
            'externalLogin' => ['nullable', 'string', 'max:255'], 'currency' => ['required', 'string', 'size:3'],
            'initialDeposit' => ['required', 'numeric', 'min:0'], 'currentBalance' => ['required', 'numeric'], 'isActive' => ['boolean'],
        ]);
        $account = TradingAccount::query()->findOrNew($this->editingId); $old = $account->exists ? $account->toArray() : null;
        $account->fill(['robot_id' => $data['robotId'], 'name' => $data['name'], 'broker' => $data['broker'] ?: null, 'platform' => $data['platform'], 'external_login' => $data['externalLogin'] ?: null, 'currency' => strtoupper($data['currency']), 'initial_deposit' => $data['initialDeposit'], 'current_balance' => $data['currentBalance'], 'is_active' => $data['isActive']])->save();
        $this->audit($account->wasRecentlyCreated ? 'account.created' : 'account.updated', $account, $old); $this->cancel(); session()->flash('status', 'Торговый счёт сохранён.');
    }

    public function toggle(int $id): void
    {
        $account = TradingAccount::query()->findOrFail($id); $old = $account->toArray(); $account->update(['is_active' => ! $account->is_active]); $this->audit('account.status_changed', $account, $old);
    }

    public function cancel(): void
    {
        $this->reset(); $this->platform = 'manual'; $this->currency = 'USD'; $this->initialDeposit = '0'; $this->currentBalance = '0'; $this->isActive = true; $this->resetValidation();
    }

    private function audit(string $action, TradingAccount $account, ?array $old): void
    {
        AuditLog::query()->create(['user_id' => auth()->id(), 'action' => $action, 'auditable_type' => TradingAccount::class, 'auditable_id' => $account->id, 'old_values' => $old, 'new_values' => $account->toArray(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }

    public function render(): View
    {
        return view('livewire.account-manager', ['robots' => Robot::query()->orderBy('name')->get(), 'accounts' => TradingAccount::query()->with('robot')->latest()->get()])->layout('components.layouts.app', ['title' => 'Счета — CEO Stat']);
    }
}

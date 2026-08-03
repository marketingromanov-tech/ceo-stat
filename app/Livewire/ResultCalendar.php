<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ResultCalendar extends Component
{
    public string $month;
    public ?int $accountId = null;
    public ?string $selectedDate = null;
    public string $amount = '';
    public string $comment = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->accountId = TradingAccount::query()->where('is_active', true)->value('id');
    }

    public function previousMonth(): void
    {
        $this->month = CarbonImmutable::createFromFormat('Y-m', $this->month)->subMonth()->format('Y-m');
        $this->resetEditor();
    }

    public function nextMonth(): void
    {
        $this->month = CarbonImmutable::createFromFormat('Y-m', $this->month)->addMonth()->format('Y-m');
        $this->resetEditor();
    }

    public function updatedAccountId(): void
    {
        $this->resetEditor();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $result = TradingResult::query()
            ->where('trading_account_id', $this->accountId)
            ->whereDate('traded_at', $date)
            ->where('source', 'manual')
            ->first();

        $this->amount = $result ? (string) $result->amount : '';
        $this->comment = $result?->comment ?? '';
        $this->resetValidation();
    }

    public function save(): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);

        $validated = $this->validate([
            'accountId' => ['required', 'integer', 'exists:trading_accounts,id'],
            'selectedDate' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'between:-9999999999999999.99,9999999999999999.99'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = TradingResult::query()->updateOrCreate([
            'trading_account_id' => $validated['accountId'],
            'traded_at' => $validated['selectedDate'],
            'source' => 'manual',
        ], [
            'amount' => $validated['amount'],
            'comment' => $validated['comment'] ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->audit('result.saved', $result, $result->getChanges());
        session()->flash('calendar-status', 'Результат сохранён.');
    }

    public function delete(): void
    {
        abort_unless(in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);

        $result = TradingResult::query()
            ->where('trading_account_id', $this->accountId)
            ->whereDate('traded_at', $this->selectedDate)
            ->where('source', 'manual')
            ->firstOrFail();
        $old = $result->toArray();
        $id = $result->id;
        $result->delete();
        $this->audit('result.deleted', $result, null, $old, $id);
        $this->resetEditor();
        session()->flash('calendar-status', 'Результат удалён.');
    }

    private function resetEditor(): void
    {
        $this->reset('selectedDate', 'amount', 'comment');
        $this->resetValidation();
    }

    private function audit(string $action, TradingResult $result, ?array $new, ?array $old = null, ?int $id = null): void
    {
        AuditLog::query()->create([
            'user_id' => auth()->id(), 'action' => $action,
            'auditable_type' => TradingResult::class, 'auditable_id' => $id ?? $result->id,
            'old_values' => $old, 'new_values' => $new,
            'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(),
        ]);
    }

    public function render(): View
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $this->month)->startOfMonth();
        $end = $start->endOfMonth();
        $results = $this->accountId
            ? TradingResult::query()->where('trading_account_id', $this->accountId)->whereBetween('traded_at', [$start, $end])->get()->keyBy(fn ($item) => $item->traded_at->format('Y-m-d'))
            : collect();
        $days = collect(range(1, $start->daysInMonth))->map(fn (int $day) => $start->setDay($day));

        return view('livewire.result-calendar', [
            'accounts' => TradingAccount::query()->with('robot')->where('is_active', true)->orderBy('name')->get(),
            'days' => $days, 'results' => $results, 'monthLabel' => $start->translatedFormat('F Y'),
            'leadingBlanks' => $start->isoWeekday() - 1,
        ]);
    }
}

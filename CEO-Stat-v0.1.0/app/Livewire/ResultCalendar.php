<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ResultCalendar extends Component
{
    public string $month;
    public ?int $robotId = null;
    public bool $readOnly = false;
    public ?int $accountId = null;
    public ?string $trackingStartedAt = null;
    public ?string $selectedDate = null;
    public ?int $editingResultId = null;
    public string $amount = '';
    public string $comment = '';

    public function mount(?int $robotId = null, bool $readOnly = false): void
    {
        $this->month = now()->format('Y-m');
        $this->robotId = $robotId;
        $this->readOnly = $readOnly;
        $this->accountId = $robotId
            ? TradingAccount::query()->where('robot_id', $robotId)->value('id')
            : null;
        $this->trackingStartedAt = $robotId
            ? Robot::query()->whereKey($robotId)->value('tracking_started_at')
            : null;
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

    public function selectDate(string $date): void
    {
        if ($this->readOnly || ! $this->accountId || $this->dateIsLocked($date)) {
            return;
        }

        $this->selectedDate = $date;
        $this->reset('editingResultId', 'amount', 'comment');
        $this->resetValidation();
    }

    public function editResult(int $id): void
    {
        abort_if($this->readOnly, 403);
        $result = $this->dayResultsQuery()->findOrFail($id);
        $this->editingResultId = $result->id;
        $this->amount = (string) $result->amount;
        $this->comment = $result->comment ?? '';
    }

    public function save(): void
    {
        abort_unless(! $this->readOnly && in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);

        $validated = $this->validate([
            'accountId' => ['required', 'integer', 'exists:trading_accounts,id'],
            'selectedDate' => ['required', 'date', Rule::when($this->trackingStartedAt, ['after_or_equal:'.$this->trackingStartedAt])],
            'amount' => ['required', 'numeric', 'between:-9999999999999999.99,9999999999999999.99'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->editingResultId
            ? $this->dayResultsQuery()->findOrFail($this->editingResultId)
            : new TradingResult([
                'trading_account_id' => $validated['accountId'],
                'traded_at' => $validated['selectedDate'],
                'source' => 'manual',
                'sequence' => ((int) $this->dayResultsQuery()->max('sequence')) + 1,
            ]);
        $result->fill([
            'amount' => $validated['amount'],
            'comment' => $validated['comment'] ?: null,
            'created_by' => auth()->id(),
        ])->save();

        $this->audit('result.saved', $result, $result->getChanges());
        $this->trackingStartedAt ??= $result->account->robot->fresh()->tracking_started_at?->format('Y-m-d');
        $this->reset('editingResultId', 'amount', 'comment');
        session()->flash('calendar-status', 'Операция сохранена.');
    }

    public function deleteResult(int $id): void
    {
        abort_unless(! $this->readOnly && in_array(auth()->user()->role->value, ['admin', 'operator'], true), 403);

        $result = $this->dayResultsQuery()->findOrFail($id);
        $old = $result->toArray();
        $id = $result->id;
        $result->delete();
        $this->audit('result.deleted', $result, null, $old, $id);
        $this->reset('editingResultId', 'amount', 'comment');
        session()->flash('calendar-status', 'Операция удалена.');
    }

    private function resetEditor(): void
    {
        $this->reset('selectedDate', 'editingResultId', 'amount', 'comment');
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
        $results = $this->summaryResults($start, $end, $this->readOnly ? null : $this->accountId);
        $days = collect(range(1, $start->daysInMonth))->map(fn (int $day) => $start->setDay($day));

        return view('livewire.result-calendar', [
            'account' => $this->accountId ? TradingAccount::query()->with('robot')->find($this->accountId) : null,
            'days' => $days, 'results' => $results, 'monthLabel' => $start->translatedFormat('F Y'),
            'leadingBlanks' => $start->isoWeekday() - 1,
            'trackingStartedAt' => $this->trackingStartedAt,
            'dayResults' => $this->selectedDate && $this->accountId ? $this->dayResultsQuery()->get() : collect(),
        ]);
    }

    private function summaryResults(CarbonImmutable $start, CarbonImmutable $end, ?int $accountId): Collection
    {
        return TradingResult::query()
            ->withinTrackingPeriod()
            ->when($accountId, fn ($query) => $query->where('trading_account_id', $accountId))
            ->whereBetween('traded_at', [$start, $end])
            ->selectRaw('traded_at, SUM(amount) as amount')
            ->groupBy('traded_at')
            ->get()
            ->keyBy(fn ($item) => $item->traded_at->format('Y-m-d'));
    }

    private function dayResultsQuery()
    {
        return TradingResult::query()
            ->where('trading_account_id', $this->accountId)
            ->whereDate('traded_at', $this->selectedDate)
            ->where('source', 'manual')
            ->orderBy('sequence');
    }

    private function dateIsLocked(string $date): bool
    {
        return $this->trackingStartedAt !== null && $date < $this->trackingStartedAt;
    }
}

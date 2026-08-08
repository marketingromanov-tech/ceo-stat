<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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
        if (auth()->user()->role->value === 'viewer') {
            if ($robotId) {
                abort_unless(auth()->user()->robots()->whereKey($robotId)->exists(), 403);
            }
            $readOnly = true;
        }

        $this->month = now()->format('Y-m');
        $this->robotId = $robotId;
        $this->readOnly = $readOnly;
        $this->accountId = $robotId
            ? TradingAccount::query()->where('robot_id', $robotId)->value('id')
            : null;
        $this->trackingStartedAt = $robotId
            ? Robot::query()->find($robotId)?->tracking_started_at?->format('Y-m-d')
            : null;
    }

    public function previousMonth(): mixed
    {
        $this->month = CarbonImmutable::createFromFormat('Y-m', $this->month)->subMonth()->format('Y-m');
        $this->resetEditor();
        $this->notifyPeriodChanged();

        return null;
    }

    public function nextMonth(): mixed
    {
        $this->month = CarbonImmutable::createFromFormat('Y-m', $this->month)->addMonth()->format('Y-m');
        $this->resetEditor();
        $this->notifyPeriodChanged();

        return null;
    }

    #[On('robot-period-selected')]
    public function selectMonth(string $month): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return;
        }

        $this->month = $month;
        $this->resetEditor();
    }

    #[On('dashboard-period-selected')]
    public function selectDashboardMonth(string $month): void
    {
        $this->selectMonth($month);
    }

    public function selectDate(string $date): void
    {
        if ($this->readOnly || ! $this->accountId || $this->dateIsLocked($date)) {
            return;
        }

        $this->selectedDate = $date;
        $this->reset('editingResultId', 'amount', 'comment');
        $this->resetValidation();
        $this->notifyPeriodChanged();
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

        $this->amount = str_replace(["\u{00A0}", ' ', ','], ['', '', '.'], trim($this->amount));

        $validated = $this->validate([
            'accountId' => ['required', 'integer', 'exists:trading_accounts,id'],
            'selectedDate' => ['required', 'date', Rule::when($this->trackingStartedAt, ['after_or_equal:'.$this->trackingStartedAt])],
            'amount' => ['required', 'numeric', 'between:-9999999999999999.99,9999999999999999.99'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'amount.required' => 'Укажите сумму.',
            'amount.numeric' => 'Введите сумму числом, например -9,03.',
            'amount.between' => 'Сумма выходит за допустимый диапазон.',
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
        $this->notifyPeriodChanged();
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
        $this->notifyPeriodChanged();
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
        $results = $this->summaryResults($start, $end, $this->robotId ? $this->accountId : null);
        $deposit = $this->percentageDeposit();
        $results->each(function ($result) use ($deposit): void {
            $result->daily_percent = $deposit > 0
                ? (float) $result->amount / $deposit * 100
                : null;
        });
        $robotBreakdown = $this->readOnly && ! $this->robotId ? $this->robotBreakdown($start, $end) : collect();
        $robotStatuses = $this->robotId ? $this->robotStatusesForMonth($start, $end) : collect();
        $days = collect(range(1, $start->daysInMonth))->map(fn (int $day) => $start->setDay($day));

        return view('livewire.result-calendar', [
            'account' => $this->accountId ? TradingAccount::query()->with('robot')->find($this->accountId) : null,
            'days' => $days,
            'results' => $results,
            'monthLabel' => $start->translatedFormat('F Y'),
            'leadingBlanks' => $start->isoWeekday() - 1,
            'trackingStartedAt' => $this->trackingStartedAt,
            'robotBreakdown' => $robotBreakdown,
            'robotStatuses' => $robotStatuses,
            'dayResults' => $this->selectedDate && $this->accountId ? $this->dayResultsQuery()->get() : collect(),
        ]);
    }

    private function summaryResults(CarbonImmutable $start, CarbonImmutable $end, ?int $accountId): Collection
    {
        return TradingResult::query()
            ->visibleTo(auth()->user())
            ->withinTrackingPeriod()
            ->when($accountId, fn ($query) => $query->where('trading_account_id', $accountId))
            ->whereBetween('traded_at', [$start, $end])
            ->selectRaw('traded_at, SUM(amount) as amount')
            ->groupBy('traded_at')
            ->get()
            ->keyBy(fn ($item) => $item->traded_at->format('Y-m-d'));
    }

    private function robotStatusesForMonth(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $periods = RobotStatusPeriod::query()
            ->where('robot_id', $this->robotId)
            ->whereDate('starts_at', '<=', $end->format('Y-m-d'))
            ->where(function ($query) use ($start): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $start->format('Y-m-d'));
            })
            ->orderBy('starts_at')
            ->get();

        $statuses = collect();

        foreach ($periods as $period) {
            $periodStart = CarbonImmutable::parse($period->starts_at)->max($start);
            $periodEnd = CarbonImmutable::parse($period->ends_at ?? $end)->min($end);

            for ($day = $periodStart; $day->lte($periodEnd); $day = $day->addDay()) {
                $statuses->put($day->format('Y-m-d'), [
                    'status' => $period->status,
                    'comment' => $period->comment,
                ]);
            }
        }

        return $statuses;
    }

    private function percentageDeposit(): float
    {
        if ($this->accountId) {
            return (float) TradingAccount::query()
                ->whereKey($this->accountId)
                ->value('initial_deposit');
        }

        $visibleRobotIds = auth()->user()->role->value === 'viewer'
            ? auth()->user()->robots()->pluck('robots.id')
            : null;

        return (float) TradingAccount::query()
            ->where('is_active', true)
            ->when($visibleRobotIds, fn ($query) => $query->whereIn('robot_id', $visibleRobotIds))
            ->sum('initial_deposit');
    }

    private function dayResultsQuery()
    {
        return TradingResult::query()
            ->where('trading_account_id', $this->accountId)
            ->whereDate('traded_at', $this->selectedDate)
            ->where('source', 'manual')
            ->orderByDesc('sequence');
    }

    private function robotBreakdown(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return TradingResult::query()
            ->visibleTo(auth()->user())
            ->withinTrackingPeriod()
            ->join('trading_accounts', 'trading_accounts.id', '=', 'trading_results.trading_account_id')
            ->join('robots', 'robots.id', '=', 'trading_accounts.robot_id')
            ->whereBetween('trading_results.traded_at', [$start, $end])
            ->selectRaw('trading_results.traded_at, robots.id as robot_id, robots.name as robot_name, SUM(trading_results.amount) as amount')
            ->groupBy('trading_results.traded_at', 'robots.id', 'robots.name')
            ->orderBy('robots.name')
            ->get()
            ->groupBy(fn ($item) => CarbonImmutable::parse($item->traded_at)->format('Y-m-d'));
    }

    private function dateIsLocked(string $date): bool
    {
        return $this->trackingStartedAt !== null && $date < $this->trackingStartedAt;
    }

    private function notifyPeriodChanged(): void
    {
        $this->dispatch('calendar-period-changed', month: $this->month, selectedDate: $this->selectedDate);
    }
}

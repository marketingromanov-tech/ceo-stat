<?php

namespace App\Livewire;

use App\Models\Robot;
use App\Services\PortfolioStatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class PortfolioStatistics extends Component
{
    public string $statisticsPeriod = 'all';
    public string $statisticsStartDate = '';
    public string $statisticsEndDate = '';
    public ?string $appliedStatisticsStartDate = null;
    public ?string $appliedStatisticsEndDate = null;

    public function selectStatisticsPeriod(string $period): void
    {
        abort_unless(in_array($period, ['all', '7_days', '30_days', '3_months', '6_months', 'current_year', 'custom'], true), 422);
        $this->statisticsPeriod = $period;

        if ($period === 'custom') {
            $this->statisticsStartDate = $this->appliedStatisticsStartDate ?? now()->subDays(29)->format('Y-m-d');
            $this->statisticsEndDate = $this->appliedStatisticsEndDate ?? now()->format('Y-m-d');
            return;
        }

        [$from, $to] = $this->periodDates($period);
        $this->appliedStatisticsStartDate = $from?->toDateString();
        $this->appliedStatisticsEndDate = $to?->toDateString();
        $this->resetValidation(['statisticsStartDate', 'statisticsEndDate']);
    }

    public function applyCustomStatisticsPeriod(): void
    {
        $validated = $this->validate([
            'statisticsStartDate' => ['required', 'date', 'before_or_equal:today'],
            'statisticsEndDate' => ['required', 'date', 'after_or_equal:statisticsStartDate', 'before_or_equal:today'],
        ]);

        $this->statisticsPeriod = 'custom';
        $this->appliedStatisticsStartDate = $validated['statisticsStartDate'];
        $this->appliedStatisticsEndDate = $validated['statisticsEndDate'];
    }

    public function resetStatisticsPeriod(): void
    {
        $this->statisticsPeriod = 'all';
        $this->statisticsStartDate = '';
        $this->statisticsEndDate = '';
        $this->appliedStatisticsStartDate = null;
        $this->appliedStatisticsEndDate = null;
        $this->resetValidation(['statisticsStartDate', 'statisticsEndDate']);
    }

    #[On('trading-result-saved')]
    public function refreshAfterTradingResultSaved(int $robotId, int $accountId, string $date): void
    {
        $query = Robot::query()->whereKey($robotId)->whereHas('account', fn ($accountQuery) => $accountQuery->whereKey($accountId));
        if (auth()->user()->role->value === 'viewer') {
            $query->whereHas('viewers', fn ($viewerQuery) => $viewerQuery->whereKey(auth()->id()));
        }

        if (! $query->exists()) {
            $this->skipRender();
        }
    }

    public function render(PortfolioStatisticsService $statisticsService): View
    {
        $from = $this->appliedStatisticsStartDate ? CarbonImmutable::parse($this->appliedStatisticsStartDate) : null;
        $to = $this->appliedStatisticsEndDate ? CarbonImmutable::parse($this->appliedStatisticsEndDate) : null;
        $statistics = $statisticsService->calculateFor(auth()->user(), $from, $to);
        $robots = Robot::query()
            ->with('account')
            ->whereIn('id', $statistics['portfolio']['robot_ids'])
            ->orderBy('name')
            ->get();

        return view('livewire.portfolio-statistics', compact('statistics', 'robots'))
            ->layout('components.layouts.app', ['title' => 'Общая статистика — CEO Stat']);
    }

    private function periodDates(string $period): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            '7_days' => [$today->subDays(6), $today],
            '30_days' => [$today->subDays(29), $today],
            '3_months' => [$today->subMonths(3), $today],
            '6_months' => [$today->subMonths(6), $today],
            'current_year' => [$today->startOfYear(), $today],
            default => [null, null],
        };
    }
}

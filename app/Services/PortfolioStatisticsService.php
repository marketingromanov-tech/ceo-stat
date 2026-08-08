<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\FinancialOperation;
use App\Models\Robot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PortfolioStatisticsService
{
    public function __construct(private readonly RobotStatisticsService $robotStatisticsService)
    {
    }

    public function calculateFor(User $user, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $robots = Robot::query()
            ->with('account')
            ->when(
                $user->role === UserRole::Viewer,
                fn ($query) => $query->whereHas('viewers', fn ($viewerQuery) => $viewerQuery->whereKey($user->id))
            )
            ->orderBy('id')
            ->get();

        $dailyResults = collect();
        $cashFlows = collect();
        $openingBalance = 0.0;
        $effectiveStarts = collect();
        $robotContributions = collect();
        $effectiveTo = ($to ?? CarbonImmutable::today())->startOfDay()->min(CarbonImmutable::today());

        foreach ($robots as $robot) {
            $statistics = $this->robotStatisticsService->calculate($robot, $from, $to);
            $openingBalance += (float) $statistics['period']['opening_balance'];
            $robotContributions->push([
                'name' => $robot->name,
                'profit' => (float) $statistics['kpi']['profit'],
            ]);

            if ($statistics['period']['from']) {
                $effectiveStarts->push($statistics['period']['from']);
            }

            foreach ($statistics['charts']['daily']['labels'] as $index => $date) {
                $dailyResults[$date] = round(
                    (float) ($dailyResults[$date] ?? 0) + (float) $statistics['charts']['daily']['values'][$index],
                    2
                );
            }

            $this->mergeCashFlows($cashFlows, $robot, $statistics['period']['from'], $statistics['period']['to']);
        }

        $dailyResults = $dailyResults->sortKeys();

        return $this->calculateStatistics(
            $dailyResults,
            round($openingBalance, 2),
            $cashFlows->sortKeys(),
            $robots,
            $robotContributions,
            $from?->startOfDay() ?? ($effectiveStarts->isNotEmpty() ? CarbonImmutable::parse($effectiveStarts->min()) : null),
            $effectiveTo,
        );
    }

    private function calculateStatistics(
        Collection $dailyResults,
        float $openingBalance,
        Collection $cashFlows,
        Collection $robots,
        Collection $robotContributions,
        ?CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $values = $dailyResults->values();
        $profit = round((float) $values->sum(), 2);
        $workingDays = $values->count();
        $profitableDays = $values->filter(fn (float $amount) => $amount > 0);
        $losingDays = $values->filter(fn (float $amount) => $amount < 0);
        $zeroDays = $values->filter(fn (float $amount) => $amount === 0.0);
        $grossProfit = round((float) $profitableDays->sum(), 2);
        $grossLoss = round(abs((float) $losingDays->sum()), 2);
        [$maxWinningStreak, $maxLosingStreak, $currentStreak] = $this->streaks($values);
        [$maxDrawdown, $maxDrawdownPercent, $growth] = $this->drawdown($dailyResults, $openingBalance, $cashFlows);
        $last30 = $dailyResults->slice(-30)->values();
        $last30Profitable = $last30->filter(fn (float $amount) => $amount > 0)->count();
        $last30Losing = $last30->filter(fn (float $amount) => $amount < 0)->count();
        $monthly = $dailyResults
            ->groupBy(fn (float $amount, string $date) => substr($date, 0, 7))
            ->map(fn (Collection $days) => round((float) $days->sum(), 2));
        $contributionTotal = (float) $robotContributions->sum('profit');
        $contributionPercentages = $robotContributions->map(
            fn (array $robot) => $contributionTotal !== 0.0 ? round($robot['profit'] / $contributionTotal * 100, 3) : 0.0
        );

        return [
            'kpi' => [
                'profit' => $profit,
                'return_percent' => $this->percent($profit, $openingBalance),
                'average_daily_percent' => $workingDays > 0 ? $this->percent($profit / $workingDays, $openingBalance) : 0.0,
                'working_days' => $workingDays,
                'profitable_days' => $profitableDays->count(),
                'profitable_days_percent' => $workingDays > 0 ? round($profitableDays->count() / $workingDays * 100, 3) : 0.0,
                'max_drawdown' => $maxDrawdown,
                'max_drawdown_percent' => $maxDrawdownPercent,
                'profit_factor' => $grossLoss > 0 ? round($grossProfit / $grossLoss, 3) : null,
                'profit_factor_state' => $grossLoss === 0.0 && $grossProfit > 0 ? 'infinite' : ($grossLoss === 0.0 ? 'undefined' : 'finite'),
                'gross_profit' => $grossProfit,
                'gross_loss' => $grossLoss,
            ],
            'quality' => [
                'profitable_days' => $profitableDays->count(),
                'losing_days' => $losingDays->count(),
                'zero_days' => $zeroDays->count(),
                'average_profitable_day' => $profitableDays->isNotEmpty() ? round((float) $profitableDays->average(), 2) : 0.0,
                'average_losing_day' => $losingDays->isNotEmpty() ? round((float) $losingDays->average(), 4) : 0.0,
                'best_day' => $values->isNotEmpty() ? round((float) $values->max(), 2) : 0.0,
                'worst_day' => $values->isNotEmpty() ? round((float) $values->min(), 2) : 0.0,
                'max_winning_streak' => $maxWinningStreak,
                'max_losing_streak' => $maxLosingStreak,
                'current_streak' => $currentStreak,
            ],
            'last_30' => [
                'profit' => round((float) $last30->sum(), 2),
                'average_result' => $last30->isNotEmpty() ? round((float) $last30->average(), 2) : 0.0,
                'average_daily_percent' => $last30->isNotEmpty() ? $this->percent((float) $last30->average(), $openingBalance) : 0.0,
                'profitable_days' => $last30Profitable,
                'losing_days' => $last30Losing,
                'profitable_days_percent' => $last30->isNotEmpty() ? round($last30Profitable / $last30->count() * 100, 3) : 0.0,
                'days' => $last30->count(),
            ],
            'charts' => [
                'growth' => ['labels' => $dailyResults->keys()->values()->all(), 'values' => $growth],
                'daily' => ['labels' => $dailyResults->keys()->values()->all(), 'values' => $values->all()],
                'monthly' => ['labels' => $monthly->keys()->values()->all(), 'values' => $monthly->values()->all()],
                'robots' => [
                    'labels' => $robotContributions->pluck('name')->all(),
                    'values' => $contributionPercentages->all(),
                    'profit_values' => $robotContributions->pluck('profit')->map(fn ($value) => round((float) $value, 2))->all(),
                ],
            ],
            'period' => [
                'from' => $from?->toDateString(),
                'to' => $to->toDateString(),
                'opening_balance' => $openingBalance,
                'has_data' => $workingDays > 0,
            ],
            'portfolio' => [
                'robots_count' => $robots->count(),
                'robot_ids' => $robots->pluck('id')->all(),
            ],
        ];
    }

    private function mergeCashFlows(Collection $cashFlows, Robot $robot, ?string $from, string $to): void
    {
        if (! $robot->account) {
            return;
        }

        $query = $robot->account->financialOperations()->whereDate('operation_date', '<=', $to);
        if ($from) {
            $query->whereDate('operation_date', '>=', $from);
        }
        if ($robot->tracking_started_at) {
            $query->whereDate('operation_date', '>=', $robot->tracking_started_at);
        }

        foreach ($query->get() as $operation) {
            $date = CarbonImmutable::parse($operation->operation_date)->toDateString();
            $cashFlows[$date] = round(
                (float) ($cashFlows[$date] ?? 0) + $this->operationEffect($operation->type, (float) $operation->amount),
                2
            );
        }
    }

    private function operationEffect(string $type, float $amount): float
    {
        return match ($type) {
            FinancialOperation::TYPE_WITHDRAWAL,
            FinancialOperation::TYPE_COMMISSION,
            FinancialOperation::TYPE_EXPENSE => -$amount,
            FinancialOperation::TYPE_DEPOSIT,
            FinancialOperation::TYPE_ADJUSTMENT => $amount,
            default => 0.0,
        };
    }

    private function percent(float $amount, float $base): float
    {
        return $base > 0 ? round($amount / $base * 100, 3) : 0.0;
    }

    private function streaks(Collection $values): array
    {
        $winning = $losing = $maxWinning = $maxLosing = 0;
        $currentType = 'none';
        $currentLength = 0;

        foreach ($values as $amount) {
            $type = $amount > 0 ? 'winning' : ($amount < 0 ? 'losing' : 'zero');
            $winning = $type === 'winning' ? $winning + 1 : 0;
            $losing = $type === 'losing' ? $losing + 1 : 0;
            $maxWinning = max($maxWinning, $winning);
            $maxLosing = max($maxLosing, $losing);
            $currentLength = $type !== 'zero' && $type === $currentType ? $currentLength + 1 : ($type === 'zero' ? 0 : 1);
            $currentType = $type;
        }

        return [$maxWinning, $maxLosing, ['type' => $currentType, 'length' => $currentLength]];
    }

    private function drawdown(Collection $dailyResults, float $openingBalance, Collection $cashFlows): array
    {
        $balance = $peak = $openingBalance;
        $maxAmount = $maxPercent = 0.0;
        $growth = [];
        $appliedCashFlowDates = [];

        foreach ($dailyResults as $date => $amount) {
            foreach ($cashFlows as $cashFlowDate => $cashFlow) {
                if (! isset($appliedCashFlowDates[$cashFlowDate]) && $cashFlowDate <= $date) {
                    $balance += $cashFlow;
                    $peak += $cashFlow;
                    $appliedCashFlowDates[$cashFlowDate] = true;
                }
            }

            $balance += $amount;
            $peak = max($peak, $balance);
            $drawdown = max(0, $peak - $balance);
            $maxAmount = max($maxAmount, $drawdown);
            $maxPercent = max($maxPercent, $peak > 0 ? $drawdown / $peak * 100 : 0);
            $growth[] = round($balance, 2);
        }

        return [round($maxAmount, 2), round($maxPercent, 3), $growth];
    }
}

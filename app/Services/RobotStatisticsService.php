<?php

namespace App\Services;

use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Models\FinancialOperation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class RobotStatisticsService
{
    public function calculate(Robot $robot, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $account = $robot->account;
        $initialDeposit = (float) ($account?->initial_deposit ?? 0);
        $nonWorkingDates = $this->nonWorkingDates($robot);
        $today = CarbonImmutable::today();
        $effectiveFrom = $from?->startOfDay();
        if ($robot->tracking_started_at) {
            $trackingStart = CarbonImmutable::parse($robot->tracking_started_at)->startOfDay();
            $effectiveFrom = $effectiveFrom && $effectiveFrom->gt($trackingStart) ? $effectiveFrom : $trackingStart;
        }
        $effectiveTo = $to ? $to->startOfDay()->min($today) : $today;

        $dailyResults = $account
            ? $account->results()
                ->withinTrackingPeriod()
                ->when($effectiveFrom, fn ($query) => $query->whereDate('traded_at', '>=', $effectiveFrom))
                ->whereDate('traded_at', '<=', $effectiveTo)
                ->selectRaw('traded_at, SUM(amount) as amount')
                ->groupBy('traded_at')
                ->orderBy('traded_at')
                ->get()
                ->mapWithKeys(fn ($result) => [CarbonImmutable::parse($result->traded_at)->toDateString() => round((float) $result->amount, 2)])
                ->reject(fn (float $amount, string $date) => isset($nonWorkingDates[$date]))
            : collect();

        $periodOpeningBalance = $account
            ? $this->openingBalance($robot, $effectiveFrom, $initialDeposit)
            : 0.0;
        $periodOperations = $account
            ? $this->operationTotalsByDate($robot, $effectiveFrom, $effectiveTo)
            : collect();

        $values = $dailyResults->values();
        $profit = round((float) $values->sum(), 2);
        $workingDays = $values->count();
        $profitableDays = $values->filter(fn (float $amount) => $amount > 0);
        $losingDays = $values->filter(fn (float $amount) => $amount < 0);
        $zeroDays = $values->filter(fn (float $amount) => $amount === 0.0);
        [$maxWinningStreak, $maxLosingStreak, $currentStreak] = $this->streaks($dailyResults);
        [$maxDrawdown, $maxDrawdownPercent, $growthValues] = $this->drawdown($dailyResults, $periodOpeningBalance, $periodOperations);
        $grossProfit = (float) $profitableDays->sum();
        $grossLoss = abs((float) $losingDays->sum());
        $profitFactor = $grossLoss > 0 ? round($grossProfit / $grossLoss, 3) : null;
        $profitFactorState = $grossLoss === 0.0 && $grossProfit > 0 ? 'infinite' : ($grossLoss === 0.0 ? 'undefined' : 'finite');
        $last30 = $dailyResults->slice(-30);
        $last30Values = $last30->values();
        $last30Profitable = $last30Values->filter(fn (float $amount) => $amount > 0)->count();
        $last30Losing = $last30Values->filter(fn (float $amount) => $amount < 0)->count();
        $monthly = $dailyResults->groupBy(fn (float $amount, string $date) => substr($date, 0, 7))
            ->map(fn (Collection $days) => round((float) $days->sum(), 2));

        return [
            'kpi' => [
                'profit' => $profit,
                'return_percent' => $this->percent($profit, $periodOpeningBalance),
                'average_daily_percent' => $workingDays > 0 ? $this->percent($profit / $workingDays, $periodOpeningBalance) : 0.0,
                'working_days' => $workingDays,
                'profitable_days' => $profitableDays->count(),
                'profitable_days_percent' => $workingDays > 0 ? round($profitableDays->count() / $workingDays * 100, 3) : 0.0,
                'max_drawdown' => $maxDrawdown,
                'max_drawdown_percent' => $maxDrawdownPercent,
                'profit_factor' => $profitFactor,
                'profit_factor_state' => $profitFactorState,
                'gross_profit' => round($grossProfit, 2),
                'gross_loss' => round($grossLoss, 2),
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
                'profit' => round((float) $last30Values->sum(), 2),
                'average_result' => $last30Values->isNotEmpty() ? round((float) $last30Values->average(), 2) : 0.0,
                'average_daily_percent' => $last30Values->isNotEmpty() ? $this->percent((float) $last30Values->average(), $periodOpeningBalance) : 0.0,
                'profitable_days' => $last30Profitable,
                'losing_days' => $last30Losing,
                'profitable_days_percent' => $last30Values->isNotEmpty() ? round($last30Profitable / $last30Values->count() * 100, 3) : 0.0,
                'days' => $last30Values->count(),
            ],
            'charts' => [
                'growth' => ['labels' => $dailyResults->keys()->values()->all(), 'values' => $growthValues],
                'daily' => ['labels' => $dailyResults->keys()->values()->all(), 'values' => $values->all()],
                'monthly' => ['labels' => $monthly->keys()->values()->all(), 'values' => $monthly->values()->all()],
            ],
            'period' => [
                'from' => $effectiveFrom?->toDateString(),
                'to' => $effectiveTo->toDateString(),
                'opening_balance' => round($periodOpeningBalance, 2),
                'has_data' => $workingDays > 0,
            ],
        ];
    }

    private function openingBalance(Robot $robot, ?CarbonImmutable $from, float $initialDeposit): float
    {
        if (! $from || ! $robot->account) {
            return $initialDeposit;
        }

        $tradingProfit = $robot->account->results()
            ->withinTrackingPeriod()
            ->whereDate('traded_at', '<', $from)
            ->selectRaw('traded_at, SUM(amount) as amount')
            ->groupBy('traded_at')
            ->get()
            ->sum(fn ($result) => (float) $result->amount);

        $operations = $this->operationTotalsByDate($robot, null, $from->subDay())->sum();

        return round($initialDeposit + $tradingProfit + $operations, 2);
    }

    private function operationTotalsByDate(Robot $robot, ?CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        if (! $robot->account) {
            return collect();
        }

        $query = $robot->account->financialOperations()->whereDate('operation_date', '<=', $to);
        if ($robot->tracking_started_at) {
            $query->whereDate('operation_date', '>=', $robot->tracking_started_at);
        }
        if ($from) {
            $query->whereDate('operation_date', '>=', $from);
        }

        return $query->orderBy('operation_date')->get()
            ->groupBy(fn ($operation) => CarbonImmutable::parse($operation->operation_date)->toDateString())
            ->map(fn (Collection $operations) => round((float) $operations->sum(fn ($operation) => $this->operationEffect($operation->type, (float) $operation->amount)), 2));
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

    private function nonWorkingDates(Robot $robot): array
    {
        $dates = [];

        foreach ($robot->statusPeriods()->whereIn('status', RobotStatusPeriod::nonWorkingStatuses())->get() as $period) {
            $start = CarbonImmutable::parse($period->starts_at)->startOfDay();
            $end = $period->ends_at ? CarbonImmutable::parse($period->ends_at)->startOfDay() : CarbonImmutable::today();

            for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                $dates[$date->toDateString()] = true;
            }
        }

        return $dates;
    }

    private function percent(float $amount, float $base): float
    {
        return $base > 0 ? round($amount / $base * 100, 3) : 0.0;
    }

    private function streaks(Collection $dailyResults): array
    {
        $winning = $losing = $maxWinning = $maxLosing = 0;
        $currentType = 'none';
        $currentLength = 0;

        foreach ($dailyResults as $amount) {
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

    private function drawdown(Collection $dailyResults, float $openingBalance, Collection $operations): array
    {
        $balance = $peak = $openingBalance;
        $maxAmount = $maxPercent = 0.0;
        $growth = [];
        $appliedOperationDates = [];

        foreach ($dailyResults as $date => $amount) {
            foreach ($operations as $operationDate => $operationAmount) {
                if (! isset($appliedOperationDates[$operationDate]) && $operationDate <= $date) {
                    $balance += $operationAmount;
                    // Cash flows move the account balance and its high-water mark together;
                    // a withdrawal is not a trading drawdown and a deposit is not trading profit.
                    $peak += $operationAmount;
                    $appliedOperationDates[$operationDate] = true;
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

<?php

namespace App\Http\Controllers;

use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $requestedMonth = $request->query('month');
        $month = is_string($requestedMonth) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedMonth)
            ? $requestedMonth
            : now()->format('Y-m');
        $monthStart = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $monthResults = TradingResult::query()->visibleTo($request->user())->withinTrackingPeriod()->whereBetween('traded_at', [$monthStart, $monthEnd]);
        $monthProfit = (float) (clone $monthResults)->sum('amount');
        $activeDays = (clone $monthResults)->distinct('traded_at')->count('traded_at');
        $visibleRobotIds = $request->user()->role->value === 'viewer' ? $request->user()->robots()->pluck('robots.id') : null;
        $deposit = (float) TradingAccount::query()->where('is_active', true)->when($visibleRobotIds, fn ($query) => $query->whereIn('robot_id', $visibleRobotIds))->sum('initial_deposit');
        $allTimeResults = TradingResult::query()->visibleTo($request->user())->withinTrackingPeriod();
        $allTimeProfit = (float) (clone $allTimeResults)->sum('amount');
        $allTimeDays = (clone $allTimeResults)->distinct('traded_at')->count('traded_at');
        $chartResults = (clone $allTimeResults)->with('account.robot:id,name')->orderBy('traded_at')->orderBy('sequence')->get();

        return view('dashboard', [
            'robotsCount' => Robot::query()->where('is_active', true)->when($visibleRobotIds, fn ($query) => $query->whereIn('id', $visibleRobotIds))->count(),
            'accountsCount' => TradingAccount::query()->where('is_active', true)->when($visibleRobotIds, fn ($query) => $query->whereIn('robot_id', $visibleRobotIds))->count(),
            'deposit' => $deposit,
            'monthProfit' => $monthProfit,
            'monthPercent' => $deposit > 0 ? $monthProfit / $deposit * 100 : 0,
            'dayPercent' => $deposit > 0 && $activeDays > 0 ? ($monthProfit / $activeDays) / $deposit * 100 : 0,
            'dailyAverage' => $activeDays > 0 ? $monthProfit / $activeDays : 0,
            'allTimeProfit' => $allTimeProfit,
            'allTimePercent' => $deposit > 0 ? $allTimeProfit / $deposit * 100 : 0,
            'allTimeDayPercent' => $deposit > 0 && $allTimeDays > 0 ? ($allTimeProfit / $allTimeDays) / $deposit * 100 : 0,
            'allTimeDailyAverage' => $allTimeDays > 0 ? $allTimeProfit / $allTimeDays : 0,
            'chartData' => $this->chartData($chartResults, $monthStart),
            'recentResults' => TradingResult::query()->visibleTo($request->user())->withinTrackingPeriod()->with('account.robot')->latest('traded_at')->latest('sequence')->limit(100)->get(),
        ]);
    }

    private function chartData(Collection $results, CarbonImmutable $monthStart): array
    {
        $dailyGroups = $results->groupBy(fn (TradingResult $result) => $result->traded_at->format('Y-m-d'));
        $monthDates = collect(range(1, $monthStart->daysInMonth))
            ->map(fn (int $day) => $monthStart->setDay($day));

        $runningTotal = 0.0;
        $cumulativeLabels = [];
        $cumulativeValues = [];
        foreach ($dailyGroups->sortKeys() as $date => $dayResults) {
            $runningTotal += (float) $dayResults->sum('amount');
            $cumulativeLabels[] = CarbonImmutable::parse($date)->format('d.m.Y');
            $cumulativeValues[] = round($runningTotal, 2);
        }

        $robotGroups = $results
            ->groupBy(fn (TradingResult $result) => $result->account->robot_id)
            ->map(fn (Collection $robotResults) => [
                'name' => $robotResults->first()->account->robot->name,
                'value' => round((float) $robotResults->sum('amount'), 2),
            ])
            ->sortByDesc('value')
            ->values();

        $monthlyGroups = $results
            ->groupBy(fn (TradingResult $result) => $result->traded_at->format('Y-m'))
            ->sortKeys()
            ->take(-12);

        return [
            'daily' => [
                'labels' => $monthDates->map(fn (CarbonImmutable $date) => $date->format('d.m'))->all(),
                'values' => $monthDates->map(fn (CarbonImmutable $date) => round((float) ($dailyGroups->get($date->format('Y-m-d'))?->sum('amount') ?? 0), 2))->all(),
            ],
            'cumulative' => ['labels' => $cumulativeLabels, 'values' => $cumulativeValues],
            'robots' => ['labels' => $robotGroups->pluck('name')->all(), 'values' => $robotGroups->pluck('value')->all()],
            'monthly' => [
                'labels' => $monthlyGroups->keys()->map(fn (string $month) => CarbonImmutable::createFromFormat('Y-m', $month)->translatedFormat('M Y'))->values()->all(),
                'values' => $monthlyGroups->map(fn (Collection $monthResults) => round((float) $monthResults->sum('amount'), 2))->values()->all(),
            ],
        ];
    }
}

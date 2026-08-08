<?php

namespace App\Http\Controllers;

use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Services\PortfolioStatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function data(Request $request, PortfolioStatisticsService $statisticsService): JsonResponse
    {
        $validated = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $monthStart = CarbonImmutable::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $monthStatistics = $statisticsService->calculateFor($request->user(), $monthStart, $monthEnd);
        $allTimeStatistics = $statisticsService->calculateFor($request->user());
        $monthResults = TradingResult::query()->visibleTo($request->user())->withinTrackingPeriod()->whereBetween('traded_at', [$monthStart, $monthEnd]);
        $activeDays = (clone $monthResults)->distinct('traded_at')->count('traded_at');
        $legacyMonthProfit = (float) (clone $monthResults)->sum('amount');

        return response()->json([
            'monthProfit' => $monthStatistics['kpi']['profit'],
            'monthPercent' => $monthStatistics['kpi']['return_percent'],
            'dayPercent' => $monthStatistics['kpi']['average_daily_percent'],
            'dailyAverage' => $activeDays > 0 ? $legacyMonthProfit / $activeDays : 0,
            'chartData' => $this->chartData($monthStatistics, $allTimeStatistics),
        ]);
    }

    public function index(Request $request, PortfolioStatisticsService $statisticsService): View
    {
        $requestedMonth = $request->query('month');
        $month = is_string($requestedMonth) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedMonth)
            ? $requestedMonth
            : now()->format('Y-m');
        $monthStart = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $monthStatistics = $statisticsService->calculateFor($request->user(), $monthStart, $monthEnd);
        $allTimeStatistics = $statisticsService->calculateFor($request->user());
        $todayStatistics = $statisticsService->calculateFor($request->user(), CarbonImmutable::today(), CarbonImmutable::today());
        $monthResults = TradingResult::query()->visibleTo($request->user())->withinTrackingPeriod()->whereBetween('traded_at', [$monthStart, $monthEnd]);
        $activeDays = (clone $monthResults)->distinct('traded_at')->count('traded_at');
        $legacyMonthProfit = (float) (clone $monthResults)->sum('amount');
        $visibleRobotIds = $request->user()->role->value === 'viewer' ? $request->user()->robots()->pluck('robots.id') : null;
        $deposit = (float) TradingAccount::query()->where('is_active', true)->when($visibleRobotIds, fn ($query) => $query->whereIn('robot_id', $visibleRobotIds))->sum('initial_deposit');
        $allTimeResults = TradingResult::query()->visibleTo($request->user())->withinTrackingPeriod();
        $allTimeDays = (clone $allTimeResults)->distinct('traded_at')->count('traded_at');
        $legacyAllTimeProfit = (float) (clone $allTimeResults)->sum('amount');
        $todayResults = TradingResult::query()->visibleTo($request->user())->withinTrackingPeriod()->whereDate('traded_at', today());
        $todayOperations = (clone $todayResults)->count();
        $legacyTodayProfit = (float) (clone $todayResults)->sum('amount');

        return view('dashboard', [
            'robotsCount' => Robot::query()->where('is_active', true)->when($visibleRobotIds, fn ($query) => $query->whereIn('id', $visibleRobotIds))->count(),
            'accountsCount' => TradingAccount::query()->where('is_active', true)->when($visibleRobotIds, fn ($query) => $query->whereIn('robot_id', $visibleRobotIds))->count(),
            'deposit' => $deposit,
            'month' => $month,
            'monthLabel' => $monthStart->translatedFormat('F Y'),
            'monthProfit' => $monthStatistics['kpi']['profit'],
            'monthPercent' => $monthStatistics['kpi']['return_percent'],
            'dayPercent' => $monthStatistics['kpi']['average_daily_percent'],
            'dailyAverage' => $activeDays > 0 ? $legacyMonthProfit / $activeDays : 0,
            'allTimeProfit' => $allTimeStatistics['kpi']['profit'],
            'allTimePercent' => $allTimeStatistics['kpi']['return_percent'],
            'allTimeDayPercent' => $allTimeStatistics['kpi']['average_daily_percent'],
            'allTimeDailyAverage' => $allTimeDays > 0 ? $legacyAllTimeProfit / $allTimeDays : 0,
            'todayProfit' => $todayStatistics['kpi']['profit'],
            'todayPercent' => $todayStatistics['kpi']['return_percent'],
            'todayOperations' => $todayOperations,
            'todayAverage' => $todayOperations > 0 ? $legacyTodayProfit / $todayOperations : 0,
            'chartData' => $this->chartData($monthStatistics, $allTimeStatistics),
            'recentResults' => TradingResult::query()->visibleTo($request->user())->withinTrackingPeriod()->with('account.robot')->latest('traded_at')->latest('sequence')->limit(100)->get(),
        ]);
    }

    private function chartData(array $monthStatistics, array $allTimeStatistics): array
    {
        $daily = $monthStatistics['charts']['daily'];
        $growth = $allTimeStatistics['charts']['growth'];
        $monthly = $allTimeStatistics['charts']['monthly'];
        $robots = $allTimeStatistics['charts']['robots'];

        return [
            'daily' => [
                'labels' => collect($daily['labels'])->map(fn (string $date) => CarbonImmutable::parse($date)->format('d.m'))->all(),
                'values' => $daily['values'],
            ],
            'cumulative' => [
                'labels' => collect($growth['labels'])->map(fn (string $date) => CarbonImmutable::parse($date)->format('d.m.Y'))->all(),
                'values' => $growth['values'],
            ],
            'robots' => [
                'labels' => $robots['labels'],
                'values' => $robots['profit_values'],
            ],
            'monthly' => [
                'labels' => collect($monthly['labels'])->take(-12)->map(fn (string $month) => CarbonImmutable::createFromFormat('Y-m', $month)->translatedFormat('M Y'))->values()->all(),
                'values' => collect($monthly['values'])->take(-12)->values()->all(),
            ],
        ];
    }
}

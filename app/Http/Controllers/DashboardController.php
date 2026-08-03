<?php

namespace App\Http\Controllers;

use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
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
        $monthResults = TradingResult::query()->withinTrackingPeriod()->whereBetween('traded_at', [$monthStart, $monthEnd]);
        $monthProfit = (float) (clone $monthResults)->sum('amount');
        $activeDays = (clone $monthResults)->distinct('traded_at')->count('traded_at');
        $deposit = (float) TradingAccount::query()->where('is_active', true)->sum('initial_deposit');
        $allTimeResults = TradingResult::query()->withinTrackingPeriod();
        $allTimeProfit = (float) (clone $allTimeResults)->sum('amount');
        $allTimeDays = (clone $allTimeResults)->distinct('traded_at')->count('traded_at');

        return view('dashboard', [
            'robotsCount' => Robot::query()->where('is_active', true)->count(),
            'accountsCount' => TradingAccount::query()->where('is_active', true)->count(),
            'deposit' => $deposit,
            'monthProfit' => $monthProfit,
            'monthPercent' => $deposit > 0 ? $monthProfit / $deposit * 100 : 0,
            'dayPercent' => $deposit > 0 && $activeDays > 0 ? ($monthProfit / $activeDays) / $deposit * 100 : 0,
            'dailyAverage' => $activeDays > 0 ? $monthProfit / $activeDays : 0,
            'allTimeProfit' => $allTimeProfit,
            'allTimePercent' => $deposit > 0 ? $allTimeProfit / $deposit * 100 : 0,
            'allTimeDayPercent' => $deposit > 0 && $allTimeDays > 0 ? ($allTimeProfit / $allTimeDays) / $deposit * 100 : 0,
            'allTimeDailyAverage' => $allTimeDays > 0 ? $allTimeProfit / $allTimeDays : 0,
            'recentResults' => TradingResult::query()->withinTrackingPeriod()->with('account.robot')->latest('traded_at')->latest('sequence')->limit(100)->get(),
        ]);
    }
}

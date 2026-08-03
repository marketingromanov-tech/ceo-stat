<?php

namespace App\Http\Controllers;

use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthResults = TradingResult::query()->whereBetween('traded_at', [$monthStart, $monthEnd]);
        $monthProfit = (float) (clone $monthResults)->sum('amount');
        $activeDays = (clone $monthResults)->distinct('traded_at')->count('traded_at');
        $deposit = (float) TradingAccount::query()->where('is_active', true)->sum('initial_deposit');

        return view('dashboard', [
            'robotsCount' => Robot::query()->where('is_active', true)->count(),
            'accountsCount' => TradingAccount::query()->where('is_active', true)->count(),
            'deposit' => $deposit,
            'monthProfit' => $monthProfit,
            'monthPercent' => $deposit > 0 ? $monthProfit / $deposit * 100 : 0,
            'dailyAverage' => $activeDays > 0 ? $monthProfit / $activeDays : 0,
            'recentResults' => TradingResult::query()->with('account.robot')->latest('traded_at')->limit(8)->get(),
        ]);
    }
}

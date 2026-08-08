<?php

namespace App\Services;

use App\Models\FinancialOperation;
use App\Models\TradingAccount;

class AccountBalanceService
{
    public function summary(TradingAccount $account): array
    {
        $tradingProfit = (float) $account->results()
            ->withinTrackingPeriod()
            ->sum('amount');

        $operations = $account->financialOperations();

        if ($account->robot?->tracking_started_at) {
            $operations->whereDate('operation_date', '>=', $account->robot->tracking_started_at);
        }

        $totals = (clone $operations)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $deposits = (float) ($totals[FinancialOperation::TYPE_DEPOSIT] ?? 0);
        $withdrawals = (float) ($totals[FinancialOperation::TYPE_WITHDRAWAL] ?? 0);
        $commissions = (float) ($totals[FinancialOperation::TYPE_COMMISSION] ?? 0);
        $expenses = (float) ($totals[FinancialOperation::TYPE_EXPENSE] ?? 0);
        $adjustments = (float) ($totals[FinancialOperation::TYPE_ADJUSTMENT] ?? 0);
        $initialDeposit = (float) $account->initial_deposit;

        $currentBalance = round(
            $initialDeposit + $tradingProfit + $deposits - $withdrawals - $commissions - $expenses + $adjustments,
            2
        );

        return [
            'initial_deposit' => round($initialDeposit, 2),
            'trading_profit' => round($tradingProfit, 2),
            'deposits' => round($deposits, 2),
            'withdrawals' => round($withdrawals, 2),
            'commissions' => round($commissions, 2),
            'expenses' => round($expenses, 2),
            'adjustments' => round($adjustments, 2),
            'current_balance' => $currentBalance,
        ];
    }

    public function refreshCurrentBalance(TradingAccount $account): array
    {
        $summary = $this->summary($account);
        $account->update(['current_balance' => $summary['current_balance']]);

        return $summary;
    }
}

<?php

namespace Tests\Unit;

use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Models\FinancialOperation;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Services\RobotStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private Robot $robot;
    private TradingAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->robot = Robot::query()->create(['name' => 'Statistics robot', 'tracking_started_at' => '2026-01-01']);
        $this->account = TradingAccount::query()->create([
            'robot_id' => $this->robot->id,
            'name' => 'Main',
            'platform' => 'manual',
            'currency' => 'USD',
            'initial_deposit' => 1000,
        ]);
    }

    public function test_aggregates_results_by_day_and_classifies_profitable_losing_and_zero_days(): void
    {
        $this->addResult('2026-01-01', 100, 1);
        $this->addResult('2026-01-01', -40, 2);
        $this->addResult('2026-01-02', -20);
        $this->addResult('2026-01-03', 0);

        $statistics = $this->statistics();

        $this->assertSame([60.0, -20.0, 0.0], $statistics['charts']['daily']['values']);
        $this->assertSame(3, $statistics['kpi']['working_days']);
        $this->assertSame(1, $statistics['quality']['profitable_days']);
        $this->assertSame(1, $statistics['quality']['losing_days']);
        $this->assertSame(1, $statistics['quality']['zero_days']);
        $this->assertSame(3.0, $statistics['kpi']['profit_factor']);
        $this->assertSame('finite', $statistics['kpi']['profit_factor_state']);
    }

    public function test_calculates_streaks_and_zero_day_breaks_a_streak(): void
    {
        foreach ([10, 20, -5, -6, -7, 0, 8] as $index => $amount) {
            $this->addResult('2026-01-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), $amount);
        }

        $quality = $this->statistics()['quality'];
        $this->assertSame(2, $quality['max_winning_streak']);
        $this->assertSame(3, $quality['max_losing_streak']);
        $this->assertSame(['type' => 'winning', 'length' => 1], $quality['current_streak']);
    }

    public function test_calculates_maximum_drawdown_from_previous_peak(): void
    {
        foreach ([100, -50, -100, 200] as $index => $amount) {
            $this->addResult('2026-01-0'.($index + 1), $amount);
        }

        $kpi = $this->statistics()['kpi'];
        $this->assertSame(150.0, $kpi['max_drawdown']);
        $this->assertSame(13.636, $kpi['max_drawdown_percent']);
    }

    public function test_last_30_uses_latest_thirty_working_days(): void
    {
        for ($day = 1; $day <= 35; $day++) {
            $this->addResult(now()->startOfYear()->addDays($day)->toDateString(), $day <= 5 ? 100 : ($day % 2 === 0 ? 10 : -5));
        }

        $last30 = $this->statistics()['last_30'];
        $this->assertSame(75.0, $last30['profit']);
        $this->assertSame(2.5, $last30['average_result']);
        $this->assertSame(15, $last30['profitable_days']);
        $this->assertSame(15, $last30['losing_days']);
        $this->assertSame(50.0, $last30['profitable_days_percent']);
    }

    public function test_respects_tracking_period_and_excludes_non_working_periods(): void
    {
        $this->robot->update(['tracking_started_at' => '2026-01-02']);
        $this->addResult('2026-01-01', 100);
        $this->addResult('2026-01-02', 50);
        $this->addResult('2026-01-03', 75);
        RobotStatusPeriod::query()->create([
            'robot_id' => $this->robot->id,
            'status' => RobotStatusPeriod::STATUS_MAINTENANCE,
            'starts_at' => '2026-01-03',
            'ends_at' => '2026-01-03',
        ]);

        $statistics = $this->statistics();
        $this->assertSame(50.0, $statistics['kpi']['profit']);
        $this->assertSame(1, $statistics['kpi']['working_days']);
    }

    public function test_empty_data_and_zero_deposit_do_not_divide_by_zero(): void
    {
        $this->account->update(['initial_deposit' => 0]);
        $empty = $this->statistics();
        $this->assertSame(0.0, $empty['kpi']['return_percent']);
        $this->assertNull($empty['kpi']['profit_factor']);
        $this->assertSame('undefined', $empty['kpi']['profit_factor_state']);
        $this->assertSame(0, $empty['kpi']['working_days']);
        $this->assertSame([], $empty['charts']['daily']['values']);

        $this->addResult('2026-01-01', 25);
        $statistics = $this->statistics();
        $this->assertSame(0.0, $statistics['kpi']['return_percent']);
        $this->assertSame(0.0, $statistics['kpi']['average_daily_percent']);
        $this->assertNull($statistics['kpi']['profit_factor']);
        $this->assertSame('infinite', $statistics['kpi']['profit_factor_state']);
    }

    public function test_custom_range_is_inclusive_and_excludes_results_outside_it(): void
    {
        $this->addResult('2026-01-01', 100);
        $this->addResult('2026-01-02', 20);
        $this->addResult('2026-01-03', -5);
        $this->addResult('2026-01-04', 200);

        $statistics = app(RobotStatisticsService::class)->calculate(
            $this->robot->fresh(),
            \Carbon\CarbonImmutable::parse('2026-01-02'),
            \Carbon\CarbonImmutable::parse('2026-01-03'),
        );

        $this->assertSame(['2026-01-02', '2026-01-03'], $statistics['charts']['daily']['labels']);
        $this->assertSame([20.0, -5.0], $statistics['charts']['daily']['values']);
        $this->assertSame(15.0, $statistics['kpi']['profit']);
        $this->assertSame(4.0, $statistics['kpi']['profit_factor']);
        $this->assertSame(5.0, $statistics['kpi']['max_drawdown']);
        $this->assertSame(2, $statistics['last_30']['days']);
    }

    public function test_range_before_tracking_start_is_trimmed_and_empty_range_is_safe(): void
    {
        $this->robot->update(['tracking_started_at' => '2026-01-03']);
        $this->addResult('2026-01-02', 100);
        $this->addResult('2026-01-03', 30);

        $statistics = app(RobotStatisticsService::class)->calculate(
            $this->robot->fresh(),
            \Carbon\CarbonImmutable::parse('2025-12-01'),
            \Carbon\CarbonImmutable::parse('2026-01-03'),
        );
        $empty = app(RobotStatisticsService::class)->calculate(
            $this->robot->fresh(),
            \Carbon\CarbonImmutable::parse('2026-02-01'),
            \Carbon\CarbonImmutable::parse('2026-02-02'),
        );

        $this->assertSame('2026-01-03', $statistics['period']['from']);
        $this->assertSame(30.0, $statistics['kpi']['profit']);
        $this->assertFalse($empty['period']['has_data']);
        $this->assertSame([], $empty['charts']['growth']['values']);
    }

    public function test_opening_balance_uses_prior_profit_and_financial_operations_without_treating_them_as_profit(): void
    {
        $this->addResult('2026-01-01', 100);
        $this->addResult('2026-01-03', 50);
        $this->financialOperation('2026-01-02', FinancialOperation::TYPE_DEPOSIT, 200);
        $this->financialOperation('2026-01-02', FinancialOperation::TYPE_WITHDRAWAL, 50);

        $statistics = app(RobotStatisticsService::class)->calculate(
            $this->robot->fresh(),
            \Carbon\CarbonImmutable::parse('2026-01-03'),
            \Carbon\CarbonImmutable::parse('2026-01-03'),
        );

        $this->assertSame(1250.0, $statistics['period']['opening_balance']);
        $this->assertSame(50.0, $statistics['kpi']['profit']);
        $this->assertSame(4.0, $statistics['kpi']['return_percent']);
        $this->assertSame([1300.0], $statistics['charts']['growth']['values']);
    }

    public function test_cash_flows_change_growth_balance_without_creating_profit_or_drawdown(): void
    {
        $this->addResult('2026-01-01', 100);
        $this->addResult('2026-01-02', 0);
        $this->financialOperation('2026-01-02', FinancialOperation::TYPE_WITHDRAWAL, 500);

        $statistics = app(RobotStatisticsService::class)->calculate(
            $this->robot->fresh(),
            \Carbon\CarbonImmutable::parse('2026-01-01'),
            \Carbon\CarbonImmutable::parse('2026-01-02'),
        );

        $this->assertSame(100.0, $statistics['kpi']['profit']);
        $this->assertSame(0.0, $statistics['kpi']['max_drawdown']);
        $this->assertSame([1100.0, 600.0], $statistics['charts']['growth']['values']);
    }

    public function test_last_n_profit_factor_drawdown_and_monthly_chart_are_limited_to_range(): void
    {
        for ($day = 1; $day <= 35; $day++) {
            $this->addResult(\Carbon\CarbonImmutable::parse('2026-01-01')->addDays($day - 1)->toDateString(), $day < 31 ? 100 : ($day === 32 ? -20 : 10));
        }

        $statistics = app(RobotStatisticsService::class)->calculate(
            $this->robot->fresh(),
            \Carbon\CarbonImmutable::parse('2026-01-31'),
            \Carbon\CarbonImmutable::parse('2026-02-04'),
        );

        $this->assertSame(5, $statistics['last_30']['days']);
        $this->assertSame(2.0, $statistics['kpi']['profit_factor']);
        $this->assertSame(20.0, $statistics['kpi']['max_drawdown']);
        $this->assertSame(['2026-01', '2026-02'], $statistics['charts']['monthly']['labels']);
    }

    private function addResult(string $date, float $amount, int $sequence = 1): void
    {
        TradingResult::query()->create([
            'trading_account_id' => $this->account->id,
            'traded_at' => $date,
            'sequence' => $sequence,
            'amount' => $amount,
            'source' => 'manual',
        ]);
    }

    private function financialOperation(string $date, string $type, float $amount): void
    {
        FinancialOperation::query()->create([
            'trading_account_id' => $this->account->id,
            'type' => $type,
            'amount' => $amount,
            'currency' => 'USD',
            'operation_date' => $date,
            'source' => 'manual',
        ]);
    }

    private function statistics(): array
    {
        return app(RobotStatisticsService::class)->calculate($this->robot->fresh());
    }
}

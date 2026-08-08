<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\FinancialOperation;
use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use App\Services\PortfolioStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_combines_two_robots_into_one_portfolio_day(): void
    {
        $admin = $this->user(UserRole::Admin);
        $alpha = $this->robot('Alpha');
        $beta = $this->robot('Beta');
        $this->addResult($alpha, '2026-01-10', 100);
        $this->addResult($beta, '2026-01-10', 50);

        $statistics = $this->statistics($admin);

        $this->assertSame(['2026-01-10'], $statistics['charts']['daily']['labels']);
        $this->assertSame([150.0], $statistics['charts']['daily']['values']);
        $this->assertSame([2150.0], $statistics['charts']['growth']['values']);
        $this->assertSame(['2026-01'], $statistics['charts']['monthly']['labels']);
        $this->assertSame([150.0], $statistics['charts']['monthly']['values']);
        $this->assertSame(['Alpha', 'Beta'], $statistics['charts']['robots']['labels']);
        $this->assertSame([66.667, 33.333], $statistics['charts']['robots']['values']);
        $this->assertSame([100.0, 50.0], $statistics['charts']['robots']['profit_values']);
        $this->assertSame(1, $statistics['kpi']['working_days']);
        $this->assertSame(2, $statistics['portfolio']['robots_count']);
    }

    public function test_offsets_profit_and_loss_before_calculating_portfolio_kpis(): void
    {
        $admin = $this->user(UserRole::Admin);
        $alpha = $this->robot('Alpha');
        $beta = $this->robot('Beta');
        $this->addResult($alpha, '2026-01-10', 100);
        $this->addResult($beta, '2026-01-10', -80);

        $statistics = $this->statistics($admin);

        $this->assertSame([20.0], $statistics['charts']['daily']['values']);
        $this->assertSame(1, $statistics['quality']['profitable_days']);
        $this->assertSame(0, $statistics['quality']['losing_days']);
        $this->assertSame(20.0, $statistics['kpi']['gross_profit']);
        $this->assertSame(0.0, $statistics['kpi']['gross_loss']);
        $this->assertSame('infinite', $statistics['kpi']['profit_factor_state']);
    }

    public function test_equal_opposite_results_create_a_zero_working_day(): void
    {
        $admin = $this->user(UserRole::Admin);
        $alpha = $this->robot('Alpha');
        $beta = $this->robot('Beta');
        $this->addResult($alpha, '2026-01-10', 100);
        $this->addResult($beta, '2026-01-10', -100);

        $statistics = $this->statistics($admin);

        $this->assertSame([0.0], $statistics['charts']['daily']['values']);
        $this->assertSame(1, $statistics['kpi']['working_days']);
        $this->assertSame(1, $statistics['quality']['zero_days']);
        $this->assertSame('undefined', $statistics['kpi']['profit_factor_state']);
    }

    public function test_applies_each_robots_tracking_period_before_combining_days(): void
    {
        $admin = $this->user(UserRole::Admin);
        $alpha = $this->robot('Alpha', '2026-01-02');
        $beta = $this->robot('Beta', '2026-01-01');
        $this->addResult($alpha, '2026-01-01', 100);
        $this->addResult($alpha, '2026-01-02', 20);
        $this->addResult($beta, '2026-01-01', 30);

        $statistics = $this->statistics($admin);

        $this->assertSame(['2026-01-01', '2026-01-02'], $statistics['charts']['daily']['labels']);
        $this->assertSame([30.0, 20.0], $statistics['charts']['daily']['values']);
        $this->assertSame(50.0, $statistics['kpi']['profit']);
    }

    public function test_applies_non_working_periods_per_robot_before_combining_days(): void
    {
        $admin = $this->user(UserRole::Admin);
        $alpha = $this->robot('Alpha');
        $beta = $this->robot('Beta');
        $this->addResult($alpha, '2026-01-10', 100);
        $this->addResult($beta, '2026-01-10', -20);
        RobotStatusPeriod::query()->create([
            'robot_id' => $alpha->id,
            'status' => RobotStatusPeriod::STATUS_MAINTENANCE,
            'starts_at' => '2026-01-10',
            'ends_at' => '2026-01-10',
        ]);

        $statistics = $this->statistics($admin);

        $this->assertSame([-20.0], $statistics['charts']['daily']['values']);
        $this->assertSame(0, $statistics['quality']['profitable_days']);
        $this->assertSame(1, $statistics['quality']['losing_days']);
        $this->assertSame(20.0, $statistics['kpi']['max_drawdown']);
    }

    public function test_viewer_portfolio_only_contains_assigned_robots(): void
    {
        $viewer = $this->user(UserRole::Viewer);
        $assigned = $this->robot('Assigned');
        $hidden = $this->robot('Hidden');
        $viewer->robots()->attach($assigned);
        $this->addResult($assigned, '2026-01-10', 25);
        $this->addResult($hidden, '2026-01-10', 900);

        $statistics = $this->statistics($viewer);

        $this->assertSame([25.0], $statistics['charts']['daily']['values']);
        $this->assertSame([$assigned->id], $statistics['portfolio']['robot_ids']);
        $this->assertSame(['Assigned'], $statistics['charts']['robots']['labels']);
        $this->assertSame([25.0], $statistics['charts']['robots']['profit_values']);
    }

    public function test_empty_portfolio_returns_safe_empty_statistics(): void
    {
        $viewer = $this->user(UserRole::Viewer);

        $statistics = $this->statistics($viewer);

        $this->assertSame(0, $statistics['portfolio']['robots_count']);
        $this->assertSame(0.0, $statistics['kpi']['profit']);
        $this->assertSame(0, $statistics['kpi']['working_days']);
        $this->assertSame([], $statistics['charts']['daily']['values']);
        $this->assertSame([], $statistics['charts']['growth']['values']);
        $this->assertSame([], $statistics['charts']['robots']['labels']);
        $this->assertFalse($statistics['period']['has_data']);
        $this->assertSame('undefined', $statistics['kpi']['profit_factor_state']);
    }

    public function test_financial_operations_change_portfolio_balance_but_not_profit_or_drawdown(): void
    {
        $admin = $this->user(UserRole::Admin);
        $alpha = $this->robot('Alpha');
        $beta = $this->robot('Beta');
        $this->addResult($alpha, '2026-01-10', 100);
        $this->addResult($beta, '2026-01-10', 0);
        FinancialOperation::query()->create([
            'trading_account_id' => $alpha->account->id,
            'type' => FinancialOperation::TYPE_WITHDRAWAL,
            'amount' => 500,
            'currency' => 'USD',
            'operation_date' => '2026-01-10',
            'source' => 'manual',
        ]);

        $statistics = $this->statistics($admin);

        $this->assertSame(100.0, $statistics['kpi']['profit']);
        $this->assertSame(0.0, $statistics['kpi']['max_drawdown']);
        $this->assertSame([1600.0], $statistics['charts']['growth']['values']);
    }

    public function test_selected_period_limits_all_portfolio_chart_datasets(): void
    {
        $admin = $this->user(UserRole::Admin);
        $alpha = $this->robot('Alpha');
        $beta = $this->robot('Beta');
        $this->addResult($alpha, '2026-01-31', 500);
        $this->addResult($alpha, '2026-02-01', 100);
        $this->addResult($beta, '2026-02-01', -40);

        $statistics = app(PortfolioStatisticsService::class)->calculateFor(
            $admin,
            \Carbon\CarbonImmutable::parse('2026-02-01'),
            \Carbon\CarbonImmutable::parse('2026-02-28'),
        );

        $this->assertSame(['2026-02-01'], $statistics['charts']['daily']['labels']);
        $this->assertSame([60.0], $statistics['charts']['daily']['values']);
        $this->assertSame(['2026-02'], $statistics['charts']['monthly']['labels']);
        $this->assertSame([100.0, -40.0], $statistics['charts']['robots']['profit_values']);
        $this->assertSame([166.667, -66.667], $statistics['charts']['robots']['values']);
    }

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function robot(string $name, string $trackingStartedAt = '2026-01-01'): Robot
    {
        $robot = Robot::query()->create(['name' => $name, 'tracking_started_at' => $trackingStartedAt]);
        TradingAccount::query()->create([
            'robot_id' => $robot->id,
            'name' => $name.' account',
            'platform' => 'manual',
            'currency' => 'USD',
            'initial_deposit' => 1000,
        ]);

        return $robot;
    }

    private function addResult(Robot $robot, string $date, float $amount): void
    {
        TradingResult::query()->create([
            'trading_account_id' => $robot->account->id,
            'traded_at' => $date,
            'sequence' => 1,
            'amount' => $amount,
            'source' => 'manual',
        ]);
    }

    private function statistics(User $user): array
    {
        return app(PortfolioStatisticsService::class)->calculateFor($user);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\ResultCalendar;
use App\Models\FinancialOperation;
use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use App\Services\PortfolioStatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-08-08');
    }

    public function test_admin_dashboard_kpis_match_portfolio_statistics(): void
    {
        $this->assertDashboardMatchesPortfolioFor($this->user(UserRole::Admin));
    }

    public function test_operator_dashboard_kpis_match_portfolio_statistics(): void
    {
        $this->assertDashboardMatchesPortfolioFor($this->user(UserRole::Operator));
    }

    public function test_viewer_dashboard_only_uses_assigned_portfolio(): void
    {
        $viewer = $this->user(UserRole::Viewer);
        [$assigned, $assignedAccount] = $this->robot('Assigned dashboard');
        [, $hiddenAccount] = $this->robot('Hidden dashboard');
        $viewer->robots()->attach($assigned);
        $this->addResult($assignedAccount, '2026-08-03', 25);
        $this->addResult($hiddenAccount, '2026-08-03', 900);

        $statistics = $this->monthStatistics($viewer);
        $response = $this->actingAs($viewer)->getJson('/dashboard-data?month=2026-08')->assertOk();
        $this->assertEquals($statistics['kpi']['profit'], $response->json('monthProfit'));
        $response->assertJsonMissing(['monthProfit' => 925]);
    }

    public function test_financial_operation_does_not_increase_dashboard_trading_profit(): void
    {
        $admin = $this->user(UserRole::Admin);
        [, $account] = $this->robot('Cash flow dashboard');
        $this->addResult($account, '2026-08-03', 100);
        FinancialOperation::query()->create([
            'trading_account_id' => $account->id,
            'type' => FinancialOperation::TYPE_DEPOSIT,
            'amount' => 500,
            'currency' => 'USD',
            'operation_date' => '2026-08-03',
            'source' => 'manual',
        ]);

        $this->actingAs($admin)->getJson('/dashboard-data?month=2026-08')
            ->assertOk()
            ->assertJsonPath('monthProfit', 100);
    }

    public function test_non_working_period_is_excluded_from_dashboard_kpis_and_daily_dataset(): void
    {
        $admin = $this->user(UserRole::Admin);
        [$robot, $account] = $this->robot('Paused dashboard');
        $this->addResult($account, '2026-08-03', 100);
        $this->addResult($account, '2026-08-04', 20);
        RobotStatusPeriod::query()->create(['robot_id' => $robot->id, 'status' => RobotStatusPeriod::STATUS_MAINTENANCE, 'starts_at' => '2026-08-03', 'ends_at' => '2026-08-03']);

        $this->actingAs($admin)->getJson('/dashboard-data?month=2026-08')
            ->assertOk()
            ->assertJsonPath('monthProfit', 20)
            ->assertJsonPath('chartData.daily.labels', ['04.08'])
            ->assertJsonPath('chartData.daily.values', [20]);
    }

    public function test_dashboard_chart_datasets_are_adapted_from_portfolio_statistics(): void
    {
        $admin = $this->user(UserRole::Admin);
        [, $account] = $this->robot('Chart dashboard');
        $this->addResult($account, '2026-07-01', 50);
        $this->addResult($account, '2026-08-03', 25);
        $month = $this->monthStatistics($admin);
        $allTime = app(PortfolioStatisticsService::class)->calculateFor($admin);

        $response = $this->actingAs($admin)->getJson('/dashboard-data?month=2026-08')->assertOk();
        $this->assertEquals($month['charts']['daily']['values'], $response->json('chartData.daily.values'));
        $this->assertEquals($allTime['charts']['growth']['values'], $response->json('chartData.cumulative.values'));
        $this->assertEquals($allTime['charts']['monthly']['values'], $response->json('chartData.monthly.values'));
        $this->assertEquals($allTime['charts']['robots']['profit_values'], $response->json('chartData.robots.values'));
    }

    public function test_dashboard_still_renders_global_calendar(): void
    {
        $admin = $this->user(UserRole::Admin);

        $this->actingAs($admin)->get('/')->assertOk()->assertSeeLivewire(ResultCalendar::class);
    }

    private function assertDashboardMatchesPortfolioFor(User $user): void
    {
        [, $account] = $this->robot($user->role->value.' dashboard');
        $this->addResult($account, '2026-08-03', 125);
        $statistics = $this->monthStatistics($user);

        $response = $this->actingAs($user)->getJson('/dashboard-data?month=2026-08')->assertOk();
        $this->assertEquals($statistics['kpi']['profit'], $response->json('monthProfit'));
        $this->assertEquals($statistics['kpi']['return_percent'], $response->json('monthPercent'));
        $this->assertEquals($statistics['kpi']['average_daily_percent'], $response->json('dayPercent'));
    }

    private function monthStatistics(User $user): array
    {
        return app(PortfolioStatisticsService::class)->calculateFor($user, CarbonImmutable::parse('2026-08-01'), CarbonImmutable::parse('2026-08-31'));
    }

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function robot(string $name): array
    {
        $robot = Robot::query()->create(['name' => $name, 'tracking_started_at' => '2026-01-01']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => $name.' account', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'is_active' => true]);

        return [$robot, $account];
    }

    private function addResult(TradingAccount $account, string $date, float $amount): void
    {
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => $date, 'sequence' => 1, 'amount' => $amount, 'source' => 'manual']);
    }
}

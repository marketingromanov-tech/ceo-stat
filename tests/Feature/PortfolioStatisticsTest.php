<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\PortfolioStatistics;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use App\Services\PortfolioStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortfolioStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_portfolio_statistics_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $robot = $this->robot('Alpha');
        $this->addResult($robot, '2026-08-01', 125);

        Livewire::actingAs($admin)->test(PortfolioStatistics::class)
            ->assertOk()
            ->assertSee('Общая статистика')
            ->assertSee('Качество торговли')
            ->assertSee('Alpha')
            ->assertSee('125,00')
            ->assertSeeHtml('id="portfolio-statistics-chart-data"')
            ->assertSeeHtml('id="portfolio-growth-chart"')
            ->assertSeeHtml('id="portfolio-daily-chart"')
            ->assertSeeHtml('id="portfolio-monthly-chart"')
            ->assertSeeHtml('id="portfolio-robots-chart"');
    }

    public function test_portfolio_route_is_available_to_admin_and_operator(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);

        $this->actingAs($admin)->get(route('portfolio.statistics'))->assertOk()->assertSee('Общая статистика');
        $this->actingAs($operator)->get(route('portfolio.statistics'))->assertOk()->assertSee('Общая статистика');
    }

    public function test_navigation_contains_portfolio_link(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Портфель')
            ->assertSee(route('portfolio.statistics'), false);
    }

    public function test_admin_route_contains_all_robots_and_matches_portfolio_service(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $alpha = $this->robot('Admin Alpha');
        $beta = $this->robot('Admin Beta');
        $this->addResult($alpha, '2026-08-01', 100);
        $this->addResult($beta, '2026-08-01', -25);
        $statistics = app(PortfolioStatisticsService::class)->calculateFor($admin);

        $this->actingAs($admin)->get(route('portfolio.statistics'))
            ->assertOk()
            ->assertSee('Admin Alpha')
            ->assertSee('Admin Beta')
            ->assertSee(number_format($statistics['kpi']['profit'], 2, ',', ' '));
    }

    public function test_viewer_only_sees_assigned_robots(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'is_active' => true]);
        $assigned = $this->robot('Assigned robot');
        $hidden = $this->robot('Hidden robot');
        $viewer->robots()->attach($assigned);
        $this->addResult($assigned, '2026-08-01', 25);
        $this->addResult($hidden, '2026-08-01', 900);

        Livewire::actingAs($viewer)->test(PortfolioStatistics::class)
            ->assertOk()
            ->assertSee('Assigned robot')
            ->assertDontSee('Hidden robot')
            ->assertSee('25,00')
            ->assertDontSee('900,00')
            ->assertSee('Assigned robot')
            ->assertDontSee('Hidden robot');
    }

    public function test_viewer_route_does_not_expose_unassigned_robot_data(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'is_active' => true]);
        $assigned = $this->robot('Route assigned');
        $hidden = $this->robot('Route hidden');
        $viewer->robots()->attach($assigned);
        $this->addResult($assigned, '2026-08-01', 40);
        $this->addResult($hidden, '2026-08-01', 777);

        $this->actingAs($viewer)->get(route('portfolio.statistics'))
            ->assertOk()
            ->assertSee('Route assigned')
            ->assertDontSee('Route hidden')
            ->assertSee('40,00')
            ->assertDontSee('777,00');
    }

    public function test_period_changes_and_recalculates_portfolio_statistics(): void
    {
        $this->travelTo('2026-08-08');
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $robot = $this->robot('Period robot', '2026-01-01');
        $this->addResult($robot, '2026-07-01', 500);
        $this->addResult($robot, '2026-08-08', 20);

        Livewire::actingAs($admin)->test(PortfolioStatistics::class)
            ->assertSee('520,00')
            ->call('selectStatisticsPeriod', '7_days')
            ->assertSet('statisticsPeriod', '7_days')
            ->assertSet('appliedStatisticsStartDate', '2026-08-02')
            ->assertSet('appliedStatisticsEndDate', '2026-08-08')
            ->assertSee('02.08.2026 — 08.08.2026')
            ->assertSee('20,00')
            ->assertDontSee('520,00')
            ->assertSee('2026-08-08')
            ->assertDontSee('2026-07-01');
    }

    public function test_empty_portfolio_shows_chart_empty_states_without_canvas_errors(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'is_active' => true]);

        Livewire::actingAs($viewer)->test(PortfolioStatistics::class)
            ->assertOk()
            ->assertSee('За выбранный период данных нет')
            ->assertSeeHtml('id="portfolio-statistics-chart-data"')
            ->assertDontSeeHtml('id="portfolio-growth-chart"')
            ->assertDontSeeHtml('id="portfolio-daily-chart"')
            ->assertDontSeeHtml('id="portfolio-monthly-chart"')
            ->assertDontSeeHtml('id="portfolio-robots-chart"');
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
}

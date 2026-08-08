<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\RobotDetail;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RobotDetailStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cards_follow_open_calendar_month_and_selected_day(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Alpha', 'tracking_started_at' => '2026-07-20']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 20000, 'current_balance' => 20150]);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-07-20', 'sequence' => 1, 'amount' => 100, 'source' => 'manual']);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-07-21', 'sequence' => 1, 'amount' => 50, 'source' => 'manual']);

        Livewire::actingAs($operator)->test(RobotDetail::class, ['robot' => $robot])
            ->call('updateCalendarPeriod', '2026-07', '2026-07-20')
            ->assertSee('150,00')
            ->assertSee('0,750%')
            ->assertSee('0,375%')
            ->assertSee('75,00');
    }

    public function test_statistics_charts_receive_daily_data_and_infinite_profit_factor_is_explicit(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Charts', 'tracking_started_at' => '2026-07-20']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000]);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-07-20', 'sequence' => 1, 'amount' => 25, 'source' => 'manual']);

        Livewire::actingAs($operator)->test(RobotDetail::class, ['robot' => $robot])
            ->assertSeeHtml('id="robot-growth-chart"')
            ->assertSeeHtml('id="robot-daily-chart"')
            ->assertSeeHtml('id="robot-monthly-chart"')
            ->assertSeeHtml('id="robot-statistics-chart-data"')
            ->assertSee('2026-07-20')
            ->assertSee('∞');
    }

    public function test_custom_statistics_period_is_applied_without_changing_url(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Period robot', 'tracking_started_at' => '2026-07-01']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000]);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-07-01', 'sequence' => 1, 'amount' => 100, 'source' => 'manual']);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-07-10', 'sequence' => 1, 'amount' => -20, 'source' => 'manual']);

        Livewire::actingAs($operator)->test(RobotDetail::class, ['robot' => $robot])
            ->call('selectStatisticsPeriod', 'custom')
            ->set('statisticsStartDate', '2026-07-10')
            ->set('statisticsEndDate', '2026-07-10')
            ->call('applyCustomStatisticsPeriod')
            ->assertHasNoErrors()
            ->assertSet('appliedStatisticsStartDate', '2026-07-10')
            ->assertSet('appliedStatisticsEndDate', '2026-07-10')
            ->assertSee('10.07.2026 — 10.07.2026')
            ->assertSee('-20,00');
    }

    public function test_custom_statistics_period_validates_dates(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Validation robot']);

        Livewire::actingAs($operator)->test(RobotDetail::class, ['robot' => $robot])
            ->call('selectStatisticsPeriod', 'custom')
            ->set('statisticsStartDate', '2026-07-10')
            ->set('statisticsEndDate', '2026-07-09')
            ->call('applyCustomStatisticsPeriod')
            ->assertHasErrors(['statisticsEndDate']);
    }

    public function test_trading_result_saved_event_recalculates_statistics_and_recent_results(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Refresh robot', 'tracking_started_at' => '2026-08-01']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000]);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-08-01', 'sequence' => 1, 'amount' => 10, 'source' => 'manual']);

        $detail = Livewire::actingAs($operator)->test(RobotDetail::class, ['robot' => $robot])
            ->assertSee('10,00');

        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-08-02', 'sequence' => 1, 'amount' => 345.67, 'source' => 'manual']);

        $detail->dispatch('trading-result-saved', robotId: $robot->id, accountId: $account->id, date: '2026-08-02')
            ->assertSee('355,67')
            ->assertSee('345,67')
            ->assertSee('2026-08-02')
            ->assertViewHas('recentResults', fn ($results) => $results->contains(fn (TradingResult $result) => (float) $result->amount === 345.67));
    }
}

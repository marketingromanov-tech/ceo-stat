<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\ResultCalendar;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResultCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_save_a_manual_daily_result(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $account = TradingAccount::query()->create(['robot_id' => Robot::query()->create(['name' => 'Alpha'])->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 1000]);

        Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $account->robot_id])
            ->call('selectDate', '2026-08-03')
            ->set('amount', '125.50')->set('comment', 'Ручной результат')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas(TradingResult::class, ['trading_account_id' => $account->id, 'traded_at' => '2026-08-03', 'amount' => 125.50, 'source' => 'manual']);
        $this->assertDatabaseHas(Robot::class, ['id' => $account->robot_id, 'tracking_started_at' => '2026-08-03']);
    }

    public function test_operator_can_add_multiple_results_to_one_day(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $account = TradingAccount::query()->create(['robot_id' => Robot::query()->create(['name' => 'Alpha'])->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 1000]);

        $calendar = Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $account->robot_id])->call('selectDate', '2026-08-03');
        $calendar->set('amount', '-15.36')->call('save')->set('amount', '11.06')->call('save')->set('amount', '80.97')->call('save');

        $this->assertSame(3, TradingResult::query()->where('trading_account_id', $account->id)->whereDate('traded_at', '2026-08-03')->count());
        $this->assertEqualsWithDelta(76.67, TradingResult::query()->where('trading_account_id', $account->id)->sum('amount'), 0.001);
    }

    public function test_days_before_robot_tracking_start_are_locked(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Alpha', 'tracking_started_at' => '2026-07-20']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 1000]);

        Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $robot->id])
            ->call('selectDate', '2026-07-19')
            ->assertSet('selectedDate', null)
            ->set('selectedDate', '2026-07-19')
            ->set('amount', '10')
            ->call('save')
            ->assertHasErrors(['selectedDate']);

        $this->assertDatabaseMissing(TradingResult::class, ['trading_account_id' => $account->id, 'traded_at' => '2026-07-19']);
    }

    public function test_tracking_start_day_itself_is_not_locked(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Alpha', 'tracking_started_at' => '2026-07-20']);
        TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 1000]);

        Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $robot->id])
            ->assertSet('trackingStartedAt', '2026-07-20')
            ->call('selectDate', '2026-07-20')
            ->assertSet('selectedDate', '2026-07-20');
    }

    public function test_results_before_tracking_start_are_excluded_from_calculations(): void
    {
        $robot = Robot::query()->create(['name' => 'Alpha', 'tracking_started_at' => '2026-07-20']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 1000]);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-07-19', 'sequence' => 1, 'amount' => 100, 'source' => 'manual']);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-07-20', 'sequence' => 1, 'amount' => 25, 'source' => 'manual']);

        $this->assertEqualsWithDelta(25, TradingResult::query()->withinTrackingPeriod()->sum('amount'), 0.001);
    }
}

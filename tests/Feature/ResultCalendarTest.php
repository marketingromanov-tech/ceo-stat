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

        $this->assertDatabaseHas(TradingResult::class, ['trading_account_id' => $account->id, 'amount' => 125.50, 'source' => 'manual']);
        $this->assertSame('2026-08-03', TradingResult::query()->where('trading_account_id', $account->id)->firstOrFail()->traded_at->format('Y-m-d'));
        $this->assertSame('2026-08-03', Robot::query()->findOrFail($account->robot_id)->tracking_started_at->format('Y-m-d'));
    }

    public function test_operator_can_add_multiple_results_to_one_day(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $account = TradingAccount::query()->create(['robot_id' => Robot::query()->create(['name' => 'Alpha'])->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 1000]);

        $calendar = Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $account->robot_id])->call('selectDate', '2026-08-03');
        $calendar->set('amount', '-15.36')->call('save')->set('amount', '11.06')->call('save')->set('amount', '80.97')->call('save');

        $this->assertSame(3, TradingResult::query()->where('trading_account_id', $account->id)->whereDate('traded_at', '2026-08-03')->count());
        $this->assertEqualsWithDelta(76.67, TradingResult::query()->where('trading_account_id', $account->id)->sum('amount'), 0.001);
        $calendar->assertSeeInOrder(['+80,97', '+11,06', '-15,36']);
    }

    public function test_operator_can_enter_decimal_amount_with_comma(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $account = TradingAccount::query()->create(['robot_id' => Robot::query()->create(['name' => 'Alpha'])->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 1000]);

        Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $account->robot_id])
            ->call('selectDate', '2026-08-03')
            ->set('amount', '-9,03')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(TradingResult::class, ['trading_account_id' => $account->id, 'amount' => -9.03]);
        $this->assertSame('2026-08-03', TradingResult::query()->where('trading_account_id', $account->id)->firstOrFail()->traded_at->format('Y-m-d'));
    }

    public function test_robot_calendar_day_shows_result_percentage_of_initial_deposit(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Alpha']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 20000, 'current_balance' => 20195]);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => now()->format('Y-m-d'), 'sequence' => 1, 'amount' => 195, 'source' => 'manual']);

        Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $robot->id])
            ->assertSee('+195')
            ->assertSee('+0,975%');
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

    public function test_global_calendar_contains_per_robot_day_breakdown(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'is_active' => true]);
        $alpha = Robot::query()->create(['name' => 'Alpha']);
        $beta = Robot::query()->create(['name' => 'Beta']);
        $viewer->robots()->attach([$alpha->id, $beta->id]);
        $alphaAccount = TradingAccount::query()->create(['robot_id' => $alpha->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 1100]);
        $betaAccount = TradingAccount::query()->create(['robot_id' => $beta->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000, 'current_balance' => 950]);
        TradingResult::query()->create(['trading_account_id' => $alphaAccount->id, 'traded_at' => now()->format('Y-m-d'), 'sequence' => 1, 'amount' => 100, 'source' => 'manual']);
        TradingResult::query()->create(['trading_account_id' => $betaAccount->id, 'traded_at' => now()->format('Y-m-d'), 'sequence' => 1, 'amount' => -50, 'source' => 'manual']);

        Livewire::actingAs($viewer)->test(ResultCalendar::class, ['readOnly' => true])
            ->assertSee('Alpha')
            ->assertSee('Beta')
            ->assertSee('+100,00')
            ->assertSee('-50,00')
            ->assertSee('+2,500%');
    }

    public function test_trading_result_saved_event_refreshes_matching_calendar_without_resetting_state(): void
    {
        $this->travelTo('2026-08-08');
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Alpha']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000]);

        $calendar = Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $robot->id])
            ->call('selectDate', '2026-08-03')
            ->set('amount', '17,50')
            ->set('comment', 'Draft comment');

        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-08-03', 'sequence' => 1, 'amount' => 321.45, 'source' => 'manual']);

        $calendar->dispatch('trading-result-saved', robotId: $robot->id, accountId: $account->id, date: '2026-08-03')
            ->assertSet('month', '2026-08')
            ->assertSet('selectedDate', '2026-08-03')
            ->assertSet('amount', '17,50')
            ->assertSet('comment', 'Draft comment')
            ->assertSee('+321,45');
    }

    public function test_trading_result_saved_event_for_another_robot_does_not_refresh_calendar(): void
    {
        $this->travelTo('2026-08-08');
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
        $alpha = Robot::query()->create(['name' => 'Alpha']);
        $beta = Robot::query()->create(['name' => 'Beta']);
        $alphaAccount = TradingAccount::query()->create(['robot_id' => $alpha->id, 'name' => 'Alpha account', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000]);
        $betaAccount = TradingAccount::query()->create(['robot_id' => $beta->id, 'name' => 'Beta account', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000]);

        $calendar = Livewire::actingAs($operator)->test(ResultCalendar::class, ['robotId' => $alpha->id]);
        TradingResult::query()->create(['trading_account_id' => $alphaAccount->id, 'traded_at' => '2026-08-03', 'sequence' => 1, 'amount' => 654.32, 'source' => 'manual']);

        $calendar->dispatch('trading-result-saved', robotId: $beta->id, accountId: $betaAccount->id, date: '2026-08-03')
            ->assertDontSee('654,32');
    }
}

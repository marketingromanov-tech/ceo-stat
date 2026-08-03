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
            ->assertSee('0,500%')
            ->assertSee('75,00');
    }
}

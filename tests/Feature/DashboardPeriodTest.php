<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_cards_use_calendar_month_from_query_string(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'Alpha', 'tracking_started_at' => '2026-07-20']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 20000, 'current_balance' => 21759.12, 'is_active' => true]);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-07-20', 'sequence' => 1, 'amount' => 1759.12, 'source' => 'manual']);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-08-01', 'sequence' => 1, 'amount' => 100, 'source' => 'manual']);

        $this->actingAs($user)
            ->get('/?month=2026-07')
            ->assertOk()
            ->assertSee('1 759,12')
            ->assertSee('8,80%');
    }
}

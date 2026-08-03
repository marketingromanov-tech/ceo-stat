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
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\RobotManager;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RobotDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_delete_robot_with_account_and_results(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true, 'password' => Hash::make('Secret123!')]);
        $robot = Robot::query()->create(['name' => 'Disposable']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => 'Main', 'platform' => 'manual', 'currency' => 'USD']);
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => '2026-08-03', 'sequence' => 1, 'amount' => 10, 'source' => 'manual']);

        Livewire::actingAs($operator)->test(RobotManager::class)
            ->call('confirmDelete', $robot->id)
            ->set('deletePassword', 'Secret123!')
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('robots', ['id' => $robot->id]);
        $this->assertDatabaseMissing('trading_accounts', ['id' => $account->id]);
        $this->assertDatabaseCount('trading_results', 0);
    }
}

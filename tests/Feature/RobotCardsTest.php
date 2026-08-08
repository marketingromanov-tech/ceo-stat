<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\QuickResultEntry;
use App\Livewire\RobotManager;
use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RobotCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-08-08');
    }

    public function test_robot_card_displays_account_statistics_and_latest_result(): void
    {
        $admin = $this->user(UserRole::Admin);
        [$robot, $account] = $this->robot('Alpha card', 2000);
        $this->addResult($account, '2026-08-01', 50, 1);
        $this->addResult($account, '2026-08-07', 150, 1);

        Livewire::actingAs($admin)->test(RobotManager::class)
            ->assertSee('Alpha card')
            ->assertSee('Alpha card account')
            ->assertSee('2 000,00')
            ->assertSee('200,00')
            ->assertSee('10,000%')
            ->assertSee('150,00')
            ->assertSee('07.08.2026')
            ->assertSee('Добавить результат');

        Livewire::actingAs($admin)->test(QuickResultEntry::class)
            ->dispatch('open-quick-result-entry', robotId: $robot->id)
            ->assertSet('isOpen', true)
            ->assertSet('robotId', $robot->id);
    }

    public function test_card_displays_current_robot_status(): void
    {
        $admin = $this->user(UserRole::Admin);
        [$robot] = $this->robot('Paused card');
        $robot->statusPeriods()->create(['status' => RobotStatusPeriod::STATUS_PAUSED, 'starts_at' => '2026-08-05']);

        Livewire::actingAs($admin)->test(RobotManager::class)
            ->assertSee('Paused card')
            ->assertSee('Пауза')
            ->assertSee('05.08.2026');
    }

    public function test_viewer_only_sees_assigned_robot_cards_and_no_result_action(): void
    {
        $viewer = $this->user(UserRole::Viewer);
        [$assigned] = $this->robot('Assigned card');
        [$hidden] = $this->robot('Hidden card');
        $viewer->robots()->attach($assigned);

        Livewire::actingAs($viewer)->test(RobotManager::class)
            ->assertSee('Assigned card')
            ->assertDontSee('Hidden card')
            ->assertDontSee('Добавить результат');
    }

    public function test_status_filter_only_keeps_matching_cards(): void
    {
        $admin = $this->user(UserRole::Admin);
        [$active] = $this->robot('Active card');
        [$paused] = $this->robot('Paused filter card');
        [$stopped] = $this->robot('Stopped card');
        $paused->statusPeriods()->create(['status' => RobotStatusPeriod::STATUS_PAUSED, 'starts_at' => '2026-08-01']);
        $stopped->statusPeriods()->create(['status' => RobotStatusPeriod::STATUS_MAINTENANCE, 'starts_at' => '2026-08-01']);

        Livewire::actingAs($admin)->test(RobotManager::class)
            ->call('selectStatusFilter', 'paused')
            ->assertSee($paused->name)
            ->assertDontSee($active->name)
            ->assertDontSee($stopped->name);
    }

    public function test_cards_can_be_sorted_by_name_profit_and_return(): void
    {
        $admin = $this->user(UserRole::Admin);
        [$low, $lowAccount] = $this->robot('A low', 100);
        [$high, $highAccount] = $this->robot('Z high', 2000);
        $this->addResult($lowAccount, '2026-08-01', 10, 1);
        $this->addResult($highAccount, '2026-08-01', 100, 1);

        $cards = Livewire::actingAs($admin)->test(RobotManager::class)
            ->assertSeeInOrder([$low->name, $high->name])
            ->call('selectSort', 'profit')
            ->assertSeeInOrder([$high->name, $low->name]);

        $cards->call('selectSort', 'return')
            ->assertSeeInOrder([$low->name, $high->name]);
    }

    public function test_selected_period_limits_card_profit(): void
    {
        $admin = $this->user(UserRole::Admin);
        [$robot, $account] = $this->robot('Period card');
        $this->addResult($account, '2026-07-01', 500, 1);
        $this->addResult($account, '2026-08-08', 20, 1);

        Livewire::actingAs($admin)->test(RobotManager::class)
            ->assertSee('520,00')
            ->call('selectStatisticsPeriod', '7_days')
            ->assertSee('20,00')
            ->assertDontSee('520,00');
    }

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function robot(string $name, float $deposit = 1000): array
    {
        $robot = Robot::query()->create(['name' => $name, 'tracking_started_at' => '2026-01-01']);
        $account = TradingAccount::query()->create(['robot_id' => $robot->id, 'name' => $name.' account', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => $deposit]);

        return [$robot, $account];
    }

    private function addResult(TradingAccount $account, string $date, float $amount, int $sequence): void
    {
        TradingResult::query()->create(['trading_account_id' => $account->id, 'traded_at' => $date, 'amount' => $amount, 'sequence' => $sequence, 'source' => 'manual']);
    }
}

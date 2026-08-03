<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\RobotManager;
use App\Livewire\UserManager;
use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_multiple_robots_to_viewer(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $alpha = Robot::query()->create(['name' => 'Alpha']);
        $beta = Robot::query()->create(['name' => 'Beta']);

        Livewire::actingAs($admin)->test(UserManager::class)
            ->set('name', 'Viewer')
            ->set('email', 'viewer@example.com')
            ->set('role', 'viewer')
            ->set('password', 'Secret123!')
            ->set('robotIds', [$alpha->id, $beta->id])
            ->call('save')
            ->assertHasNoErrors();

        $viewer = User::query()->where('email', 'viewer@example.com')->firstOrFail();
        $this->assertEqualsCanonicalizing([$alpha->id, $beta->id], $viewer->robots()->pluck('robots.id')->all());
    }

    public function test_viewer_only_sees_assigned_robot_data(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'is_active' => true]);
        $assigned = Robot::query()->create(['name' => 'Assigned']);
        $hidden = Robot::query()->create(['name' => 'Hidden']);
        $viewer->robots()->attach($assigned);
        $assignedAccount = TradingAccount::query()->create(['robot_id' => $assigned->id, 'name' => 'Assigned account', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000]);
        $hiddenAccount = TradingAccount::query()->create(['robot_id' => $hidden->id, 'name' => 'Hidden account', 'platform' => 'manual', 'currency' => 'USD', 'initial_deposit' => 1000]);
        TradingResult::query()->create(['trading_account_id' => $assignedAccount->id, 'traded_at' => now()->format('Y-m-d'), 'sequence' => 1, 'amount' => 25, 'source' => 'manual']);
        TradingResult::query()->create(['trading_account_id' => $hiddenAccount->id, 'traded_at' => now()->format('Y-m-d'), 'sequence' => 1, 'amount' => 900, 'source' => 'manual']);

        $this->actingAs($viewer)->get('/')->assertOk()->assertSee('25,00')->assertDontSee('900,00')->assertDontSee('Hidden');
        $this->actingAs($viewer)->get(route('robots.show', $assigned))->assertOk();
        $this->actingAs($viewer)->get(route('robots.show', $hidden))->assertForbidden();
        Livewire::actingAs($viewer)->test(RobotManager::class)->call('edit', $assigned->id)->assertForbidden();
    }
}

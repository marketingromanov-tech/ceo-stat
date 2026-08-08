<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\RobotDetail;
use App\Livewire\RobotManager;
use App\Models\Robot;
use App\Models\RobotStatusPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RobotStatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_robot_displays_current_working_period(): void
    {
        $robot = $this->robotWithStatus(RobotStatusPeriod::STATUS_WORKING, '2026-08-01', '2026-08-31');

        $this->assertStatusOnDetailAndList($robot, 'Активен', '01.08.2026', '31.08.2026');
    }

    public function test_paused_robot_displays_current_pause_period(): void
    {
        $robot = $this->robotWithStatus(RobotStatusPeriod::STATUS_PAUSED, '2026-08-05');

        $this->assertStatusOnDetailAndList($robot, 'На паузе', '05.08.2026');
    }

    public function test_stopped_robot_displays_current_maintenance_period(): void
    {
        $robot = $this->robotWithStatus(RobotStatusPeriod::STATUS_MAINTENANCE, '2026-08-03', '2026-08-20');

        $this->assertStatusOnDetailAndList($robot, 'Остановлен', '03.08.2026', '20.08.2026');
    }

    public function test_robot_without_status_period_defaults_to_active(): void
    {
        $this->travelTo('2026-08-08');
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $robot = Robot::query()->create(['name' => 'No status robot']);

        Livewire::actingAs($admin)->test(RobotDetail::class, ['robot' => $robot])
            ->assertSee('Активен')
            ->assertSee('Действующий период статуса не задан.');

        Livewire::actingAs($admin)->test(RobotManager::class)
            ->assertSee('No status robot')
            ->assertSee('Активен');
    }

    private function robotWithStatus(string $status, string $startsAt, ?string $endsAt = null): Robot
    {
        $this->travelTo('2026-08-08');
        $robot = Robot::query()->create(['name' => ucfirst($status).' robot']);
        RobotStatusPeriod::query()->create([
            'robot_id' => $robot->id,
            'status' => $status,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        return $robot;
    }

    private function assertStatusOnDetailAndList(Robot $robot, string $label, string $startsAt, ?string $endsAt = null): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $detail = Livewire::actingAs($admin)->test(RobotDetail::class, ['robot' => $robot])
            ->assertSee($label)
            ->assertSee($startsAt);
        $list = Livewire::actingAs($admin)->test(RobotManager::class)
            ->assertSee($robot->name)
            ->assertSee($label)
            ->assertSee($startsAt);

        if ($endsAt) {
            $detail->assertSee($endsAt);
            $list->assertSee($endsAt);
        }
    }
}

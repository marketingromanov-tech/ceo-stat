<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_management_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $this->actingAs($admin)->get('/robots')->assertOk();
    }

    public function test_viewer_cannot_open_management_pages(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'is_active' => true]);

        $this->actingAs($viewer)->get('/robots')->assertForbidden();
    }
}

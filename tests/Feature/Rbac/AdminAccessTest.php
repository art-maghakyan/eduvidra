<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use App\Enums\RbacRole;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $this->seed(RbacSeeder::class);

        $student = User::factory()->create();
        $student->assignRole(RbacRole::STUDENT->value);

        $response = $this->actingAs($student)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RbacRole::ADMIN->value);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
    }
}


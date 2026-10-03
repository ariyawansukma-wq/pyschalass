<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_is_authorized_for_all_features(): void
    {
        $admin = User::create([
            'username' => 'admin',
            'name' => 'Administrator',
            'role' => UserRole::Admin->value,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin);

        $this->assertTrue($admin->can('manage-users'));
        $this->assertTrue($admin->can('manage-athletes'));
        $this->assertTrue($admin->can('manage-activities'));
        $this->assertTrue($admin->can('manage-settings'));
    }

    public function test_officer_role_is_restricted_to_specific_features(): void
    {
        $officer = User::create([
            'username' => 'officer',
            'name' => 'Officer',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($officer);

        $this->assertFalse($officer->can('manage-users'));
        $this->assertFalse($officer->can('manage-settings'));
        $this->assertFalse($officer->can('manage-reports'));
        $this->assertTrue($officer->can('manage-athletes'));
        $this->assertTrue($officer->can('manage-sport-branches'));
        $this->assertTrue($officer->can('manage-benchmarks'));
        $this->assertTrue($officer->can('view-folders'));
    }
}

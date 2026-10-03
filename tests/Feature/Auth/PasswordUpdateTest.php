<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('old-password'),
        ]);

        $this->actingAs($user);

        $response = $this->put('/password', [
            'current_password' => 'old-password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertSessionHas('status');
        $this->assertTrue(\Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_invalid_current_password(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('old-password'),
        ]);

        $this->actingAs($user);

        $response = $this->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }
}

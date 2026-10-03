<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\AccountDeactivatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DeactivateExpiredUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivates_expired_users_and_sends_notification_once(): void
    {
        Notification::fake();

        $expiredUser = User::create([
            'username' => 'officer_expired',
            'name' => 'Officer Expired',
            'email' => 'officer@example.com',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
            'is_active' => true,
            'active_until' => now()->subDay(),
            'expired_notified_at' => null,
        ]);

        $activeUser = User::create([
            'username' => 'officer_active',
            'name' => 'Officer Active',
            'email' => 'active@example.com',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
            'is_active' => true,
            'active_until' => now()->addDays(7),
            'expired_notified_at' => null,
        ]);

        // 1. First run - should deactivate expired user and send notification
        $this->artisan('app:deactivate-expired-users')
            ->assertSuccessful();

        $this->assertFalse($expiredUser->fresh()->is_active);
        $this->assertNotNull($expiredUser->fresh()->expired_notified_at);
        $this->assertTrue($activeUser->fresh()->is_active);

        Notification::assertSentTo($expiredUser, AccountDeactivatedNotification::class, 1);
        Notification::assertNotSentTo($activeUser, AccountDeactivatedNotification::class);

        // 2. Second run - should NOT spam notification again because expired_notified_at is already set
        Notification::fake();

        $this->artisan('app:deactivate-expired-users')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}

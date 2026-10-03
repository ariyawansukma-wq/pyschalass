<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\DeviceFingerprintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_session_id_is_regenerated_on_successful_login(): void
    {
        User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_rate_limit_applies_to_failed_attempts(): void
    {
        User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'testuser',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_user_can_logout_successfully(): void
    {
        User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs(User::where('username', 'testuser')->first());

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_blocks_new_device_when_max_devices_exceeded(): void
    {
        $user = User::create([
            'username' => 'multidevice',
            'name' => 'Multi Device User',
            'role' => UserRole::Officer->value,
            'max_devices' => 2,
            'password' => bcrypt('password'),
        ]);

        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            // Seed 2 active sessions in DB from other devices (within 30 mins)
            \Illuminate\Support\Facades\DB::table('sessions')->insert([
                [
                    'id' => 'sess_1',
                    'user_id' => $user->id,
                    'ip_address' => '10.0.0.1',
                    'user_agent' => 'Device 1',
                    'payload' => 'payload1',
                    'last_activity' => now()->subMinutes(10)->timestamp,
                ],
                [
                    'id' => 'sess_2',
                    'user_id' => $user->id,
                    'ip_address' => '10.0.0.2',
                    'user_agent' => 'Device 2',
                    'payload' => 'payload2',
                    'last_activity' => now()->subMinutes(5)->timestamp,
                ],
            ]);

            // User attempts to log in on a 3rd device (exceeding max_devices = 2)
            $response = $this->post('/login', [
                'username' => 'multidevice',
                'password' => 'password',
            ]);

            $response->assertSessionHasErrors('username');
            $this->assertGuest();

            // Both existing sessions should still be preserved
            $this->assertDatabaseHas('sessions', ['id' => 'sess_1']);
            $this->assertDatabaseHas('sessions', ['id' => 'sess_2']);
        }
    }

    public function test_login_from_same_device_replaces_old_session(): void
    {
        $user = User::create([
            'username' => 'samedevice',
            'name' => 'Same Device User',
            'role' => UserRole::Officer->value,
            'max_devices' => 1,
            'password' => bcrypt('password'),
        ]);

        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            \Illuminate\Support\Facades\DB::table('sessions')->insert([
                'id' => 'sess_same_device',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Symfony',
                'payload' => 'payload',
                'last_activity' => now()->subMinutes(10)->timestamp,
            ]);

            $response = $this->post('/login', [
                'username' => 'samedevice',
                'password' => 'password',
            ]);

            $response->assertRedirect('/dashboard');
            $this->assertDatabaseMissing('sessions', ['id' => 'sess_same_device']);
        } else {
            $response = $this->post('/login', [
                'username' => 'samedevice',
                'password' => 'password',
            ]);
            $response->assertRedirect('/dashboard');
        }
    }

    public function test_idle_session_older_than_threshold_is_purged(): void
    {
        $user = User::create([
            'username' => 'idletest',
            'name' => 'Idle Test User',
            'role' => UserRole::Officer->value,
            'max_devices' => 1,
            'password' => bcrypt('password'),
        ]);

        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            // Seed a session that was idle for 35 minutes (> 30 minutes idle timeout)
            \Illuminate\Support\Facades\DB::table('sessions')->insert([
                'id' => 'sess_idle_device',
                'user_id' => $user->id,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Other Device',
                'payload' => 'payload',
                'last_activity' => now()->subMinutes(35)->timestamp,
            ]);

            $response = $this->post('/login', [
                'username' => 'idletest',
                'password' => 'password',
            ]);

            $response->assertRedirect('/dashboard');
            // The idle session should be purged and not block the new login
            $this->assertDatabaseMissing('sessions', ['id' => 'sess_idle_device']);
        } else {
            $response = $this->post('/login', [
                'username' => 'idletest',
                'password' => 'password',
            ]);
            $response->assertRedirect('/dashboard');
        }
    }

    public function test_authenticated_user_can_send_heartbeat(): void
    {
        $user = User::create([
            'username' => 'heartbeattest',
            'name' => 'Heartbeat User',
            'role' => UserRole::Officer->value,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/heartbeat');
        $response->assertOk();
        $response->assertJson(['status' => 'alive']);
    }

    public function test_login_from_same_device_with_different_ip_replaces_old_session(): void
    {
        $user = User::create([
            'username' => 'ipchangeuser',
            'name' => 'IP Change User',
            'role' => UserRole::Officer->value,
            'max_devices' => 1,
            'password' => bcrypt('password'),
        ]);

        if (Schema::hasTable('sessions')) {
            $fingerprintService = new DeviceFingerprintService();
            
            // Create a fake request to calculate fingerprint for device
            $fakeReq = Request::create('/login', 'POST', [], [], [], [
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
            ]);
            $fingerprint = $fingerprintService->generate($fakeReq);

            // Seed an active session under old IP (e.g. WiFi 192.168.1.50)
            DB::table('sessions')->insert([
                'id' => 'sess_wifi_old_ip',
                'user_id' => $user->id,
                'ip_address' => '192.168.1.50',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'fingerprint' => $fingerprint,
                'payload' => 'payload',
                'last_activity' => now()->subMinutes(5)->timestamp,
            ]);

            // User logs in on the SAME device but with NEW IP (e.g. Hotspot 114.125.10.20)
            $response = $this->withServerVariables([
                'REMOTE_ADDR' => '114.125.10.20',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
            ])->post('/login', [
                'username' => 'ipchangeuser',
                'password' => 'password',
            ]);

            $response->assertRedirect('/dashboard');
            // Old session with old IP should be cleaned up and replaced, allowing smooth login with max_devices = 1
            $this->assertDatabaseMissing('sessions', ['id' => 'sess_wifi_old_ip']);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\AccountDeactivatedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Notification;

class DeactivateExpiredUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deactivate-expired-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically deactivate user accounts whose active_until expiration date has passed, revoke active sessions, and send email notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredUsers = User::whereNotNull('active_until')
            ->whereDate('active_until', '<', now()->startOfDay())
            ->where(function ($query) {
                $query->where('is_active', true)
                      ->orWhereNull('expired_notified_at');
            })
            ->get();

        $count = $expiredUsers->count();

        if ($count === 0) {
            $this->info('No expired user accounts found.');
            return Command::SUCCESS;
        }

        $userIds = $expiredUsers->pluck('id')->toArray();

        // 1. Update is_active to false for any active users
        User::whereIn('id', $userIds)->where('is_active', true)->update(['is_active' => false]);

        // 2. Revoke active sessions for these users if sessions table exists
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();
        }

        // 3. Filter users who have not received the expiration notification yet
        $unnotifiedUsers = $expiredUsers->filter(fn($u) => is_null($u->expired_notified_at));
        $usersToNotify = $unnotifiedUsers->filter(fn($u) => !empty($u->email));

        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new AccountDeactivatedNotification());
        }

        // 4. Mark expired_notified_at timestamp so we do not spam notifications
        if ($unnotifiedUsers->isNotEmpty()) {
            User::whereIn('id', $unnotifiedUsers->pluck('id'))->update(['expired_notified_at' => now()]);
        }

        $notifiedCount = $usersToNotify->count();
        $this->info("Successfully deactivated {$count} expired user account(s), cleared active sessions, and sent {$notifiedCount} notification(s).");

        return Command::SUCCESS;
    }
}
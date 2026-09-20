<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Security\AccountLockout;
use Illuminate\Console\Command;

/**
 * Escape hatch for the account lockout: an operator who has locked themselves
 * out of the panel can be released from the CLI.
 */
class UnlockAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:unlock {identifier : Email, mobile or username of the account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the failed login counters and release the lockout on an account';

    /**
     * Execute the console command.
     *
     * @param \App\Support\Security\AccountLockout $lockout
     * @return int
     */
    public function handle(AccountLockout $lockout)
    {
        $identifier = $this->argument('identifier');

        $users = User::withTrashed()
            ->where('email', $identifier)
            ->orWhere('mobile', $identifier)
            ->orWhere('username', $identifier)
            ->get();

        if ($users->isEmpty()) {
            $this->error("No account found for [{$identifier}].");

            return self::FAILURE;
        }

        foreach ($users as $user) {
            $lockout->unlock($user);

            $this->info("Unlocked user #{$user->id} ({$user->email}).");
        }

        return self::SUCCESS;
    }
}

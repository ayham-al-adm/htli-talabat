<?php

namespace App\Support\Security;

use App\Models\User;
use Carbon\Carbon;

/**
 * Progressive account lockout for credential based logins.
 *
 * Counters live on the users table (failed_login_attempts, last_failed_login_at,
 * locked_until) so a lockout survives a cache flush or an app restart, unlike
 * the cache backed rate limiter that sits in front of it.
 */
class AccountLockout
{
    /**
     * Whether lockout is switched on.
     *
     * @return bool
     */
    public function enabled()
    {
        return (bool) config('security.lockout.enabled', true);
    }

    /**
     * Number of failed attempts that triggers a lock.
     *
     * @return int
     */
    public function maxAttempts()
    {
        return max(1, (int) config('security.lockout.max_attempts', 5));
    }

    /**
     * How long an account stays locked, in minutes.
     *
     * @return int
     */
    public function durationMinutes()
    {
        return max(1, (int) config('security.lockout.duration_minutes', 15));
    }

    /**
     * How long a failed attempt keeps counting, in minutes.
     *
     * @return int
     */
    public function windowMinutes()
    {
        return max(1, (int) config('security.lockout.window_minutes', 15));
    }

    /**
     * Determine whether the account is currently locked.
     *
     * @param \App\Models\User|null $user
     * @return bool
     */
    public function isLocked($user)
    {
        return $this->lockedUntil($user) !== null;
    }

    /**
     * The moment the lock expires, or null when the account is not locked.
     *
     * @param \App\Models\User|null $user
     * @return \Carbon\Carbon|null
     */
    public function lockedUntil($user)
    {
        if (!$this->enabled() || !$user instanceof User || empty($user->locked_until)) {
            return null;
        }

        $lockedUntil = Carbon::parse($user->locked_until);

        return $lockedUntil->isFuture() ? $lockedUntil : null;
    }

    /**
     * Minutes remaining on the current lock (rounded up, minimum 1).
     *
     * @param \App\Models\User|null $user
     * @return int
     */
    public function minutesRemaining($user)
    {
        if (!$lockedUntil = $this->lockedUntil($user)) {
            return 0;
        }

        return max(1, (int) ceil(Carbon::now()->diffInSeconds($lockedUntil, false) / 60));
    }

    /**
     * Record a failed credential check and lock the account once the
     * threshold is reached.
     *
     * @param \App\Models\User|null $user
     * @return \App\Models\User|null
     */
    public function recordFailedAttempt($user)
    {
        if (!$this->enabled() || !$user instanceof User) {
            return $user;
        }

        $now = Carbon::now();

        // Attempts that fell outside the counting window are forgiven.
        $lastFailure = $user->last_failed_login_at ? Carbon::parse($user->last_failed_login_at) : null;

        $attempts = ($lastFailure && $lastFailure->gt($now->copy()->subMinutes($this->windowMinutes())))
            ? (int) $user->failed_login_attempts
            : 0;

        $attempts++;

        $attributes = [
            'failed_login_attempts' => $attempts,
            'last_failed_login_at'  => $now,
        ];

        if ($attempts >= $this->maxAttempts()) {
            $attributes['locked_until'] = $now->copy()->addMinutes($this->durationMinutes());
            $attributes['failed_login_attempts'] = 0;
        }

        return $this->persist($user, $attributes);
    }

    /**
     * Clear the failure counters after a successful login.
     *
     * @param \App\Models\User|null $user
     * @return \App\Models\User|null
     */
    public function clear($user)
    {
        if (!$user instanceof User) {
            return $user;
        }

        if (!$user->failed_login_attempts && !$user->last_failed_login_at && !$user->locked_until) {
            return $user;
        }

        return $this->persist($user, [
            'failed_login_attempts' => 0,
            'last_failed_login_at'  => null,
            'locked_until'          => null,
        ]);
    }

    /**
     * Release a lock without touching anything else. Used by the admin side.
     *
     * @param \App\Models\User|null $user
     * @return \App\Models\User|null
     */
    public function unlock($user)
    {
        return $this->clear($user);
    }

    /**
     * Write the counters straight to the row, bypassing mass assignment and
     * model events so nothing else in the login path is disturbed.
     *
     * @param \App\Models\User $user
     * @param array $attributes
     * @return \App\Models\User
     */
    protected function persist(User $user, array $attributes)
    {
        // toBase() keeps this off updated_at and out of the model events, so a
        // failed login never looks like an edit to the account.
        User::withoutGlobalScopes()
            ->toBase()
            ->where($user->getKeyName(), $user->getKey())
            ->update($attributes);

        $user->forceFill($attributes)->syncOriginal();

        return $user;
    }
}

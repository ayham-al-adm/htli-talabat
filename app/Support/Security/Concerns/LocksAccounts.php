<?php

namespace App\Support\Security\Concerns;

use App\Models\User;
use App\Support\Security\AccountLockout;

/**
 * Glue between the login controllers and the AccountLockout service.
 */
trait LocksAccounts
{
    /**
     * The account lockout service instance.
     *
     * @var \App\Support\Security\AccountLockout|null
     */
    protected $accountLockout;

    /**
     * Resolve the account lockout service.
     *
     * @return \App\Support\Security\AccountLockout
     */
    protected function accountLockout()
    {
        if (!$this->accountLockout) {
            $this->accountLockout = app(AccountLockout::class);
        }

        return $this->accountLockout;
    }

    /**
     * Abort the login when the account is locked out.
     *
     * @param \App\Models\User|null $user
     * @param string|null $field
     * @return void
     * @throws \App\Base\Exceptions\CustomValidationException
     */
    protected function guardAgainstLockedAccount($user, $field = null)
    {
        $lockout = $this->accountLockout();

        if (!$lockout->isLocked($user)) {
            return;
        }

        $this->throwCustomValidationException(
            $this->accountLockedMessage($lockout->minutesRemaining($user)),
            $field ?: 'email'
        );
    }

    /**
     * Count a failed credential check against the account, then respond with
     * the generic invalid credentials error.
     *
     * @param \App\Models\User|null $user
     * @param string|null $field
     * @return void
     * @throws \App\Base\Exceptions\CustomValidationException
     */
    protected function registerFailedLoginAttempt($user, $field = null)
    {
        $this->noteFailedLoginAttempt($user, $field);

        $this->throwInvalidCredentialsException($field);
    }

    /**
     * Count a failed attempt against the account and throw only when that
     * attempt tripped the lock. Callers that have their own error message
     * (an invalid OTP, for instance) use this and then throw their own.
     *
     * @param \App\Models\User|null $user
     * @param string|null $field
     * @return void
     * @throws \App\Base\Exceptions\CustomValidationException
     */
    protected function noteFailedLoginAttempt($user, $field = null)
    {
        $lockout = $this->accountLockout();

        $lockout->recordFailedAttempt($user);

        // The attempt we just recorded may have been the one that tripped the lock.
        if ($lockout->isLocked($user)) {
            $this->throwCustomValidationException(
                $this->accountLockedMessage($lockout->minutesRemaining($user)),
                $field ?: 'email'
            );
        }
    }

    /**
     * Reset the failure counters after a successful authentication.
     *
     * @param \App\Models\User|null $user
     * @return void
     */
    protected function registerSuccessfulLoginAttempt($user)
    {
        if ($user instanceof User) {
            $this->accountLockout()->clear($user);
        }
    }

    /**
     * The message shown while an account is locked.
     *
     * @param int $minutes
     * @return string
     */
    protected function accountLockedMessage($minutes)
    {
        return trans_choice(
            'Too many failed login attempts. This account is locked for 1 more minute.|Too many failed login attempts. This account is locked for :count more minutes.',
            $minutes,
            ['count' => $minutes]
        );
    }
}

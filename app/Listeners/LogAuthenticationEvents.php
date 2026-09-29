<?php

namespace App\Listeners;

use App\Models\SystemLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Writes sign-ins, sign-outs, failed sign-ins and lockouts to the audit log.
 * Registered automatically through Laravel's event discovery (each handle*
 * method's type-hint names the event it listens to).
 *
 * Failed and Lockout are fired by App\Http\Requests\Auth\LoginRequest. The
 * submitted password is never logged, only the phone number.
 */
class LogAuthenticationEvents
{
    public function handleLogin(Login $event): void
    {
        SystemLog::logAction($event->user->barangay_id, $event->user->id, 'LOGIN', 'Authentication', "{$event->user->full_name} signed in.");
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            SystemLog::logAction($event->user->barangay_id, $event->user->id, 'LOGOUT', 'Authentication', "{$event->user->full_name} signed out.");
        }
    }

    public function handleFailed(Failed $event): void
    {
        $phone = $event->credentials['phone_number'] ?? 'unknown';
        $reason = $event->user ? 'wrong password' : 'unknown phone number';

        SystemLog::logAction($event->user?->barangay_id, $event->user?->id, 'LOGIN_FAILED', 'Authentication', "Failed sign-in for {$phone} ({$reason}).");
    }

    public function handleLockout(Lockout $event): void
    {
        $phone = $event->request->input('phone_number', 'unknown');

        SystemLog::logAction(null, null, 'LOCKOUT', 'Authentication', "Sign-in temporarily locked for {$phone} after too many failed attempts.");
    }
}

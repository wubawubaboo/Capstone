<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Shared by the resident login() and staff staffLogin() actions. */
class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string'],
            'password' => ['required'],
        ];
    }

    /**
     * Checks the credentials without starting a session, so the caller can
     * apply its portal rules (role, verification) before logging the user in.
     * Failed attempts are rate limited per phone number and IP address.
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('phone_number', 'password');

        if (!Auth::validate($credentials)) {
            RateLimiter::hit($this->throttleKey());

            // Auth::validate() doesn't fire this itself (Auth::attempt() would);
            // it feeds the audit log via App\Listeners\LogAuthenticationEvents.
            event(new Failed(Auth::getDefaultDriver(), Auth::getLastAttempted(), $credentials));

            throw ValidationException::withMessages([
                'phone_number' => 'The provided phone number or password does not match our records.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return Auth::getLastAttempted();
    }

    private function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'phone_number' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate($this->string('phone_number')->lower() . '|' . $this->ip());
    }
}

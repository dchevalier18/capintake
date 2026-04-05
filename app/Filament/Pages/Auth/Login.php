<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;

class Login extends \Filament\Auth\Pages\Login
{
    /**
     * Override authenticate to add email+IP rate limiting.
     *
     * Filament's default only rate-limits by IP via Livewire's rateLimit().
     * We add a secondary limiter keyed on the submitted email address,
     * which prevents brute-force attacks even when IP detection is unreliable
     * (e.g., behind proxies, or during automated testing).
     */
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $email = $data['email'] ?? '';

        // Rate limit by email+IP combination: 5 attempts per 60 seconds
        $key = 'login-attempt:' . sha1($email . '|' . request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            Notification::make()
                ->title(__('Too many login attempts'))
                ->body(__('Please wait :seconds seconds before trying again.', [
                    'seconds' => $seconds,
                ]))
                ->danger()
                ->persistent()
                ->send();

            return null;
        }

        RateLimiter::hit($key, 60);

        // Also rate limit by IP alone: 10 attempts per 60 seconds
        // (prevents credential stuffing across multiple emails)
        $ipKey = 'login-attempt-ip:' . request()->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);

            Notification::make()
                ->title(__('Too many login attempts'))
                ->body(__('Please wait :seconds seconds before trying again.', [
                    'seconds' => $seconds,
                ]))
                ->danger()
                ->persistent()
                ->send();

            return null;
        }

        RateLimiter::hit($ipKey, 60);

        // Delegate to parent for actual authentication
        // (parent also has its own Livewire rate limiting as a third layer)
        $response = parent::authenticate();

        // If authentication succeeded, clear the limiters
        if (Filament::auth()->check()) {
            RateLimiter::clear($key);
            RateLimiter::clear($ipKey);
        }

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AgencySetting;
use Filament\Http\Middleware\Authenticate;

/**
 * Extends Filament's Authenticate middleware to skip authentication
 * when the initial setup wizard has not been completed yet.
 *
 * This allows a fresh install to go straight to the setup wizard
 * (which creates the first admin account) without needing a
 * pre-seeded throwaway login.
 */
class AuthenticateWithSetupBypass extends Authenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        // Skip authentication entirely until initial setup is complete.
        // EnsureSetupComplete middleware (which runs before this) will
        // redirect all non-setup routes to /admin/setup anyway.
        if (! AgencySetting::isSetupComplete()) {
            return;
        }

        parent::authenticate($request, $guards);
    }
}

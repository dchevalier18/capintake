<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AgencySetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        // Don't redirect if already on the setup page or health check
        if ($request->is('admin/setup') || $request->is('admin/setup/*') || $request->is('admin/health-check')) {
            return $next($request);
        }

        // Don't redirect for login/logout/password-reset routes
        if ($request->is('admin/login') || $request->is('admin/logout') || $request->is('admin/password-reset/*')) {
            return $next($request);
        }

        // Redirect to health check if the environment is not ready
        if (! $this->isEnvironmentHealthy()) {
            return redirect('/admin/health-check');
        }

        // Auto-seed reference data if tables exist but are empty (VPS installs that skipped db:seed)
        $this->autoSeedIfEmpty();

        if (! AgencySetting::isSetupComplete()) {
            return redirect('/admin/setup');
        }

        return $next($request);
    }

    /**
     * Check if the environment has minimum requirements to run (database, APP_KEY).
     */
    private function isEnvironmentHealthy(): bool
    {
        if (empty(config('app.key'))) {
            return false;
        }

        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Seed reference data if the lookup_categories table exists but is empty.
     * This handles VPS installs where migrations ran but db:seed was skipped.
     */
    private function autoSeedIfEmpty(): void
    {
        try {
            if (Schema::hasTable('lookup_categories') && DB::table('lookup_categories')->count() === 0) {
                Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Throwable) {
            // Silently fail — if the database isn't ready, the health check will catch it
        }
    }
}

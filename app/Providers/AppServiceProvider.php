<?php

namespace App\Providers;

use App\Models\AgencySetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(10)
            ->mixedCase()
            ->numbers()
            ->symbols());

        $this->applyMailSettings();
    }

    /**
     * Override mail config with agency settings if configured via SetupWizard.
     */
    private function applyMailSettings(): void
    {
        try {
            $settings = AgencySetting::current();
        } catch (\Throwable) {
            return;
        }

        if (! $settings || ! $settings->mail_host) {
            return;
        }

        config([
            'mail.default' => $settings->mail_mailer ?? 'smtp',
            'mail.mailers.smtp.host' => $settings->mail_host,
            'mail.mailers.smtp.port' => $settings->mail_port ?? 587,
            'mail.mailers.smtp.username' => $settings->mail_username,
            'mail.mailers.smtp.password' => $settings->mail_password,
            'mail.mailers.smtp.encryption' => $settings->mail_encryption ?? 'tls',
            'mail.from.address' => $settings->mail_from_address ?? config('mail.from.address'),
            'mail.from.name' => $settings->mail_from_name ?? $settings->agency_name ?? config('mail.from.name'),
        ]);
    }
}

<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureSetupComplete;
use App\Models\AgencySetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureSetupComplete::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        // Apply white-label settings if setup is complete
        $this->applyBranding($panel);

        return $panel;
    }

    protected function applyBranding(Panel $panel): void
    {
        try {
            $settings = AgencySetting::current();
        } catch (\Throwable) {
            // Table may not exist yet during migration
            $settings = null;
        }

        if ($settings && $settings->setup_completed) {
            $panel->brandName($settings->agency_name);

            if ($settings->logo_path) {
                $panel->brandLogo(asset("storage/{$settings->logo_path}"));
            }

            $panel->colors([
                'primary' => Color::hex($settings->primary_color),
            ]);
        } else {
            $panel->colors([
                'primary' => Color::Amber,
            ]);
        }
    }
}

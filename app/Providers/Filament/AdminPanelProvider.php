<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Widgets\StudioSettingsWidget;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Pages\CapellDashboard;
use Capell\Admin\Filament\Plugin\CapellAdminPlugin;
use Capell\Admin\Http\Middleware\SetSitePermissionScope;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Override;

class AdminPanelProvider extends PanelProvider
{
    #[Override]
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                CapellDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                StudioSettingsWidget::class,
                FilamentInfoWidget::class,
                ...CapellAdmin::getWidgets(),
            ])
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
            ])
            ->authMiddleware([
                Authenticate::class, SetSitePermissionScope::class,
            ])
            ->plugin(CapellAdminPlugin::make())
            ->navigationItems(CapellAdmin::getNavigationItems())
            ->navigationGroups(CapellAdmin::getNavigationGroups());
    }
}

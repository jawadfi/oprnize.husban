<?php

namespace App\Providers\Filament;

use App\Filament\Company\Pages\LeaveRequests;
use App\Filament\Company\Pages\Login;
use App\Filament\Company\Pages\PendingHiring;
use App\Filament\Company\Pages\ProviderCompaniesListing;
use App\Filament\Company\Pages\ProviderCompanyEmployees;
use App\Filament\Company\Pages\Register;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CompanyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->maxContentWidth(MaxWidth::Full)
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->id('company')
            ->path('company')
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => Color::Cyan,
            ])
            ->login(Login::class)
            ->registration(Register::class)
            ->emailVerification()
            ->profile()
            ->discoverResources(in: app_path('Filament/Company/Resources'), for: 'App\\Filament\\Company\\Resources')
            ->discoverPages(in: app_path('Filament/Company/Pages'), for: 'App\\Filament\\Company\\Pages')
            ->pages([
                Pages\Dashboard::class,
                PendingHiring::class,
                ProviderCompaniesListing::class,
                ProviderCompanyEmployees::class,
                LeaveRequests::class,
            ])->plugins([
                AuthUIEnhancerPlugin::make()
                    ->formPanelBackgroundColor(Color::hex('#ffffff'))
                    ->emptyPanelBackgroundImageUrl(asset('images/auth-wallpaper.jpeg')),
            ])
            ->theme(asset('css/filament/company/theme.css'))
            ->discoverWidgets(in: app_path('Filament/Company/Widgets'), for: 'App\\Filament\\Company\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authGuard('company')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

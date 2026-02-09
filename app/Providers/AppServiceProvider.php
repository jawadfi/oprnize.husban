<?php

namespace App\Providers;

use App\Auth\CompanyUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Filament\Actions\Imports\Models\Import;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
        // Register custom user provider for company guard
        // Auth::provider('company', function ($app, array $config) {
        //     return new CompanyUserProvider();
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        
        Import::polymorphicUserRelationship();
        Schema::defaultStringLength(191);
    }
}

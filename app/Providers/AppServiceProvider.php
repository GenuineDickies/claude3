<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\SmsService;
use App\Services\SmsServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsServiceInterface::class, SmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Safety nets — catch common bugs early in dev,
        // but keep production lenient to avoid surprises.
        $strict = ! app()->environment('production');
        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);
        Model::preventAccessingMissingAttributes($strict);

        // Share company branding variables with all views (single source of truth).
        View::composer('*', function ($view) {
            $view->with('companyName', Setting::getValue('company_name', config('app.name')));
            $view->with('companyTagline', Setting::getValue('company_tagline', 'Dispatch management'));
        });
    }
}

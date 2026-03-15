<?php

namespace App\Providers;

use App\Models\DocumentTransactionImport;
use App\Models\Setting;
use App\Services\DocumentIntelligenceInterface;
use App\Services\DocumentIntelligenceService;
use App\Services\Access\AccessControlService;
use App\Services\SmsService;
use App\Services\SmsServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/access.php');

        $this->app->singleton(SmsServiceInterface::class, SmsService::class);
        $this->app->singleton(DocumentIntelligenceInterface::class, DocumentIntelligenceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom route model binding for transaction imports
        Route::model('import', DocumentTransactionImport::class);

        // Safety nets — catch common bugs early in dev,
        // but keep production lenient to avoid surprises.
        $strict = ! app()->environment('production');
        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);
        Model::preventAccessingMissingAttributes($strict);

        // Share company branding variables with all views (single source of truth).
        View::composer('*', function ($view) {
            $accessControl = app(AccessControlService::class);
            $user = Auth::user();

            $view->with('companyName', Setting::getValue('company_name', config('app.name')));
            $view->with('companyTagline', Setting::getValue('company_tagline', 'Dispatch management'));
            $view->with('companyLogoUrl', Setting::companyLogoUrl());
            $view->with('currentUserCanManageAccess',
                $accessControl->canAccessPage($user, '/admin/users')
                || $accessControl->canAccessPage($user, '/admin/roles')
                || $accessControl->canAccessPage($user, '/admin/pages')
                || $accessControl->canAccessPage($user, '/admin/audit-logs')
            );
        });
    }
}

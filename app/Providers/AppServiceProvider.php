<?php

declare(strict_types=1);

namespace App\Providers;

use App\Filament\Configurators\Pages\CaseStudyPageConfigurator;
use App\Models\User;
use App\Support\StudioRenderDataContributor;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Support\Themes\LayoutViewRegistry;
use Capell\Frontend\Contracts\PublicRenderDataContributor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Override;

class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->tag([StudioRenderDataContributor::class], PublicRenderDataContributor::TAG);
    }

    public function boot(): void
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
            class: CaseStudyPageConfigurator::class, group: 'Pages', name: 'CaseStudy', owner: 'app',
        ));
        $views = app(LayoutViewRegistry::class);
        $views->registerLayout('capell.themes.capell-app.studio.layout', 'Studio');
        $views->registerMaster('capell.themes.capell-app.studio.empty', 'Studio');
        Gate::define('manage-studio', fn (User $user): bool => $user->hasRole('super_admin'));
        RateLimiter::for('contact', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
    }
}

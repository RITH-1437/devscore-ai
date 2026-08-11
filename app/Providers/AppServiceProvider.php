<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Repository;
use App\Policies\RepositoryPolicy;
use App\Services\GitHubService;
use App\Services\OpenRouterService;
use App\Services\PortfolioScoreService;
use App\Services\RepositoryAnalysisService;
use App\Services\RepositorySyncService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        // Register services as singletons so they are only instantiated once per request
        $this->app->singleton(GitHubService::class);
        $this->app->singleton(OpenRouterService::class);
        $this->app->singleton(PortfolioScoreService::class);
        $this->app->singleton(RepositoryAnalysisService::class);
        $this->app->singleton(RepositorySyncService::class);
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Repository::class, RepositoryPolicy::class);

        // Register Blade directives
        Blade::directive('languageColor', function ($language) {
            return "<?php echo \App\Helpers\LanguageColorHelper::getLanguageColor($language); ?>";
        });

        Blade::directive('languageBadge', function ($expression) {
            return "<?php echo \App\Helpers\LanguageColorHelper::getLanguageBadge($expression); ?>";
        });
    }
}

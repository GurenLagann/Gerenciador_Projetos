<?php

namespace App\Providers;

use App\Models\Annotation;
use App\Models\Idea;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\TechnicalDebt;
use App\Observers\SearchableObserver;
use Illuminate\Support\ServiceProvider;

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
        Project::observe(SearchableObserver::class);
        Idea::observe(SearchableObserver::class);
        Annotation::observe(SearchableObserver::class);
        Milestone::observe(SearchableObserver::class);
        TechnicalDebt::observe(SearchableObserver::class);
    }
}

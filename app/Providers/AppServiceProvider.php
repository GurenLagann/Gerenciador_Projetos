<?php

namespace App\Providers;

use App\Models\Annotation;
use App\Models\Idea;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\TechnicalDebt;
use App\Observers\AnnotationObserver;
use App\Observers\IdeaObserver;
use App\Observers\MilestoneObserver;
use App\Observers\ProjectObserver;
use App\Observers\TechnicalDebtObserver;
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
        Project::observe(ProjectObserver::class);
        Idea::observe(IdeaObserver::class);
        Annotation::observe(AnnotationObserver::class);
        Milestone::observe(MilestoneObserver::class);
        TechnicalDebt::observe(TechnicalDebtObserver::class);
    }
}

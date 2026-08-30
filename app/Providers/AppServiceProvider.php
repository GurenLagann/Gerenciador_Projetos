<?php

namespace App\Providers;

use App\Models\Annotation;
use App\Models\Idea;
use App\Models\Project;
use App\Observers\AnnotationObserver;
use App\Observers\IdeaObserver;
use App\Observers\ProjectObserver;
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
    }
}

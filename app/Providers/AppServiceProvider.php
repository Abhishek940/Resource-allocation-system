<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Repositories\ProjectRepositoryInterface;
use App\Repositories\ProjectRepository;
use App\Repositories\DashboardRepositoryInterface;
use App\Repositories\DashboardRepository;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       $this->app->bind(ProjectRepositoryInterface::class,ProjectRepository::class);
       $this->app->bind(DashboardRepositoryInterface::class,DashboardRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

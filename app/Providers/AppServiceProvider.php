<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\ResourceEligibilityRepositoryInterface;
use App\Repositories\ResourceEligibilityRepository;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       $this->app->bind(ResourceEligibilityRepositoryInterface::class,
        ResourceEligibilityRepository::class,
           
        );
       
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

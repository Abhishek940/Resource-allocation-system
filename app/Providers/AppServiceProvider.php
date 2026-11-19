<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\EmployeeRepositoryInterface;
use App\Repositories\EmployeeRepository;
use App\Repositories\RoleRepositoryInterface;
use App\Repositories\RoleRepository;
use App\Repositories\AuthRepositoryInterface;
use App\Repositories\AuthRepository;
class AppServiceProvider extends ServiceProvider
{
   public function register(): void
    {
         $this->app->bind(
            EmployeeRepositoryInterface::class,
            EmployeeRepository::class,
            
        );
        $this->app->bind(AuthRepositoryInterface::class,AuthRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

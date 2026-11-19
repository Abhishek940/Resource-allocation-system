<?php 


namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\DashboardRepositoryInterface;
use Override;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getActiveProjects()
    {
        return DB::table('projects')->where('status', 2)->where('deleted_at',0)
         ->get();
    }

    public function getActiveProjectCount(): int
    {
        return DB::table('projects')
            ->where('status', 2)
            ->where('deleted_at', 0)
            ->count();
    }

    public function getBenchResourceCount(): int
    {
        return DB::table('employees')
            ->whereRaw('LOWER(TRIM(resourceStatus)) = ?',['bench'])
            ->count();
    }

     
  
}

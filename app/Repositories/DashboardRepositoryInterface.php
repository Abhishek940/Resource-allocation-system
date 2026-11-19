<?php

namespace App\Repositories;

interface DashboardRepositoryInterface
{
    public function getActiveProjects();

    public function getActiveProjectCount(): int;

    public function getBenchResourceCount(): int;

 

}


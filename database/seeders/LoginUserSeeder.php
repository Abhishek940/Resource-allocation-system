<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    
    public function run(): void
    {
         $users = [
            [
                'roleId' => 1,
                'username' => 'systemAdmin',
                'password' => Hash::make('Admin@123'),
            ],
            [
                'roleId' => 2,
                'username' => 'lkUser',
                'password' => Hash::make('LK@123'),
            ],
            [
                'roleId' => 3,
                'username' => 'resourceManager',
                'password' => Hash::make('Resource@123'),
            ],
            [
                'roleId' => 4,
                'username' => 'projectManager',
                'password' => Hash::make('Project@123'),
            ],
            [
                'roleId' => 5,
                'username' => 'authorizedAllocator',
                'password' => Hash::make('Allocator@123'),
            ],
            [
                'roleId' => 6,
                'username' => 'readonlyManagement',
                'password' => Hash::make('ReadOnly@123'),
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                [
                    'roleId' => $user['roleId'],
                    'password' => $user['password'],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    
    }

}

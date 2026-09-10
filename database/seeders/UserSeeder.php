<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        foreach (['admin', 'manager', 'staff', 'investor'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@bwetfarms.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_approved' => true,
                'phone' => '08012345678',
                'farm_location' => 'Lagos',
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@bwetfarms.com',
                'password' => bcrypt('password'),
                'role' => 'manager',
                'is_approved' => true,
                'phone' => '08087654321',
                'farm_location' => 'Ogun',
            ],
            [
                'name' => 'Staff User',
                'email' => 'staff@bwetfarms.com',
                'password' => bcrypt('password'),
                'role' => 'staff',
                'is_approved' => true,
                'phone' => '08098765432',
                'farm_location' => 'Oyo',
            ],
            [
                'name' => 'Investor User',
                'email' => 'investor@bwetfarms.com',
                'password' => bcrypt('password'),
                'role' => 'investor',
                'is_approved' => true,
                'phone' => '08055555555',
                'farm_location' => 'Abuja',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            $user->assignRole($userData['role']);
        }
    }
}
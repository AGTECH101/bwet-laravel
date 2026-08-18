<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SectorSeeder::class,
            SystemVariableSeeder::class,
            PenSeeder::class,
            UserSeeder::class,
            RealisticFarmSeeder::class,
        ]);
    }
}
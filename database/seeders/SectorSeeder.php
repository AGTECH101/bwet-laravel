<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        Sector::firstOrCreate(
            ['slug' => 'poultry'],
            [
                'name' => 'Poultry',
                'description' => 'Broiler and layer production',
                'status' => 'active',
                'is_live' => true,
            ]
        );
    }
}
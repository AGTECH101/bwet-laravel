<?php

namespace Database\Seeders;

use App\Models\Poultry\Pen;
use Illuminate\Database\Seeder;

class PenSeeder extends Seeder
{
    public function run(): void
    {
        $pens = [
            ['name' => 'Brooding House A', 'pen_code' => 'brood1', 'pen_type' => 'brooding', 'capacity' => 5000],
            ['name' => 'Brooding House B', 'pen_code' => 'brood2', 'pen_type' => 'brooding', 'capacity' => 5000],
            ['name' => 'Pen 1', 'pen_code' => 'pen1', 'pen_type' => 'batch', 'capacity' => 10000],
            ['name' => 'Pen 2', 'pen_code' => 'pen2', 'pen_type' => 'batch', 'capacity' => 10000],
            ['name' => 'Pen 3', 'pen_code' => 'pen3', 'pen_type' => 'batch', 'capacity' => 10000],
            ['name' => 'Pen 4', 'pen_code' => 'pen4', 'pen_type' => 'batch', 'capacity' => 10000],
        ];

        foreach ($pens as $pen) {
            Pen::firstOrCreate(['pen_code' => $pen['pen_code']], $pen);
        }
    }
}
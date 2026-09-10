<?php

namespace Database\Seeders;

use App\Models\SystemVariable;
use Illuminate\Database\Seeder;

class SystemVariableSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Profit Margin', 'key' => 'profit_margin', 'category' => 'financial', 'value' => '20', 'data_type' => 'percentage'],
            ['name' => 'Dress Percentage', 'key' => 'dress_percentage', 'category' => 'financial', 'value' => '75', 'data_type' => 'percentage'],
            ['name' => 'Weighing Frequency (Days)', 'key' => 'weighing_frequency_days', 'category' => 'weighing', 'value' => '4', 'data_type' => 'integer'],
            ['name' => 'Daily Profit Tolerance (%)', 'key' => 'daily_profit_tolerance', 'category' => 'performance', 'value' => '-15', 'data_type' => 'percentage'],
            ['name' => 'FCR Efficiency Tolerance (%)', 'key' => 'fcr_efficiency_tolerance', 'category' => 'performance', 'value' => '20', 'data_type' => 'percentage'],
            ['name' => 'Stop Loss Amount (₦)', 'key' => 'stop_loss_amount', 'category' => 'financial', 'value' => '20000', 'data_type' => 'decimal'],
        ];

        foreach ($defaults as $var) {
            SystemVariable::firstOrCreate(['key' => $var['key']], $var);
        }
    }
}
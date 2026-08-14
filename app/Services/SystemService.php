<?php

namespace App\Services;

use App\Models\SystemVariable;
use Illuminate\Support\Facades\Cache;

class SystemService
{
    public static function initializeDefaultVariables()
    {
        $defaults = [
            ['key' => 'profit_margin', 'name' => 'Profit Margin', 'value' => '20', 'data_type' => 'percentage', 'category' => 'financial'],
            ['key' => 'dress_percentage', 'name' => 'Dress Percentage', 'value' => '75', 'data_type' => 'percentage', 'category' => 'financial'],
            ['key' => 'weighing_frequency_days', 'name' => 'Weighing Frequency (Days)', 'value' => '4', 'data_type' => 'integer', 'category' => 'weighing'],
            ['key' => 'daily_profit_tolerance', 'name' => 'Daily Profit Tolerance (%)', 'value' => '-15', 'data_type' => 'percentage', 'category' => 'performance'],
            ['key' => 'fcr_efficiency_tolerance', 'name' => 'FCR Efficiency Tolerance (%)', 'value' => '20', 'data_type' => 'percentage', 'category' => 'performance'],
            ['key' => 'stop_loss_amount', 'name' => 'Stop Loss Amount (₦)', 'value' => '20000', 'data_type' => 'decimal', 'category' => 'financial'],
        ];

        foreach ($defaults as $var) {
            SystemVariable::firstOrCreate(
                ['key' => $var['key']],
                $var
            );
        }
    }
}
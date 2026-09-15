<?php

namespace App\Services;

use App\Models\SystemVariable;

class SystemService
{
    /**
     * Ensure every default variable exists exactly once.
     *
     * - Missing keys are created.
     * - Existing keys are left alone (the admin's value wins).
     *
     * The old version of this method created a new row whenever the stored
     * value differed from the default, which spawned duplicates that never
     * went away. This version respects the one-row-per-key invariant.
     */
    public static function initializeDefaultVariables(): void
    {
        $defaults = [
            ['key' => 'profit_margin',             'name' => 'Profit Margin',              'value' => '20',     'data_type' => 'percentage', 'category' => 'financial'],
            ['key' => 'dress_percentage',          'name' => 'Dress Percentage',           'value' => '75',     'data_type' => 'percentage', 'category' => 'financial'],
            ['key' => 'weighing_frequency_days',   'name' => 'Weighing Frequency (Days)',  'value' => '4',      'data_type' => 'integer',    'category' => 'weighing'],
            ['key' => 'daily_profit_tolerance',    'name' => 'Daily Profit Tolerance (%)', 'value' => '-15',    'data_type' => 'percentage', 'category' => 'performance'],
            ['key' => 'fcr_efficiency_tolerance',  'name' => 'FCR Efficiency Tolerance (%)', 'value' => '20',   'data_type' => 'percentage', 'category' => 'performance'],
            ['key' => 'stop_loss_amount',          'name' => 'Stop Loss Amount (₦)',       'value' => '20000',  'data_type' => 'decimal',    'category' => 'financial'],
        ];

        foreach ($defaults as $default) {
            SystemVariable::firstOrCreate(
                ['key' => $default['key']],
                array_merge($default, ['effective_from' => now()])
            );
        }
    }
}
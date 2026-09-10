<?php

use Illuminate\Support\Str;
use App\Models\Poultry\Batch;

if (!function_exists('format_currency')) {
    function format_currency($value, $default = '₦0.00')
    {
        if (is_null($value) || $value === '') {
            return $default;
        }
        try {
            $amount = floatval($value);
            return '₦' . number_format($amount, 2);
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('format_weight')) {
    function format_weight($value, $default = '0.000 kg')
    {
        if (is_null($value) || $value === '') {
            return $default;
        }
        try {
            $weight = floatval($value);
            return number_format($weight, 3) . ' kg';
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('format_percentage')) {
    function format_percentage($value, $decimals = 1, $default = '0%')
    {
        if (is_null($value) || $value === '') {
            return $default;
        }
        try {
            $percent = floatval($value);
            return number_format($percent, $decimals) . '%';
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('format_fcr')) {
    function format_fcr($value, $decimals = 3, $default = '0.000')
    {
        if (is_null($value) || $value === '') {
            return $default;
        }
        try {
            $fcr = floatval($value);
            return number_format($fcr, $decimals);
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('format_date')) {
    function format_date($date, $format = 'd M Y')
    {
        if (!$date) {
            return 'N/A';
        }
        if ($date instanceof \DateTimeInterface) {
            return $date->format($format);
        }
        try {
            return \Carbon\Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return 'Invalid date';
        }
    }
}

// Badge helpers (return HTML)
if (!function_exists('batch_status_badge')) {
    function batch_status_badge($status)
    {
        $classes = [
            'active' => 'bg-green-100 text-green-800',
            'closed' => 'bg-gray-100 text-gray-800',
            'completed' => 'bg-blue-100 text-blue-800',
        ];
        $label = ucfirst($status);
        $class = $classes[$status] ?? 'bg-gray-100 text-gray-800';
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}\">{$label}</span>";
    }
}

if (!function_exists('priority_badge')) {
    function priority_badge($priority)
    {
        $classes = [
            'critical' => 'bg-red-100 text-red-800',
            'high' => 'bg-orange-100 text-orange-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-blue-100 text-blue-800',
        ];
        $label = ucfirst($priority);
        $class = $classes[$priority] ?? 'bg-gray-100 text-gray-800';
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}\">{$label}</span>";
    }
}

if (!function_exists('observation_status_badge')) {
    function observation_status_badge($status)
    {
        $classes = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'reviewed' => 'bg-blue-100 text-blue-800',
            'resolved' => 'bg-green-100 text-green-800',
            'closed' => 'bg-gray-100 text-gray-800',
            'action_taken' => 'bg-purple-100 text-purple-800',
        ];
        $label = ucfirst(str_replace('_', ' ', $status));
        $class = $classes[$status] ?? 'bg-gray-100 text-gray-800';
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}\">{$label}</span>";
    }
}

if (!function_exists('cv_status_badge')) {
    function cv_status_badge($status)
    {
        $classes = [
            'excellent' => 'bg-green-100 text-green-800',
            'caution' => 'bg-yellow-100 text-yellow-800',
            'warning' => 'bg-orange-100 text-orange-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];
        $label = ucfirst($status);
        $class = $classes[$status] ?? 'bg-gray-100 text-gray-800';
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}\">{$label}</span>";
    }
}

if (!function_exists('notification_type_badge')) {
    function notification_type_badge($type)
    {
        $classes = [
            'weighing_day' => 'bg-blue-100 text-blue-800',
            'missed_weighing' => 'bg-yellow-100 text-yellow-800',
            'low_stock' => 'bg-red-100 text-red-800',
            'batch_closed' => 'bg-green-100 text-green-800',
            'system' => 'bg-gray-100 text-gray-800',
            'slaughter_trigger' => 'bg-red-100 text-red-800',
            'observation_report' => 'bg-purple-100 text-purple-800',
            'manual_mode' => 'bg-orange-100 text-orange-800',
        ];
        $label = ucfirst(str_replace('_', ' ', $type));
        $class = $classes[$type] ?? 'bg-gray-100 text-gray-800';
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}\">{$label}</span>";
    }
}

if (!function_exists('days_since')) {
    function days_since($date)
    {
        if (!$date) {
            return 'N/A';
        }
        try {
            $carbon = \Carbon\Carbon::parse($date);
            return $carbon->diffForHumans();
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}

// Calculate percentage of total
if (!function_exists('percentage_of')) {
    function percentage_of($part, $total, $decimals = 1)
    {
        if ($total == 0) {
            return 0;
        }
        return round(($part / $total) * 100, $decimals);
    }
}

if (!function_exists('sector_id')) {
    function sector_id(string $slug): int
    {
        static $cache = [];
        if (isset($cache[$slug])) {
            return $cache[$slug];
        }
        $sector = \App\Models\Sector::where('slug', $slug)->first();
        $cache[$slug] = $sector?->id ?? 0;
        return $cache[$slug];
    }
}
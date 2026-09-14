<?php

use App\Models\Sector;

// ============================================================
// SECTOR HELPERS
// ============================================================

if (!function_exists('sector_id')) {
    function sector_id(string $slug): ?int
    {
        static $cache = [];
        if (!array_key_exists($slug, $cache)) {
            $cache[$slug] = Sector::where('slug', $slug)->value('id');
        }
        return $cache[$slug];
    }
}

// ============================================================
// FORMATTING HELPERS
// ============================================================

if (!function_exists('format_currency')) {
    function format_currency($amount, $symbol = '₦'): string
    {
        return $symbol . number_format((float) $amount, 2);
    }
}

if (!function_exists('format_weight')) {
    function format_weight($kg, $unit = 'kg'): string
    {
        return number_format((float) $kg, 3) . ' ' . $unit;
    }
}

if (!function_exists('format_fcr')) {
    function format_fcr($fcr): string
    {
        return number_format((float) $fcr, 3);
    }
}

if (!function_exists('format_percentage')) {
    function format_percentage($value, $decimals = 2): string
    {
        return number_format((float) $value, $decimals) . '%';
    }
}

// ============================================================
// BADGE HELPERS
// ============================================================

if (!function_exists('batch_status_badge')) {
    function batch_status_badge(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'active'    => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>',
            'closed'    => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">Closed</span>',
            'completed' => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">Completed</span>',
            default     => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">' . ucfirst($status ?: 'Unknown') . '</span>',
        };
    }
}

if (!function_exists('cv_status_badge')) {
    /**
     * Badge for CV status. 'high' is a warning badge (not an error).
     * 'rejected' is kept as an alias for legacy data.
     */
    function cv_status_badge(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'excellent' => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">✅ Excellent</span>',
            'caution'   => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">✓ Good</span>',
            'warning'   => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">⚠️ Caution</span>',
            'high'      => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800">⚠️ High Variation</span>',
            'rejected'  => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800">⚠️ High Variation</span>',
            default     => '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">' . ucfirst($status ?: 'Unknown') . '</span>',
        };
    }
}

if (!function_exists('observation_status_badge')) {
    function observation_status_badge(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'pending'      => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>',
            'reviewed'     => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Reviewed</span>',
            'action_taken' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Action Taken</span>',
            'resolved'     => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Resolved</span>',
            'closed'       => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Closed</span>',
            default        => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">' . ucfirst($status ?: 'Unknown') . '</span>',
        };
    }
}

if (!function_exists('priority_badge')) {
    function priority_badge(?string $priority): string
    {
        $priority = strtolower((string) $priority);

        return match ($priority) {
            'low'      => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Low</span>',
            'medium'   => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Medium</span>',
            'high'     => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">High</span>',
            'critical' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Critical</span>',
            default    => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">' . ucfirst($priority ?: 'Unknown') . '</span>',
        };
    }
}
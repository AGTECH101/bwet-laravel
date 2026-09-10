<?php

namespace Tests\Feature;

use App\Models\SystemVariable;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemVariableFutureOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_variable_changes_only_apply_to_future_calculations(): void
    {
        $variable = SystemVariable::create([
            'name' => 'Profit Margin',
            'key' => 'profit_margin',
            'category' => 'financial',
            'value' => '20',
            'data_type' => 'percentage',
            'is_active' => true,
        ]);

        $variable->updated_at = Carbon::now()->subDays(10);
        $variable->saveQuietly();

        $variable->update([
            'value' => '35',
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        $this->assertSame(20.0, SystemVariable::getValueForDate('profit_margin', Carbon::now()->subDays(5)));
        $this->assertSame(35.0, SystemVariable::getValueForDate('profit_margin', Carbon::now()));
    }
}

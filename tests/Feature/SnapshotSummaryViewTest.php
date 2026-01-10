<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SnapshotSummaryViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_summary_includes_date_range_filters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('plan.snapshots.summary'));

        $response->assertOk();
        $response->assertSee('name="range_start"', false);
        $response->assertSee('name="range_end"', false);
        $response->assertSee('Apply filter');
        $response->assertSee('Reset range');
        $response->assertSee(":class=\"trendClass('assets')\"", false);
    }
}

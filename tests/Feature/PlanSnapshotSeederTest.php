<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanSnapshotSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_five_years_of_monthly_snapshots(): void
    {
        $this->seed();

        $plan = Plan::first();

        $this->assertNotNull($plan);
        $this->assertSame(1, $plan->user_id);

        $snapshots = $plan->snapshots()->reorder('captured_at')->get();

        $this->assertCount(60, $snapshots);

        $firstValue = $snapshots[0]->payload['netWorth'][0]['assets'];
        $secondValue = $snapshots[1]->payload['netWorth'][0]['assets'];

        $this->assertEqualsWithDelta($firstValue * 0.95, $secondValue, 0.01);
    }
}

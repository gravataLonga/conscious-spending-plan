<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanSnapshotSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_static_plan_snapshot_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Jonathan Fontes',
            'email' => 'me@jonathan.pt',
        ]);

        $this->seed(\Database\Seeders\PlanSnapshotSeeder::class);

        $plan = Plan::where('user_id', $user->id)->first();

        $this->assertNotNull($plan);
        $this->assertSame('Default Plan', $plan->name);
        $this->assertSame('USD', $plan->currency);
        $this->assertSame(15.0, (float) $plan->buffer_percent);

        $plan->load([
            'partners',
            'netWorths',
            'incomes',
            'expenseCategories.entries',
            'investingCategories.entries',
            'savingGoalCategories.entries',
        ]);

        $this->assertCount(1, $plan->partners);
        $this->assertSame('Mariana & Jonathan', $plan->partners->first()?->name);

        $this->assertSame(15000.0, (float) $plan->netWorths->first()?->assets);
        $this->assertSame(3900.0, (float) $plan->incomes->first()?->net);

        $this->assertSame(
            [
                'Rent or Mortgage',
                'Utilities',
                'Car & Commute',
                'Groceries',
                'Pet',
                'Health & Well Bean',
                'Debt',
            ],
            $plan->expenseCategories->sortBy('sort')->pluck('name')->values()->all()
        );

        $rent = $plan->expenseCategories->firstWhere('name', 'Rent or Mortgage');
        $this->assertSame(600.0, (float) $rent?->entries->first()?->amount);

        $investing = $plan->investingCategories->firstWhere('name', 'PPR');
        $this->assertSame(313.0, (float) $investing?->entries->first()?->amount);

        $savingGoal = $plan->savingGoalCategories->firstWhere('name', 'Purifier');
        $this->assertSame(150.0, (float) $savingGoal?->entries->first()?->amount);

        $snapshot = $plan->snapshots()->first();
        $this->assertNotNull($snapshot);
        $this->assertSame('January 2026', $snapshot->name);
        $this->assertSame('USD', $snapshot->payload['plan']['currency'] ?? null);
        $this->assertSame(
            'Rent or Mortgage',
            $snapshot->payload['expenses'][0]['label'] ?? null
        );
        $this->assertEquals(
            [600.0],
            $snapshot->payload['expenses'][0]['values'] ?? null
        );
    }
}

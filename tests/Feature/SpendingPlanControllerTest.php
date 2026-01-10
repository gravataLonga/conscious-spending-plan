<?php

namespace Tests\Feature;

use App\Models\ExpenseEntry;
use App\Models\Income;
use App\Models\InvestingEntry;
use App\Models\NetWorth;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\PlanSnapshot;
use App\Models\SavingGoalEntry;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SpendingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $this->seed(CurrencySeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user);

        return $user;
    }

    public function test_it_returns_default_plan_payload(): void
    {
        $user = $this->signIn();

        $response = $this->getJson(route('plan.data'));

        $response->assertOk();

        $data = $response->json();

        $this->assertSame('USD', $data['plan']['currency']);
        $this->assertEquals(15.0, $data['plan']['buffer_percent']);
        $this->assertTrue(
            collect($data['currencies'] ?? [])->contains('code', 'USD')
        );
        $this->assertCount(2, $data['partners']);
        $this->assertCount(9, $data['expenses']);
        $this->assertCount(3, $data['investing']);
        $this->assertCount(3, $data['savingGoals']);
        $this->assertCount(2, $data['expenses'][0]['values']);

        $this->assertSame(1, Plan::count());
        $this->assertSame($user->id, Plan::first()->user_id);
        $this->assertSame(2, Partner::count());
    }

    public function test_it_persists_plan_updates(): void
    {
        $user = $this->signIn();

        $payload = $this->getJson(route('plan.data'))->json();

        $expenseCategoryId = $payload['expenses'][0]['id'];
        $investingCategoryId = $payload['investing'][0]['id'];
        $savingGoalCategoryId = $payload['savingGoals'][0]['id'];

        $storePayload = [
            'plan' => [
                'buffer_percent' => 10,
                'currency' => 'EUR',
            ],
            'partners' => [
                ['name' => 'Alex'],
                ['name' => 'Sam'],
            ],
            'netWorth' => [
                [
                    'assets' => 100,
                    'invested' => 200,
                    'saving' => 50,
                    'debt' => 20,
                ],
                [
                    'assets' => 300,
                    'invested' => 400,
                    'saving' => 60,
                    'debt' => 0,
                ],
            ],
            'income' => [
                [
                    'net' => 5000,
                    'gross' => 7000,
                ],
                [
                    'net' => 4000,
                    'gross' => 6000,
                ],
            ],
            'expenses' => [
                [
                    'id' => $expenseCategoryId,
                    'values' => [100, 200],
                ],
            ],
            'investing' => [
                [
                    'id' => $investingCategoryId,
                    'values' => [300, 400],
                ],
            ],
            'savingGoals' => [
                [
                    'id' => $savingGoalCategoryId,
                    'values' => [50, 60],
                ],
            ],
        ];

        $this->postJson(route('plan.store'), $storePayload)->assertOk();

        $plan = Plan::where('user_id', $user->id)->firstOrFail();
        $partners = Partner::orderBy('id')->get();

        $this->assertSame(10.0, (float) $plan->buffer_percent);
        $this->assertSame('EUR', $plan->currency);
        $this->assertSame('Alex', $partners[0]->name);
        $this->assertSame('Sam', $partners[1]->name);

        $this->assertDatabaseHas((new NetWorth)->getTable(), [
            'plan_id' => $plan->id,
            'partner_id' => $partners[0]->id,
            'assets' => 100,
            'invested' => 200,
            'saving' => 50,
            'debt' => 20,
        ]);

        $this->assertDatabaseHas((new NetWorth)->getTable(), [
            'plan_id' => $plan->id,
            'partner_id' => $partners[1]->id,
            'assets' => 300,
            'invested' => 400,
            'saving' => 60,
            'debt' => 0,
        ]);

        $this->assertDatabaseHas((new Income)->getTable(), [
            'plan_id' => $plan->id,
            'partner_id' => $partners[0]->id,
            'net' => 5000,
            'gross' => 7000,
        ]);

        $this->assertDatabaseHas((new Income)->getTable(), [
            'plan_id' => $plan->id,
            'partner_id' => $partners[1]->id,
            'net' => 4000,
            'gross' => 6000,
        ]);

        $this->assertDatabaseHas((new ExpenseEntry)->getTable(), [
            'expense_category_id' => $expenseCategoryId,
            'partner_id' => $partners[0]->id,
            'amount' => 100,
        ]);

        $this->assertDatabaseHas((new ExpenseEntry)->getTable(), [
            'expense_category_id' => $expenseCategoryId,
            'partner_id' => $partners[1]->id,
            'amount' => 200,
        ]);

        $this->assertDatabaseHas((new InvestingEntry)->getTable(), [
            'investing_category_id' => $investingCategoryId,
            'partner_id' => $partners[0]->id,
            'amount' => 300,
        ]);

        $this->assertDatabaseHas((new InvestingEntry)->getTable(), [
            'investing_category_id' => $investingCategoryId,
            'partner_id' => $partners[1]->id,
            'amount' => 400,
        ]);

        $this->assertDatabaseHas((new SavingGoalEntry)->getTable(), [
            'saving_goal_category_id' => $savingGoalCategoryId,
            'partner_id' => $partners[0]->id,
            'amount' => 50,
        ]);

        $this->assertDatabaseHas((new SavingGoalEntry)->getTable(), [
            'saving_goal_category_id' => $savingGoalCategoryId,
            'partner_id' => $partners[1]->id,
            'amount' => 60,
        ]);
    }

    public function test_it_creates_a_snapshot_of_the_current_plan(): void
    {
        $this->signIn();

        $payload = $this->getJson(route('plan.data'))->json();

        $expenseCategoryId = $payload['expenses'][0]['id'];
        $investingCategoryId = $payload['investing'][0]['id'];
        $savingGoalCategoryId = $payload['savingGoals'][0]['id'];

        $storePayload = [
            'plan' => [
                'buffer_percent' => 10,
            ],
            'partners' => [
                ['name' => 'Alex'],
                ['name' => 'Sam'],
            ],
            'netWorth' => [
                [
                    'assets' => 100,
                    'invested' => 200,
                    'saving' => 50,
                    'debt' => 20,
                ],
                [
                    'assets' => 300,
                    'invested' => 400,
                    'saving' => 60,
                    'debt' => 0,
                ],
            ],
            'income' => [
                [
                    'net' => 5000,
                    'gross' => 7000,
                ],
                [
                    'net' => 4000,
                    'gross' => 6000,
                ],
            ],
            'expenses' => [
                [
                    'id' => $expenseCategoryId,
                    'values' => [100, 200],
                ],
            ],
            'investing' => [
                [
                    'id' => $investingCategoryId,
                    'values' => [300, 400],
                ],
            ],
            'savingGoals' => [
                [
                    'id' => $savingGoalCategoryId,
                    'values' => [50, 60],
                ],
            ],
        ];

        $this->postJson(route('plan.store'), $storePayload)->assertOk();

        Carbon::setTestNow(Carbon::create(2025, 3, 15, 10, 0, 0));

        $response = $this->postJson(route('plan.snapshots.store'), [
            'note' => 'First month after bonus payout.',
        ]);

        $response->assertCreated();

        $snapshot = PlanSnapshot::first();

        $this->assertNotNull($snapshot);
        $this->assertSame('March 2025', $snapshot->name);
        $this->assertSame('First month after bonus payout.', $snapshot->note);
        $this->assertNotNull($snapshot->snapshot_plan_id);

        $snapshotPlan = Plan::find($snapshot->snapshot_plan_id);

        $this->assertNotNull($snapshotPlan);
        $this->assertTrue($snapshotPlan->is_snapshot);
        $this->assertEquals(1090.0, (float) $snapshot->total_net_worth);
        $this->assertEquals(9000.0, (float) $snapshot->net_income);
        $this->assertEquals(330.0, (float) $snapshot->total_expenses);
        $this->assertEquals(110.0, (float) $snapshot->total_saving);
        $this->assertEquals(700.0, (float) $snapshot->total_investing);
        $this->assertEquals(7860.0, (float) $snapshot->guilt_free);

        Carbon::setTestNow();
    }

    public function test_it_lists_snapshots_for_the_plan(): void
    {
        $this->signIn();

        $this->postJson(route('plan.snapshots.store'))->assertCreated();
        $this->postJson(route('plan.snapshots.store'))->assertCreated();

        $response = $this->getJson(route('plan.snapshots'));

        $response->assertOk();
        $this->assertCount(2, $response->json('snapshots'));
        $snapshot = $response->json('snapshots.0');

        $this->assertIsNumeric($snapshot['total_net_worth'] ?? null);
        $this->assertIsNumeric($snapshot['net_income'] ?? null);
        $this->assertIsNumeric($snapshot['total_expenses'] ?? null);
        $this->assertIsNumeric($snapshot['total_saving'] ?? null);
        $this->assertIsNumeric($snapshot['total_investing'] ?? null);
        $this->assertIsNumeric($snapshot['guilt_free'] ?? null);
    }

    public function test_it_paginates_snapshots_for_the_plan(): void
    {
        $this->signIn();

        for ($i = 0; $i < 12; $i++) {
            $this->postJson(route('plan.snapshots.store'))->assertCreated();
        }

        $response = $this->getJson(route('plan.snapshots', ['page' => 1, 'per_page' => 10]));

        $response->assertOk();
        $response->assertJsonStructure([
            'snapshots',
            'meta' => ['current_page', 'last_page', 'total', 'per_page'],
        ]);
        $this->assertCount(10, $response->json('snapshots'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }

    public function test_snapshot_summary_uses_stored_totals(): void
    {
        $this->signIn();

        $this->postJson(route('plan.snapshots.store'))->assertCreated();

        $snapshot = PlanSnapshot::firstOrFail();
        $snapshot->update([
            'total_net_worth' => 12345.67,
            'net_income' => 8901.23,
            'total_expenses' => 456.78,
            'total_saving' => 321.09,
            'total_investing' => 654.32,
            'guilt_free' => 987.65,
        ]);

        $response = $this->getJson(route('plan.snapshots.summary.data'));

        $response->assertOk();
        $summary = $response->json('snapshots.0');

        $this->assertEquals(12345.67, (float) ($summary['net_worth'] ?? 0));
        $this->assertEquals(8901.23, (float) ($summary['net_income'] ?? 0));
        $this->assertEquals(456.78, (float) ($summary['expenses'] ?? 0));
        $this->assertEquals(321.09, (float) ($summary['saving'] ?? 0));
        $this->assertEquals(654.32, (float) ($summary['investing'] ?? 0));
        $this->assertEquals(987.65, (float) ($summary['guilt_free'] ?? 0));
    }

    public function test_it_does_not_allow_accessing_another_users_snapshot(): void
    {
        $this->signIn();
        $ownerSnapshotResponse = $this->postJson(route('plan.snapshots.store'));
        $ownerSnapshotResponse->assertCreated();
        $snapshotId = $ownerSnapshotResponse->json('snapshot.id');

        $this->actingAs(User::factory()->create());

        $this->getJson(route('plan.snapshots.show', ['snapshot' => $snapshotId]))
            ->assertNotFound();
    }

    public function test_it_requires_authentication_for_plan_endpoints(): void
    {
        $this->getJson(route('plan.data'))->assertUnauthorized();
        $this->postJson(route('plan.store'))->assertUnauthorized();
    }

    public function test_it_requires_authentication_for_snapshot_endpoints(): void
    {
        $this->getJson(route('plan.snapshots'))->assertUnauthorized();
        $this->postJson(route('plan.snapshots.store'))->assertUnauthorized();
        $this->getJson(route('plan.snapshots.show', ['snapshot' => 1]))->assertUnauthorized();
    }

    public function test_it_sanitizes_csv_formula_injection(): void
    {
        $this->signIn();

        $payload = $this->getJson(route('plan.data'))->json();
        $expenseCategoryId = $payload['expenses'][0]['id'];

        $this->postJson(route('plan.store'), [
            'partners' => [
                ['name' => '=HYPERLINK("http://evil.test")'],
                ['name' => '+SUM(1,1)'],
            ],
            'expenses' => [
                [
                    'id' => $expenseCategoryId,
                    'label' => '-cmd',
                    'values' => [100, 200],
                ],
            ],
        ])->assertOk();

        $response = $this->get(route('plan.export.csv'));
        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK(\"\"http://evil.test\"\")", $csv);
        $this->assertStringContainsString("'+SUM(1,1)", $csv);
        $this->assertStringContainsString("'-cmd", $csv);
    }
}

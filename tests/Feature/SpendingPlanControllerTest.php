<?php

namespace Tests\Feature;

use App\Models\ExpenseEntry;
use App\Models\Income;
use App\Models\InvestingEntry;
use App\Models\NetWorth;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\SavingGoalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpendingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_default_plan_payload(): void
    {
        $response = $this->getJson(route('plan.data'));

        $response->assertOk();

        $data = $response->json();

        $this->assertSame('USD', $data['plan']['currency']);
        $this->assertEquals(15.0, $data['plan']['buffer_percent']);
        $this->assertCount(2, $data['partners']);
        $this->assertCount(9, $data['expenses']);
        $this->assertCount(3, $data['investing']);
        $this->assertCount(3, $data['savingGoals']);
        $this->assertCount(2, $data['expenses'][0]['values']);

        $this->assertSame(1, Plan::count());
        $this->assertSame(2, Partner::count());
    }

    public function test_it_persists_plan_updates(): void
    {
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

        $plan = Plan::first();
        $partners = Partner::orderBy('id')->get();

        $this->assertSame(10.0, (float) $plan->buffer_percent);
        $this->assertSame('Alex', $partners[0]->name);
        $this->assertSame('Sam', $partners[1]->name);

        $this->assertDatabaseHas((new NetWorth())->getTable(), [
            'plan_id' => $plan->id,
            'partner_id' => $partners[0]->id,
            'assets' => 100,
            'invested' => 200,
            'saving' => 50,
            'debt' => 20,
        ]);

        $this->assertDatabaseHas((new NetWorth())->getTable(), [
            'plan_id' => $plan->id,
            'partner_id' => $partners[1]->id,
            'assets' => 300,
            'invested' => 400,
            'saving' => 60,
            'debt' => 0,
        ]);

        $this->assertDatabaseHas((new Income())->getTable(), [
            'plan_id' => $plan->id,
            'partner_id' => $partners[0]->id,
            'net' => 5000,
            'gross' => 7000,
        ]);

        $this->assertDatabaseHas((new Income())->getTable(), [
            'plan_id' => $plan->id,
            'partner_id' => $partners[1]->id,
            'net' => 4000,
            'gross' => 6000,
        ]);

        $this->assertDatabaseHas((new ExpenseEntry())->getTable(), [
            'expense_category_id' => $expenseCategoryId,
            'partner_id' => $partners[0]->id,
            'amount' => 100,
        ]);

        $this->assertDatabaseHas((new ExpenseEntry())->getTable(), [
            'expense_category_id' => $expenseCategoryId,
            'partner_id' => $partners[1]->id,
            'amount' => 200,
        ]);

        $this->assertDatabaseHas((new InvestingEntry())->getTable(), [
            'investing_category_id' => $investingCategoryId,
            'partner_id' => $partners[0]->id,
            'amount' => 300,
        ]);

        $this->assertDatabaseHas((new InvestingEntry())->getTable(), [
            'investing_category_id' => $investingCategoryId,
            'partner_id' => $partners[1]->id,
            'amount' => 400,
        ]);

        $this->assertDatabaseHas((new SavingGoalEntry())->getTable(), [
            'saving_goal_category_id' => $savingGoalCategoryId,
            'partner_id' => $partners[0]->id,
            'amount' => 50,
        ]);

        $this->assertDatabaseHas((new SavingGoalEntry())->getTable(), [
            'saving_goal_category_id' => $savingGoalCategoryId,
            'partner_id' => $partners[1]->id,
            'amount' => 60,
        ]);
    }
}

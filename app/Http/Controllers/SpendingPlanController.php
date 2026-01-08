<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\Income;
use App\Models\InvestingCategory;
use App\Models\InvestingEntry;
use App\Models\NetWorth;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\SavingGoalCategory;
use App\Models\SavingGoalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SpendingPlanController extends Controller
{
    private const DEFAULT_EXPENSES = [
        'Rent or Mortgage',
        'Utilities',
        'Insurance',
        'Car Payment',
        'Groceries',
        'Clothes',
        'Phone',
        'Subscriptions',
        'Debt',
    ];

    private const DEFAULT_INVESTING = [
        'Post-tax Retirement Saving',
        'ETF',
        'Other',
    ];

    private const DEFAULT_SAVING_GOALS = [
        'Vacation',
        'Gifts',
        'Long Term Emergency Fund',
    ];

    public function show()
    {
        return view('welcome');
    }

    public function data()
    {
        $plan = $this->ensurePlan();

        return response()->json($this->serializePlan($plan));
    }

    public function store(Request $request)
    {
        $plan = $this->ensurePlan();

        DB::transaction(function () use ($plan, $request) {
            $bufferPercent = $request->input('plan.buffer_percent');
            if ($bufferPercent !== null) {
                $plan->update(['buffer_percent' => (float) $bufferPercent]);
            }

            $partners = $this->ensurePartners($plan);
            $incomingPartners = $request->input('partners', []);

            foreach ($partners as $index => $partner) {
                $name = Arr::get($incomingPartners, "$index.name", $partner->name);
                $partner->update(['name' => $name ?: $partner->name]);
            }

            $netWorth = $request->input('netWorth', []);
            $income = $request->input('income', []);

            foreach ($partners as $index => $partner) {
                $netWorthPayload = Arr::get($netWorth, $index, []);
                NetWorth::updateOrCreate(
                    ['plan_id' => $plan->id, 'partner_id' => $partner->id],
                    [
                        'assets' => (float) Arr::get($netWorthPayload, 'assets', 0),
                        'invested' => (float) Arr::get($netWorthPayload, 'invested', 0),
                        'saving' => (float) Arr::get($netWorthPayload, 'saving', 0),
                        'debt' => (float) Arr::get($netWorthPayload, 'debt', 0),
                    ]
                );

                $incomePayload = Arr::get($income, $index, []);
                Income::updateOrCreate(
                    ['plan_id' => $plan->id, 'partner_id' => $partner->id],
                    [
                        'net' => (float) Arr::get($incomePayload, 'net', 0),
                        'gross' => (float) Arr::get($incomePayload, 'gross', 0),
                    ]
                );
            }

            $this->syncCategoryEntries(
                $plan,
                ExpenseCategory::class,
                ExpenseEntry::class,
                'expenses',
                'expense_category_id',
                $request
            );

            $this->syncCategoryEntries(
                $plan,
                InvestingCategory::class,
                InvestingEntry::class,
                'investing',
                'investing_category_id',
                $request
            );

            $this->syncCategoryEntries(
                $plan,
                SavingGoalCategory::class,
                SavingGoalEntry::class,
                'savingGoals',
                'saving_goal_category_id',
                $request
            );
        });

        $plan->refresh();

        return response()->json($this->serializePlan($plan));
    }

    private function ensurePlan(): Plan
    {
        $plan = Plan::first();

        if (! $plan) {
            $plan = Plan::create(['name' => 'Default Plan']);
        }

        $this->ensurePartners($plan);
        $this->ensureCategories($plan, ExpenseCategory::class, self::DEFAULT_EXPENSES);
        $this->ensureCategories($plan, InvestingCategory::class, self::DEFAULT_INVESTING);
        $this->ensureCategories($plan, SavingGoalCategory::class, self::DEFAULT_SAVING_GOALS);

        return $plan->fresh([
            'partners',
            'netWorths',
            'incomes',
            'expenseCategories.entries',
            'investingCategories.entries',
            'savingGoalCategories.entries',
        ]);
    }

    private function ensurePartners(Plan $plan)
    {
        $partners = $plan->partners()->orderBy('id')->get();

        while ($partners->count() < 2) {
            $partners->push($plan->partners()->create([
                'name' => 'Partner ' . ($partners->count() + 1),
            ]));
        }

        return $partners;
    }

    private function ensureCategories(Plan $plan, string $model, array $defaults): void
    {
        if ($model::where('plan_id', $plan->id)->exists()) {
            return;
        }

        foreach ($defaults as $index => $name) {
            $model::create([
                'plan_id' => $plan->id,
                'name' => $name,
                'sort' => $index + 1,
            ]);
        }
    }

    private function syncCategoryEntries(
        Plan $plan,
        string $categoryModel,
        string $entryModel,
        string $payloadKey,
        string $foreignKey,
        Request $request
    ): void {
        $partners = $plan->partners()->orderBy('id')->get();
        $payload = $request->input($payloadKey, []);

        foreach ($payload as $categoryPayload) {
            $categoryId = Arr::get($categoryPayload, 'id');
            if (! $categoryId) {
                continue;
            }

            $category = $categoryModel::where('plan_id', $plan->id)->find($categoryId);
            if (! $category) {
                continue;
            }

            $values = Arr::get($categoryPayload, 'values', []);

            foreach ($partners as $index => $partner) {
                $entryModel::updateOrCreate(
                    [$foreignKey => $category->id, 'partner_id' => $partner->id],
                    ['amount' => (float) Arr::get($values, $index, 0)]
                );
            }
        }
    }

    private function serializePlan(Plan $plan): array
    {
        $partners = $plan->partners()->orderBy('id')->get();
        $netWorths = $plan->netWorths->keyBy('partner_id');
        $incomes = $plan->incomes->keyBy('partner_id');

        return [
            'plan' => [
                'id' => $plan->id,
                'buffer_percent' => (float) $plan->buffer_percent,
                'currency' => $plan->currency,
            ],
            'partners' => $partners->map(fn (Partner $partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
            ])->values()->all(),
            'netWorth' => $partners->map(function (Partner $partner) use ($netWorths) {
                $entry = $netWorths->get($partner->id);

                return [
                    'assets' => (float) ($entry->assets ?? 0),
                    'invested' => (float) ($entry->invested ?? 0),
                    'saving' => (float) ($entry->saving ?? 0),
                    'debt' => (float) ($entry->debt ?? 0),
                ];
            })->values()->all(),
            'income' => $partners->map(function (Partner $partner) use ($incomes) {
                $entry = $incomes->get($partner->id);

                return [
                    'net' => (float) ($entry->net ?? 0),
                    'gross' => (float) ($entry->gross ?? 0),
                ];
            })->values()->all(),
            'expenses' => $plan->expenseCategories->map(function (ExpenseCategory $category) use ($partners) {
                $entries = $category->entries->keyBy('partner_id');

                return [
                    'id' => $category->id,
                    'label' => $category->name,
                    'values' => $partners->map(fn (Partner $partner) => (float) ($entries->get($partner->id)->amount ?? 0))
                        ->values()
                        ->all(),
                ];
            })->values()->all(),
            'investing' => $plan->investingCategories->map(function (InvestingCategory $category) use ($partners) {
                $entries = $category->entries->keyBy('partner_id');

                return [
                    'id' => $category->id,
                    'label' => $category->name,
                    'values' => $partners->map(fn (Partner $partner) => (float) ($entries->get($partner->id)->amount ?? 0))
                        ->values()
                        ->all(),
                ];
            })->values()->all(),
            'savingGoals' => $plan->savingGoalCategories->map(function (SavingGoalCategory $category) use ($partners) {
                $entries = $category->entries->keyBy('partner_id');

                return [
                    'id' => $category->id,
                    'label' => $category->name,
                    'values' => $partners->map(fn (Partner $partner) => (float) ($entries->get($partner->id)->amount ?? 0))
                        ->values()
                        ->all(),
                ];
            })->values()->all(),
        ];
    }
}

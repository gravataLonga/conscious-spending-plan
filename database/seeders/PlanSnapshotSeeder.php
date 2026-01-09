<?php

namespace Database\Seeders;

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
use App\Models\User;
use App\PlanDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PlanSnapshotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(1);

        if (! $user) {
            return;
        }

        $plan = Plan::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Default Plan',
                'currency' => 'USD',
                'buffer_percent' => 15,
            ]
        );

        $this->resetPlanData($plan);

        $partners = $this->seedPartners($plan);
        $netWorthBase = [
            [
                'assets' => 150000,
                'invested' => 65000,
                'saving' => 12000,
                'debt' => 20000,
            ],
            [
                'assets' => 95000,
                'invested' => 42000,
                'saving' => 9000,
                'debt' => 12000,
            ],
        ];

        $incomeBase = [
            [
                'net' => 78000,
                'gross' => 104000,
            ],
            [
                'net' => 64000,
                'gross' => 88000,
            ],
        ];

        $this->seedNetWorths($plan, $partners, $netWorthBase);
        $this->seedIncomes($plan, $partners, $incomeBase);

        $expenseDefinitions = $this->buildCategoryDefinitions(PlanDefaults::Expenses->labels(), [
            [1800, 1600],
            [260, 230],
            [220, 200],
            [350, 325],
            [520, 460],
            [140, 110],
            [120, 110],
            [80, 70],
            [200, 150],
        ]);

        $investingDefinitions = $this->buildCategoryDefinitions(PlanDefaults::Investing->labels(), [
            [450, 320],
            [300, 250],
            [140, 100],
        ]);

        $savingGoalDefinitions = $this->buildCategoryDefinitions(PlanDefaults::SavingGoals->labels(), [
            [220, 180],
            [90, 60],
            [300, 240],
        ]);

        $expenseCategories = $this->seedCategories(
            $plan,
            ExpenseCategory::class,
            ExpenseEntry::class,
            $expenseDefinitions,
            $partners
        );

        $investingCategories = $this->seedCategories(
            $plan,
            InvestingCategory::class,
            InvestingEntry::class,
            $investingDefinitions,
            $partners
        );

        $savingGoalCategories = $this->seedCategories(
            $plan,
            SavingGoalCategory::class,
            SavingGoalEntry::class,
            $savingGoalDefinitions,
            $partners
        );

        $start = Carbon::now()->subYears(5)->startOfMonth();
        $factor = 1.0;

        for ($month = 0; $month < 60; $month++) {
            $factor *= $month % 2 === 0 ? 1.10 : 0.95;
            $capturedAt = $start->copy()->addMonths($month);

            $payload = $this->buildPayload(
                $plan,
                $partners,
                $netWorthBase,
                $incomeBase,
                $expenseDefinitions,
                $investingDefinitions,
                $savingGoalDefinitions,
                $expenseCategories,
                $investingCategories,
                $savingGoalCategories,
                $factor
            );

            $plan->snapshots()->create([
                'name' => $capturedAt->format('F Y'),
                'captured_at' => $capturedAt,
                'payload' => $payload,
            ]);
        }
    }

    private function resetPlanData(Plan $plan): void
    {
        $plan->snapshots()->delete();
        $plan->netWorths()->delete();
        $plan->incomes()->delete();
        $plan->partners()->delete();
        $plan->expenseCategories()->delete();
        $plan->investingCategories()->delete();
        $plan->savingGoalCategories()->delete();
    }

    /**
     * @return array<int, Partner>
     */
    private function seedPartners(Plan $plan): array
    {
        return collect(PlanDefaults::Partners->labels())
            ->map(fn (string $name) => $plan->partners()->create(['name' => $name]))
            ->all();
    }

    /**
     * @param  array<int, array{assets: float|int, invested: float|int, saving: float|int, debt: float|int}>  $netWorthBase
     */
    private function seedNetWorths(Plan $plan, array $partners, array $netWorthBase): void
    {
        foreach ($partners as $index => $partner) {
            $payload = $netWorthBase[$index] ?? [
                'assets' => 0,
                'invested' => 0,
                'saving' => 0,
                'debt' => 0,
            ];

            NetWorth::create([
                'plan_id' => $plan->id,
                'partner_id' => $partner->id,
                'assets' => (float) $payload['assets'],
                'invested' => (float) $payload['invested'],
                'saving' => (float) $payload['saving'],
                'debt' => (float) $payload['debt'],
            ]);
        }
    }

    /**
     * @param  array<int, array{net: float|int, gross: float|int}>  $incomeBase
     */
    private function seedIncomes(Plan $plan, array $partners, array $incomeBase): void
    {
        foreach ($partners as $index => $partner) {
            $payload = $incomeBase[$index] ?? [
                'net' => 0,
                'gross' => 0,
            ];

            Income::create([
                'plan_id' => $plan->id,
                'partner_id' => $partner->id,
                'net' => (float) $payload['net'],
                'gross' => (float) $payload['gross'],
            ]);
        }
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, array<int, float|int>>  $values
     * @return array<int, array{label: string, values: array<int, float|int>}>
     */
    private function buildCategoryDefinitions(array $labels, array $values): array
    {
        return collect($labels)
            ->map(function (string $label, int $index) use ($values) {
                return [
                    'label' => $label,
                    'values' => $values[$index] ?? [],
                ];
            })
            ->all();
    }

    /**
     * @param  class-string  $categoryModel
     * @param  class-string  $entryModel
     * @param  array<int, array{label: string, values: array<int, float|int>}>  $definitions
     * @param  array<int, Partner>  $partners
     * @return array<int, object>
     */
    private function seedCategories(
        Plan $plan,
        string $categoryModel,
        string $entryModel,
        array $definitions,
        array $partners
    ): array {
        $categories = [];

        foreach ($definitions as $index => $definition) {
            $category = $categoryModel::create([
                'plan_id' => $plan->id,
                'name' => $definition['label'],
                'sort' => $index + 1,
            ]);

            foreach ($partners as $partnerIndex => $partner) {
                $values = $definition['values'] ?? [];

                $entryModel::create([
                    $this->resolveForeignKey($categoryModel) => $category->id,
                    'partner_id' => $partner->id,
                    'amount' => (float) ($values[$partnerIndex] ?? 0),
                ]);
            }

            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(
        Plan $plan,
        array $partners,
        array $netWorthBase,
        array $incomeBase,
        array $expenseDefinitions,
        array $investingDefinitions,
        array $savingGoalDefinitions,
        array $expenseCategories,
        array $investingCategories,
        array $savingGoalCategories,
        float $factor
    ): array {
        return [
            'plan' => [
                'id' => $plan->id,
                'buffer_percent' => (float) $plan->buffer_percent,
                'currency' => $plan->currency,
            ],
            'partners' => collect($partners)
                ->map(fn (Partner $partner) => [
                    'id' => $partner->id,
                    'name' => $partner->name,
                ])
                ->values()
                ->all(),
            'netWorth' => $this->scaleStructuredEntries($netWorthBase, $factor, [
                'assets',
                'invested',
                'saving',
                'debt',
            ]),
            'income' => $this->scaleStructuredEntries($incomeBase, $factor, ['net', 'gross']),
            'expenses' => $this->mapCategoryPayload($expenseDefinitions, $expenseCategories, $factor),
            'investing' => $this->mapCategoryPayload($investingDefinitions, $investingCategories, $factor),
            'savingGoals' => $this->mapCategoryPayload($savingGoalDefinitions, $savingGoalCategories, $factor),
        ];
    }

    /**
     * @param  array<int, array<string, float|int>>  $entries
     * @param  array<int, string>  $keys
     * @return array<int, array<string, float>>
     */
    private function scaleStructuredEntries(array $entries, float $factor, array $keys): array
    {
        return collect($entries)
            ->map(function (array $entry) use ($factor, $keys) {
                $scaled = [];

                foreach ($keys as $key) {
                    $value = (float) ($entry[$key] ?? 0);
                    $scaled[$key] = round($value * $factor, 2);
                }

                return $scaled;
            })
            ->all();
    }

    /**
     * @param  array<int, array{label: string, values: array<int, float|int>}>  $definitions
     * @param  array<int, object>  $categories
     * @return array<int, array{id: int, label: string, values: array<int, float>}>
     */
    private function mapCategoryPayload(array $definitions, array $categories, float $factor): array
    {
        return collect($definitions)
            ->map(function (array $definition, int $index) use ($categories, $factor) {
                $category = $categories[$index];
                $values = array_map(
                    fn ($value) => round((float) $value * $factor, 2),
                    $definition['values']
                );

                return [
                    'id' => $category->id,
                    'label' => $definition['label'],
                    'values' => $values,
                ];
            })
            ->all();
    }

    private function resolveForeignKey(string $categoryModel): string
    {
        return match ($categoryModel) {
            ExpenseCategory::class => 'expense_category_id',
            InvestingCategory::class => 'investing_category_id',
            SavingGoalCategory::class => 'saving_goal_category_id',
            default => 'category_id',
        };
    }
}

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PlanSnapshotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = $this->planSnapshotData();

        $user = User::firstWhere('email', $data['user']['email']);

        if (! $user) {
            return;
        }

        $plan = Plan::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $data['plan']['name'],
                'currency' => $data['plan']['currency'],
                'buffer_percent' => $data['plan']['buffer_percent'],
            ]
        );

        $this->resetPlanData($plan);

        $partners = collect($data['partners'])
            ->map(fn (array $partner) => $plan->partners()->create(['name' => $partner['name']]))
            ->values()
            ->all();

        $this->seedNetWorths($plan, $partners, $data['netWorth']);
        $this->seedIncomes($plan, $partners, $data['income']);

        $expenseCategories = $this->seedCategories(
            $plan,
            ExpenseCategory::class,
            ExpenseEntry::class,
            $data['expenses'],
            $partners,
            'expense_category_id'
        );

        $investingCategories = $this->seedCategories(
            $plan,
            InvestingCategory::class,
            InvestingEntry::class,
            $data['investing'],
            $partners,
            'investing_category_id'
        );

        $savingGoalCategories = $this->seedCategories(
            $plan,
            SavingGoalCategory::class,
            SavingGoalEntry::class,
            $data['savingGoals'],
            $partners,
            'saving_goal_category_id'
        );

        $payload = $this->buildPayload(
            $plan,
            $partners,
            $data,
            $expenseCategories,
            $investingCategories,
            $savingGoalCategories
        );

        $plan->snapshots()->create([
            'name' => $data['snapshot']['name'],
            'captured_at' => Carbon::parse($data['snapshot']['captured_at']),
            'payload' => $payload,
        ]);
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
     * @param  array<int, array{assets: float|int, invested: float|int, saving: float|int, debt: float|int}>  $netWorthData
     * @param  array<int, Partner>  $partners
     */
    private function seedNetWorths(Plan $plan, array $partners, array $netWorthData): void
    {
        foreach ($partners as $index => $partner) {
            $payload = $netWorthData[$index] ?? [
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
     * @param  array<int, array{net: float|int, gross: float|int}>  $incomeData
     * @param  array<int, Partner>  $partners
     */
    private function seedIncomes(Plan $plan, array $partners, array $incomeData): void
    {
        foreach ($partners as $index => $partner) {
            $payload = $incomeData[$index] ?? [
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
        array $partners,
        string $foreignKey
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
                    $foreignKey => $category->id,
                    'partner_id' => $partner->id,
                    'amount' => (float) ($values[$partnerIndex] ?? 0),
                ]);
            }

            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * @param  array<int, Partner>  $partners
     * @param  array<int, array{label: string, values: array<int, float|int>}>  $expenses
     * @param  array<int, array{label: string, values: array<int, float|int>}>  $investing
     * @param  array<int, array{label: string, values: array<int, float|int>}>  $savingGoals
     * @param  array<int, object>  $expenseCategories
     * @param  array<int, object>  $investingCategories
     * @param  array<int, object>  $savingGoalCategories
     * @return array<string, mixed>
     */
    private function buildPayload(
        Plan $plan,
        array $partners,
        array $data,
        array $expenseCategories,
        array $investingCategories,
        array $savingGoalCategories
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
            'netWorth' => $this->castStructuredEntries($data['netWorth'], [
                'assets',
                'invested',
                'saving',
                'debt',
            ]),
            'income' => $this->castStructuredEntries($data['income'], ['net', 'gross']),
            'expenses' => $this->mapCategoryPayload($data['expenses'], $expenseCategories),
            'investing' => $this->mapCategoryPayload($data['investing'], $investingCategories),
            'savingGoals' => $this->mapCategoryPayload($data['savingGoals'], $savingGoalCategories),
        ];
    }

    /**
     * @param  array<int, array<string, float|int>>  $entries
     * @param  array<int, string>  $keys
     * @return array<int, array<string, float>>
     */
    private function castStructuredEntries(array $entries, array $keys): array
    {
        return collect($entries)
            ->map(function (array $entry) use ($keys) {
                $casted = [];

                foreach ($keys as $key) {
                    $casted[$key] = (float) ($entry[$key] ?? 0);
                }

                return $casted;
            })
            ->all();
    }

    /**
     * @param  array<int, array{label: string, values: array<int, float|int>}>  $definitions
     * @param  array<int, object>  $categories
     * @return array<int, array{id: int, label: string, values: array<int, float>}>
     */
    private function mapCategoryPayload(array $definitions, array $categories): array
    {
        return collect($definitions)
            ->map(function (array $definition, int $index) use ($categories) {
                $category = $categories[$index];

                return [
                    'id' => $category->id,
                    'label' => $definition['label'],
                    'values' => array_map(
                        fn ($value) => (float) $value,
                        $definition['values']
                    ),
                ];
            })
            ->all();
    }

    /**
     * @return array{
     *     user: array{email: string},
     *     plan: array{name: string, currency: string, buffer_percent: float|int},
     *     partners: array<int, array{name: string}>,
     *     netWorth: array<int, array{assets: float|int, invested: float|int, saving: float|int, debt: float|int}>,
     *     income: array<int, array{net: float|int, gross: float|int}>,
     *     expenses: array<int, array{label: string, values: array<int, float|int>}>,
     *     investing: array<int, array{label: string, values: array<int, float|int>}>,
     *     savingGoals: array<int, array{label: string, values: array<int, float|int>}>,
     *     snapshot: array{name: string, captured_at: string}
     * }
     */
    private function planSnapshotData(): array
    {
        return [
            'user' => [
                'email' => 'me@jonathan.pt',
            ],
            'plan' => [
                'name' => 'Default Plan',
                'currency' => 'USD',
                'buffer_percent' => 15,
            ],
            'partners' => [
                [
                    'name' => 'Mariana & Jonathan',
                ],
            ],
            'netWorth' => [
                [
                    'assets' => 15000,
                    'invested' => 17484,
                    'saving' => 10863,
                    'debt' => 0,
                ],
            ],
            'income' => [
                [
                    'net' => 3900,
                    'gross' => 3900,
                ],
            ],
            'expenses' => [
                [
                    'label' => 'Rent or Mortgage',
                    'values' => [600],
                ],
                [
                    'label' => 'Utilities',
                    'values' => [200],
                ],
                [
                    'label' => 'Car & Commute',
                    'values' => [150],
                ],
                [
                    'label' => 'Groceries',
                    'values' => [350],
                ],
                [
                    'label' => 'Pet',
                    'values' => [80],
                ],
                [
                    'label' => 'Health & Well Bean',
                    'values' => [150],
                ],
                [
                    'label' => 'Debt',
                    'values' => [100],
                ],
            ],
            'investing' => [
                [
                    'label' => 'PPR',
                    'values' => [313],
                ],
                [
                    'label' => 'ETF',
                    'values' => [600],
                ],
                [
                    'label' => 'Other',
                    'values' => [0],
                ],
            ],
            'savingGoals' => [
                [
                    'label' => 'Vacation',
                    'values' => [100],
                ],
                [
                    'label' => 'Crib Dream & Remodel',
                    'values' => [100],
                ],
                [
                    'label' => 'Pet',
                    'values' => [0],
                ],
                [
                    'label' => 'Dehumidifier',
                    'values' => [50],
                ],
                [
                    'label' => 'Purifier',
                    'values' => [150],
                ],
                [
                    'label' => 'Car (QoL, Maintenance)',
                    'values' => [100],
                ],
            ],
            'snapshot' => [
                'name' => 'January 2026',
                'captured_at' => '2026-01-09 16:10:34',
            ],
        ];
    }
}

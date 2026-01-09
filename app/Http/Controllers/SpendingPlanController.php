<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanSnapshotRequest;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\Income;
use App\Models\InvestingCategory;
use App\Models\InvestingEntry;
use App\Models\NetWorth;
use App\Models\Partner;
use App\Models\Plan;
use App\Models\PlanSnapshot;
use App\Models\SavingGoalCategory;
use App\Models\SavingGoalEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    private const DEFAULT_PARTNERS = [
        'Partner 1',
        'Partner 2',
    ];

    public function show()
    {
        return view('plan');
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

            $incomingPartners = $request->input('partners', []);
            $partners = $this->syncPartners($plan, $incomingPartners);

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
                $request,
                $partners
            );

            $this->syncCategoryEntries(
                $plan,
                InvestingCategory::class,
                InvestingEntry::class,
                'investing',
                'investing_category_id',
                $request,
                $partners
            );

            $this->syncCategoryEntries(
                $plan,
                SavingGoalCategory::class,
                SavingGoalEntry::class,
                'savingGoals',
                'saving_goal_category_id',
                $request,
                $partners
            );
        });

        $plan->refresh();

        return response()->json($this->serializePlan($plan));
    }

    public function exportCsv(): StreamedResponse
    {
        $plan = $this->ensurePlan();
        $planData = $this->serializePlan($plan);
        $partners = collect($planData['partners'])->pluck('name')->all();
        $partnerCount = count($partners);

        $filename = 'conscious-spending-plan-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($planData, $partners, $partnerCount) {
            $handle = fopen('php://output', 'w');

            $headers = array_merge(['Section', 'Category'], $partners);
            $headers = array_map(fn ($value) => $this->sanitizeCsvValue($value), $headers);
            fputcsv($handle, $headers);

            $planRows = [
                [
                    'label' => 'Buffer Percent',
                    'values' => [(float) ($planData['plan']['buffer_percent'] ?? 0)],
                ],
                [
                    'label' => 'Currency',
                    'values' => [$planData['plan']['currency'] ?? ''],
                ],
            ];
            $this->writeCsvSection($handle, 'Plan', $planRows, $partnerCount);

            $netWorthRows = [
                [
                    'label' => 'Assets',
                    'values' => array_column($planData['netWorth'], 'assets'),
                ],
                [
                    'label' => 'Invested',
                    'values' => array_column($planData['netWorth'], 'invested'),
                ],
                [
                    'label' => 'Saving',
                    'values' => array_column($planData['netWorth'], 'saving'),
                ],
                [
                    'label' => 'Debt',
                    'values' => array_column($planData['netWorth'], 'debt'),
                ],
            ];
            $this->writeCsvSection($handle, 'Net Worth', $netWorthRows, $partnerCount);

            $incomeRows = [
                [
                    'label' => 'Net Income (Annual)',
                    'values' => array_column($planData['income'], 'net'),
                ],
                [
                    'label' => 'Gross Income (Annual)',
                    'values' => array_column($planData['income'], 'gross'),
                ],
            ];
            $this->writeCsvSection($handle, 'Income', $incomeRows, $partnerCount);

            $expenseRows = collect($planData['expenses'])
                ->map(fn (array $category) => [
                    'label' => $category['label'],
                    'values' => $category['values'],
                ])
                ->all();
            $this->writeCsvSection($handle, 'Expenses', $expenseRows, $partnerCount);

            $investingRows = collect($planData['investing'])
                ->map(fn (array $category) => [
                    'label' => $category['label'],
                    'values' => $category['values'],
                ])
                ->all();
            $this->writeCsvSection($handle, 'Investing', $investingRows, $partnerCount);

            $savingRows = collect($planData['savingGoals'])
                ->map(fn (array $category) => [
                    'label' => $category['label'],
                    'values' => $category['values'],
                ])
                ->all();
            $this->writeCsvSection($handle, 'Saving Goals', $savingRows, $partnerCount);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportPdf(): Response
    {
        $plan = $this->ensurePlan();
        $planData = $this->serializePlan($plan);

        $pdf = Pdf::loadView('exports.plan-pdf', [
            'plan' => $planData['plan'],
            'partners' => $planData['partners'],
            'netWorth' => $planData['netWorth'],
            'income' => $planData['income'],
            'expenses' => $planData['expenses'],
            'investing' => $planData['investing'],
            'savingGoals' => $planData['savingGoals'],
            'exportedAt' => now()->format('M j, Y'),
        ])->setPaper('letter');

        $filename = 'conscious-spending-plan-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function snapshotSummary()
    {
        return view('snapshots');
    }

    public function snapshotSummaryData()
    {
        $plan = $this->ensurePlan();
        $snapshots = $plan->snapshots()->reorder('captured_at')->get();

        $summaries = $snapshots->map(function (PlanSnapshot $snapshot) {
            return $this->summarizeSnapshot($snapshot);
        })->values()->all();

        $latest = empty($summaries) ? null : $summaries[array_key_last($summaries)];

        return response()->json([
            'snapshots' => $summaries,
            'latest' => $latest,
        ]);
    }

    public function snapshots()
    {
        $plan = $this->ensurePlan();

        $snapshots = $plan->snapshots()->get()->map(function (PlanSnapshot $snapshot) {
            return $this->serializeSnapshot($snapshot);
        })->values()->all();

        return response()->json(['snapshots' => $snapshots]);
    }

    public function showSnapshot(PlanSnapshot $snapshot)
    {
        $plan = $this->ensurePlan();

        $snapshot = $plan->snapshots()->whereKey($snapshot->id)->firstOrFail();

        return response()->json([
            'snapshot' => $this->serializeSnapshot($snapshot),
            'data' => $snapshot->payload ?? [],
        ]);
    }

    public function storeSnapshot(StorePlanSnapshotRequest $request)
    {
        $plan = $this->ensurePlan();
        $payload = $this->serializePlan($plan);
        $name = $request->validated()['name'] ?? now()->format('F Y');

        $snapshot = $plan->snapshots()->create([
            'name' => $name,
            'captured_at' => now(),
            'payload' => $payload,
        ]);

        return response()->json([
            'snapshot' => $this->serializeSnapshot($snapshot),
        ], 201);
    }

    private function ensurePlan(): Plan
    {
        $user = request()->user();
        if (! $user) {
            abort(401);
        }

        $plan = $user->plan()->first();

        if (! $plan) {
            $plan = $user->plan()->create(['name' => 'Default Plan']);
        }

        $this->ensureCategories($plan, ExpenseCategory::class, self::DEFAULT_EXPENSES);
        $this->ensureCategories($plan, InvestingCategory::class, self::DEFAULT_INVESTING);
        $this->ensureCategories($plan, SavingGoalCategory::class, self::DEFAULT_SAVING_GOALS);
        $this->ensurePartners($plan);

        return $plan->fresh([
            'partners',
            'netWorths',
            'incomes',
            'expenseCategories.entries',
            'investingCategories.entries',
            'savingGoalCategories.entries',
        ]);
    }

    private function syncPartners(Plan $plan, array $incomingPartners)
    {
        $existingPartners = $plan->partners()->get()->keyBy('id');
        $partners = collect();

        foreach ($incomingPartners as $index => $partnerPayload) {
            $partnerId = Arr::get($partnerPayload, 'id');
            $name = trim((string) Arr::get($partnerPayload, 'name', ''));

            if ($partnerId && $existingPartners->has($partnerId)) {
                $partner = $existingPartners->get($partnerId);
                $partner->update(['name' => $name ?: $partner->name]);
            } else {
                $partner = $plan->partners()->create([
                    'name' => $name ?: 'Partner '.($index + 1),
                ]);
            }

            $partners->push($partner);
        }

        $keepIds = $partners->pluck('id')->all();
        if (! empty($keepIds)) {
            $plan->partners()->whereNotIn('id', $keepIds)->delete();
        } else {
            $plan->partners()->delete();
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

    private function ensurePartners(Plan $plan): void
    {
        if ($plan->partners()->exists()) {
            return;
        }

        foreach (self::DEFAULT_PARTNERS as $partnerName) {
            $plan->partners()->create(['name' => $partnerName]);
        }
    }

    private function syncCategoryEntries(
        Plan $plan,
        string $categoryModel,
        string $entryModel,
        string $payloadKey,
        string $foreignKey,
        Request $request,
        $partners
    ): void {
        $payload = $request->input($payloadKey, []);
        $existingCategories = $categoryModel::where('plan_id', $plan->id)->get()->keyBy('id');
        $keptCategories = collect();

        foreach ($payload as $index => $categoryPayload) {
            $categoryId = Arr::get($categoryPayload, 'id');
            $label = trim((string) Arr::get($categoryPayload, 'label', ''));
            $sort = $index + 1;

            if ($categoryId && $existingCategories->has($categoryId)) {
                $category = $existingCategories->get($categoryId);
                $category->update([
                    'name' => $label ?: $category->name,
                    'sort' => $sort,
                ]);
            } else {
                $category = $categoryModel::create([
                    'plan_id' => $plan->id,
                    'name' => $label ?: 'Category',
                    'sort' => $sort,
                ]);
            }

            $keptCategories->push($category);

            $values = Arr::get($categoryPayload, 'values', []);

            foreach ($partners as $index => $partner) {
                $entryModel::updateOrCreate(
                    [$foreignKey => $category->id, 'partner_id' => $partner->id],
                    ['amount' => (float) Arr::get($values, $index, 0)]
                );
            }
        }

        $keepIds = $keptCategories->pluck('id')->all();
        if (! empty($keepIds)) {
            $categoryModel::where('plan_id', $plan->id)->whereNotIn('id', $keepIds)->delete();
        } else {
            $categoryModel::where('plan_id', $plan->id)->delete();
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

    private function serializeSnapshot(PlanSnapshot $snapshot): array
    {
        return [
            'id' => $snapshot->id,
            'name' => $snapshot->name,
            'captured_at' => optional($snapshot->captured_at)->toIso8601String(),
        ];
    }

    private function summarizeSnapshot(PlanSnapshot $snapshot): array
    {
        $payload = $snapshot->payload ?? [];

        $assets = 0.0;
        $investingTotal = 0.0;
        $savingTotal = 0.0;
        $netWorthTotal = 0.0;

        foreach ($payload['netWorth'] ?? [] as $entry) {
            $assetsValue = (float) ($entry['assets'] ?? 0);
            $investedValue = (float) ($entry['invested'] ?? 0);
            $savingValue = (float) ($entry['saving'] ?? 0);
            $debtValue = (float) ($entry['debt'] ?? 0);

            $assets += $assetsValue;
            $investingTotal += $investedValue;
            $savingTotal += $savingValue;
            $netWorthTotal += $assetsValue + $investedValue + $savingValue - $debtValue;
        }

        $expensesSubtotal = $this->sumCategoryValues($payload['expenses'] ?? []);
        $bufferPercent = (float) ($payload['plan']['buffer_percent'] ?? 0);
        $expenses = $expensesSubtotal + ($expensesSubtotal * $bufferPercent / 100);

        return [
            'id' => $snapshot->id,
            'name' => $snapshot->name,
            'captured_at' => optional($snapshot->captured_at)->toIso8601String(),
            'assets' => $assets,
            'expenses' => $expenses,
            'investing' => $investingTotal,
            'saving' => $savingTotal,
            'net_worth' => $netWorthTotal,
        ];
    }

    /**
     * @param  array<int, array{values?: array<int, float|int|string|null>}>  $categories
     */
    private function sumCategoryValues(array $categories): float
    {
        $total = 0.0;

        foreach ($categories as $category) {
            foreach ($category['values'] ?? [] as $value) {
                $total += (float) $value;
            }
        }

        return $total;
    }

    /**
     * @param  resource  $handle
     * @param  array<int, array{label: string, values: array<int, float|string|null>}>  $rows
     */
    private function writeCsvSection($handle, string $section, array $rows, int $partnerCount): void
    {
        foreach ($rows as $row) {
            $values = array_values($row['values']);
            $values = array_pad($values, $partnerCount, '');
            $values = array_map(fn ($value) => $this->sanitizeCsvValue($value), $values);

            $sectionValue = $this->sanitizeCsvValue($section);
            $labelValue = $this->sanitizeCsvValue($row['label']);

            fputcsv($handle, array_merge([$sectionValue, $labelValue], $values));
        }
    }

    private function sanitizeCsvValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed === '') {
            return $value;
        }

        if (preg_match('/^[=+\-@]/', $trimmed) !== 1) {
            return $value;
        }

        return "'".$value;
    }
}

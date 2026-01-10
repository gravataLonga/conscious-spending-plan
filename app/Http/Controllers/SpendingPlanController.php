<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanSnapshotRequest;
use App\Models\Currency;
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
use App\PlanDefaults;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpendingPlanController extends Controller
{
    public function show()
    {
        return view('plan');
    }

    public function data()
    {
        $plan = $this->ensurePlan();

        return response()->json($this->serializePlanResponse($plan));
    }

    public function store(Request $request)
    {
        $plan = $this->ensurePlan();

        DB::transaction(function () use ($plan, $request) {
            $bufferPercent = $request->input('plan.buffer_percent');
            if ($bufferPercent !== null) {
                $plan->update(['buffer_percent' => (float) $bufferPercent]);
            }

            $currencyCode = $request->input('plan.currency');
            if ($currencyCode !== null) {
                $currencyCode = strtoupper(trim((string) $currencyCode));
                if (Currency::where('code', $currencyCode)->exists()) {
                    $plan->update(['currency' => $currencyCode]);
                }
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

        return response()->json($this->serializePlanResponse($plan));
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
        $snapshots = $plan->snapshots()
            ->with([
                'snapshotPlan.partners',
                'snapshotPlan.netWorths',
                'snapshotPlan.incomes',
                'snapshotPlan.expenseCategories.entries',
                'snapshotPlan.investingCategories.entries',
                'snapshotPlan.savingGoalCategories.entries',
            ])
            ->reorder('captured_at')
            ->get();

        $summaries = $snapshots->map(function (PlanSnapshot $snapshot) {
            return $this->summarizeSnapshot($snapshot);
        })->values()->all();

        $latest = empty($summaries) ? null : $summaries[array_key_last($summaries)];

        return response()->json([
            'snapshots' => $summaries,
            'latest' => $latest,
            'currency' => $plan->currency,
        ]);
    }

    public function snapshots(Request $request)
    {
        $plan = $this->ensurePlan();

        $query = $plan->snapshots();

        if ($request->has('page') || $request->boolean('paginate')) {
            $perPage = (int) $request->input('per_page', 10);
            $perPage = max(1, min($perPage, 50));

            $paginator = $query->paginate($perPage);
            $snapshots = $paginator->getCollection()
                ->map(fn (PlanSnapshot $snapshot) => $this->serializeSnapshot($snapshot))
                ->values()
                ->all();

            return response()->json([
                'snapshots' => $snapshots,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                ],
            ]);
        }

        $snapshots = $query->get()->map(function (PlanSnapshot $snapshot) {
            return $this->serializeSnapshot($snapshot);
        })->values()->all();

        return response()->json(['snapshots' => $snapshots]);
    }

    public function showSnapshot(PlanSnapshot $snapshot)
    {
        $plan = $this->ensurePlan();

        $snapshot = $plan->snapshots()
            ->with([
                'snapshotPlan.partners',
                'snapshotPlan.netWorths',
                'snapshotPlan.incomes',
                'snapshotPlan.expenseCategories.entries',
                'snapshotPlan.investingCategories.entries',
                'snapshotPlan.savingGoalCategories.entries',
            ])
            ->whereKey($snapshot->id)
            ->firstOrFail();

        $snapshotPlan = $snapshot->snapshotPlan;
        $data = $snapshotPlan ? $this->serializePlan($snapshotPlan) : ($snapshot->payload ?? []);

        return response()->json([
            'snapshot' => $this->serializeSnapshot($snapshot),
            'data' => $data,
        ]);
    }

    public function storeSnapshot(StorePlanSnapshotRequest $request)
    {
        $plan = $this->ensurePlan();
        $validated = $request->validated();
        $name = $validated['name'] ?? now()->format('F Y');
        $note = $validated['note'] ?? null;

        $snapshot = DB::transaction(function () use ($plan, $name, $note) {
            $payload = $this->serializePlan($plan);
            $totals = $this->calculateSnapshotTotals($payload);

            $snapshotPlan = $this->clonePlanForSnapshot($plan);

            $snapshotPayload = [
                'name' => $name,
                'captured_at' => now(),
                'snapshot_plan_id' => $snapshotPlan->id,
                'total_net_worth' => $totals['net_worth'],
                'net_income' => $totals['net_income'],
                'total_expenses' => $totals['expenses'],
                'total_saving' => $totals['saving'],
                'total_investing' => $totals['investing'],
                'guilt_free' => $totals['guilt_free'],
                'payload' => [],
            ];

            if (Schema::hasColumn('plan_snapshots', 'note')) {
                $snapshotPayload['note'] = $note;
            }

            return $plan->snapshots()->create($snapshotPayload);
        });

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

        $this->ensureCategories($plan, ExpenseCategory::class, PlanDefaults::Expenses->labels());
        $this->ensureCategories($plan, InvestingCategory::class, PlanDefaults::Investing->labels());
        $this->ensureCategories($plan, SavingGoalCategory::class, PlanDefaults::SavingGoals->labels());
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

        foreach (PlanDefaults::Partners->labels() as $partnerName) {
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

    /**
     * @return array{plan: array{id: int, buffer_percent: float, currency: string|null}, partners: array<int, array{id: int, name: string}>, netWorth: array<int, array{assets: float, invested: float, saving: float, debt: float}>, income: array<int, array{net: float, gross: float}>, expenses: array<int, array{id: int, label: string, values: array<int, float>}>, investing: array<int, array{id: int, label: string, values: array<int, float>}>, savingGoals: array<int, array{id: int, label: string, values: array<int, float>}>, currencies: array<int, array{code: string, name: string, symbol: string}>}
     */
    private function serializePlanResponse(Plan $plan): array
    {
        return array_merge($this->serializePlan($plan), [
            'currencies' => Currency::query()
                ->orderBy('name')
                ->get(['code', 'name', 'symbol'])
                ->map(fn (Currency $currency) => [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                ])
                ->values()
                ->all(),
        ]);
    }

    private function serializeSnapshot(PlanSnapshot $snapshot): array
    {
        return [
            'id' => $snapshot->id,
            'name' => $snapshot->name,
            'note' => $snapshot->note,
            'captured_at' => optional($snapshot->captured_at)->toIso8601String(),
            'total_net_worth' => $snapshot->total_net_worth !== null ? (float) $snapshot->total_net_worth : null,
            'net_income' => $snapshot->net_income !== null ? (float) $snapshot->net_income : null,
            'total_expenses' => $snapshot->total_expenses !== null ? (float) $snapshot->total_expenses : null,
            'total_saving' => $snapshot->total_saving !== null ? (float) $snapshot->total_saving : null,
            'total_investing' => $snapshot->total_investing !== null ? (float) $snapshot->total_investing : null,
            'guilt_free' => $snapshot->guilt_free !== null ? (float) $snapshot->guilt_free : null,
        ];
    }

    private function summarizeSnapshot(PlanSnapshot $snapshot): array
    {
        $payload = $snapshot->snapshotPlan
            ? $this->serializePlan($snapshot->snapshotPlan)
            : ($snapshot->payload ?? []);

        $assets = 0.0;

        foreach ($payload['netWorth'] ?? [] as $entry) {
            $assetsValue = (float) ($entry['assets'] ?? 0);
            $assets += $assetsValue;
        }

        $totals = $this->calculateSnapshotTotals($payload);
        $netWorthTotal = $snapshot->total_net_worth !== null
            ? (float) $snapshot->total_net_worth
            : $totals['net_worth'];
        $netIncome = $snapshot->net_income !== null
            ? (float) $snapshot->net_income
            : $totals['net_income'];
        $expenses = $snapshot->total_expenses !== null
            ? (float) $snapshot->total_expenses
            : $totals['expenses'];
        $savingTotal = $snapshot->total_saving !== null
            ? (float) $snapshot->total_saving
            : $totals['saving'];
        $investingTotal = $snapshot->total_investing !== null
            ? (float) $snapshot->total_investing
            : $totals['investing'];
        $guiltFree = $snapshot->guilt_free !== null
            ? (float) $snapshot->guilt_free
            : $totals['guilt_free'];

        return [
            'id' => $snapshot->id,
            'name' => $snapshot->name,
            'captured_at' => optional($snapshot->captured_at)->toIso8601String(),
            'assets' => $assets,
            'expenses' => $expenses,
            'investing' => $investingTotal,
            'saving' => $savingTotal,
            'net_worth' => $netWorthTotal,
            'net_income' => $netIncome,
            'guilt_free' => $guiltFree,
        ];
    }

    /**
     * @param  array{plan?: array{buffer_percent?: float|int|string}, netWorth?: array<int, array{assets?: float|int|string, invested?: float|int|string, saving?: float|int|string, debt?: float|int|string}>, income?: array<int, array{net?: float|int|string}>, expenses?: array<int, array{values?: array<int, float|int|string|null>}>, investing?: array<int, array{values?: array<int, float|int|string|null>}>, savingGoals?: array<int, array{values?: array<int, float|int|string|null>}>}  $payload
     * @return array{net_worth: float, net_income: float, expenses: float, saving: float, investing: float, guilt_free: float}
     */
    private function calculateSnapshotTotals(array $payload): array
    {
        $netWorthTotal = 0.0;
        $netIncome = 0.0;

        foreach ($payload['netWorth'] ?? [] as $entry) {
            $assetsValue = (float) ($entry['assets'] ?? 0);
            $investedValue = (float) ($entry['invested'] ?? 0);
            $savingValue = (float) ($entry['saving'] ?? 0);
            $debtValue = (float) ($entry['debt'] ?? 0);

            $netWorthTotal += $assetsValue + $investedValue + $savingValue - $debtValue;
        }

        foreach ($payload['income'] ?? [] as $entry) {
            $netIncome += (float) ($entry['net'] ?? 0);
        }

        $expensesSubtotal = $this->sumCategoryValues($payload['expenses'] ?? []);
        $bufferPercent = (float) ($payload['plan']['buffer_percent'] ?? 0);
        $expenses = $expensesSubtotal + ($expensesSubtotal * $bufferPercent / 100);

        $investingTotal = $this->sumCategoryValues($payload['investing'] ?? []);
        $savingTotal = $this->sumCategoryValues($payload['savingGoals'] ?? []);

        $guiltFree = $netIncome - $expenses - $investingTotal - $savingTotal;

        return [
            'net_worth' => $netWorthTotal,
            'net_income' => $netIncome,
            'expenses' => $expenses,
            'saving' => $savingTotal,
            'investing' => $investingTotal,
            'guilt_free' => $guiltFree,
        ];
    }

    private function clonePlanForSnapshot(Plan $plan): Plan
    {
        $plan->loadMissing([
            'partners',
            'netWorths',
            'incomes',
            'expenseCategories.entries',
            'investingCategories.entries',
            'savingGoalCategories.entries',
        ]);

        $snapshotPlan = $plan->replicate();
        $snapshotPlan->is_snapshot = true;
        $snapshotPlan->save();

        $partnerMap = [];

        foreach ($plan->partners as $partner) {
            $newPartner = $partner->replicate();
            $newPartner->plan_id = $snapshotPlan->id;
            $newPartner->save();

            $partnerMap[$partner->id] = $newPartner->id;
        }

        foreach ($plan->netWorths as $netWorth) {
            $newNetWorth = $netWorth->replicate();
            $newNetWorth->plan_id = $snapshotPlan->id;
            $newNetWorth->partner_id = $partnerMap[$netWorth->partner_id] ?? $netWorth->partner_id;
            $newNetWorth->save();
        }

        foreach ($plan->incomes as $income) {
            $newIncome = $income->replicate();
            $newIncome->plan_id = $snapshotPlan->id;
            $newIncome->partner_id = $partnerMap[$income->partner_id] ?? $income->partner_id;
            $newIncome->save();
        }

        $this->cloneCategoriesWithEntries(
            $snapshotPlan,
            $plan->expenseCategories,
            $partnerMap,
            'expense_category_id'
        );

        $this->cloneCategoriesWithEntries(
            $snapshotPlan,
            $plan->investingCategories,
            $partnerMap,
            'investing_category_id'
        );

        $this->cloneCategoriesWithEntries(
            $snapshotPlan,
            $plan->savingGoalCategories,
            $partnerMap,
            'saving_goal_category_id'
        );

        return $snapshotPlan;
    }

    private function cloneCategoriesWithEntries(
        Plan $snapshotPlan,
        iterable $categories,
        array $partnerMap,
        string $foreignKey
    ): void {
        foreach ($categories as $category) {
            $newCategory = $category->replicate();
            $newCategory->plan_id = $snapshotPlan->id;
            $newCategory->save();

            foreach ($category->entries as $entry) {
                $newEntry = $entry->replicate();
                $newEntry->{$foreignKey} = $newCategory->id;
                $newEntry->partner_id = $partnerMap[$entry->partner_id] ?? $entry->partner_id;
                $newEntry->save();
            }
        }
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

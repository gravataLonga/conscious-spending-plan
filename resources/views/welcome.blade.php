<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Conscious Spending Plan</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&family=fraunces:600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <div class="relative overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                <div class="absolute -top-32 right-[-10%] h-72 w-72 rounded-xl bg-[radial-gradient(circle,rgba(59,130,246,0.18)_0%,rgba(59,130,246,0)_70%)] blur-2xl"></div>
                <div class="absolute left-[-15%] top-40 h-80 w-80 rounded-xl bg-[radial-gradient(circle,rgba(15,23,42,0.14)_0%,rgba(15,23,42,0)_72%)] blur-2xl"></div>
                <div class="absolute bottom-[-18%] right-10 h-72 w-72 rounded-xl bg-[radial-gradient(circle,rgba(148,163,184,0.18)_0%,rgba(148,163,184,0)_70%)] blur-2xl"></div>
            </div>

            <main
                class="relative mx-auto flex max-w-6xl flex-col gap-10 px-4 py-10 md:py-16"
                x-data="cspPlan()"
                x-cloak
            >
                <header class="space-y-6 animate-[rise_0.8s_ease-out]">
                    <div class="inline-flex items-center gap-2 rounded-md border border-slate-200/80 bg-white/80 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700">
                        Conscious Spending Plan
                        <span class="inline-flex h-1.5 w-1.5 rounded-sm bg-slate-500"></span>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-[1.25fr,0.9fr]">
                        <div>
                            <h1 class="text-4xl font-semibold leading-tight text-slate-950 md:text-5xl lg:text-6xl font-['Fraunces',serif]">
                                Make your money plan obvious, calm, and shared.
                            </h1>
                            <p class="mt-4 text-base text-slate-700 md:text-lg">
                                Track income, expenses, investing, and savings side by side. This layout is built for two partners and keeps guilt-free spending visible without digging through spreadsheets.
                            </p>
                            <div class="mt-6 flex flex-wrap items-center gap-4">
                                <button
                                    class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                                    type="button"
                                    @click="savePlan"
                                    :disabled="saving"
                                >
                                    <span x-text="saving ? 'Saving...' : 'Save Plan'"></span>
                                </button>
                                <span class="text-sm text-slate-500" x-text="saveNotice" x-show="saveNotice"></span>
                                <span class="text-sm text-slate-500" x-show="loading">Loading data...</span>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200/70 bg-white/80 p-5 shadow-sm">
                            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Partner Labels</h2>
                            <div class="mt-4 grid gap-4">
                                <label class="text-sm font-medium text-slate-700">
                                    Partner 1
                                    <input
                                        class="mt-2 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-base focus:border-slate-400 focus:outline-none"
                                        type="text"
                                        x-model="partners[0].name"
                                        placeholder="Partner 1"
                                    />
                                </label>
                                <label class="text-sm font-medium text-slate-700">
                                    Partner 2
                                    <input
                                        class="mt-2 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-base focus:border-slate-400 focus:outline-none"
                                        type="text"
                                        x-model="partners[1].name"
                                        placeholder="Partner 2"
                                    />
                                </label>
                            </div>
                        </div>
                    </div>
                </header>

                <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200/70 bg-white/80 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Net Income</p>
                        <p class="mt-3 text-2xl font-semibold text-slate-900" x-text="formatCurrency(totalIncomeNet())"></p>
                        <p class="mt-1 text-xs text-slate-500" x-text="formatCurrency(totalIncomeGross()) + ' gross'"></p>
                    </div>
                    <div class="rounded-lg border border-slate-200/70 bg-white/80 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Expenses</p>
                        <p class="mt-3 text-2xl font-semibold text-slate-900" x-text="formatCurrency(totalExpenses())"></p>
                        <p class="mt-1 text-xs text-slate-500" x-text="formatPercent(shareOfIncome(totalExpenses(), totalIncomeNet())) + ' of net income'"
                        ></p>
                    </div>
                    <div class="rounded-lg border border-slate-200/70 bg-white/80 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Investing + Saving</p>
                        <p
                            class="mt-3 text-2xl font-semibold text-slate-900"
                            x-text="formatCurrency(totalInvesting() + totalSavingGoals())"
                        ></p>
                        <p
                            class="mt-1 text-xs text-slate-500"
                            x-text="formatPercent(shareOfIncome(totalInvesting() + totalSavingGoals(), totalIncomeNet())) + ' of net income'"
                        ></p>
                    </div>
                    <div class="rounded-lg border border-slate-200/70 bg-white/80 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Guilt-Free</p>
                        <p class="mt-3 text-2xl font-semibold text-slate-900" x-text="formatCurrency(guiltyFreeSpending())"></p>
                        <p class="mt-1 text-xs text-slate-500">Available after goals.</p>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Net Worth Snapshot</h2>
                            <p class="text-sm text-slate-500">Assets + Invested + Saving - Debt</p>
                        </div>
                        <div class="flex gap-6 text-sm font-semibold text-slate-700">
                            <span x-text="partnerName(0) + ': ' + formatCurrency(netWorthTotal(0))"></span>
                            <span x-text="partnerName(1) + ': ' + formatCurrency(netWorthTotal(1))"></span>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-3">
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Category</div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(0)"></div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(1)"></div>
                        </div>
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-medium text-slate-800">Assets</div>
                            <input class="input-field" type="number" step="0.01" x-model.number="netWorth.assets[0]" />
                            <input class="input-field" type="number" step="0.01" x-model.number="netWorth.assets[1]" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-medium text-slate-800">Invested</div>
                            <input class="input-field" type="number" step="0.01" x-model.number="netWorth.invested[0]" />
                            <input class="input-field" type="number" step="0.01" x-model.number="netWorth.invested[1]" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-medium text-slate-800">Saving</div>
                            <input class="input-field" type="number" step="0.01" x-model.number="netWorth.saving[0]" />
                            <input class="input-field" type="number" step="0.01" x-model.number="netWorth.saving[1]" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-medium text-slate-800">Debt</div>
                            <input class="input-field" type="number" step="0.01" x-model.number="netWorth.debt[0]" />
                            <input class="input-field" type="number" step="0.01" x-model.number="netWorth.debt[1]" />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Income</h2>
                            <p class="text-sm text-slate-500">Use net income for plan calculations, and track gross for visibility.</p>
                        </div>
                        <div class="flex gap-6 text-sm font-semibold text-slate-700">
                            <span x-text="'Net: ' + formatCurrency(totalIncomeNet())"></span>
                            <span x-text="'Gross: ' + formatCurrency(totalIncomeGross())"></span>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-3">
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Category</div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(0)"></div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(1)"></div>
                        </div>
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-medium text-slate-800">Net Income (Annual)</div>
                            <input class="input-field" type="number" step="0.01" x-model.number="income.net[0]" />
                            <input class="input-field" type="number" step="0.01" x-model.number="income.net[1]" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-medium text-slate-800">Gross Income (Annual)</div>
                            <input class="input-field" type="number" step="0.01" x-model.number="income.gross[0]" />
                            <input class="input-field" type="number" step="0.01" x-model.number="income.gross[1]" />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Total Expenses</h2>
                            <p class="text-sm text-slate-500">Buffer adds a percentage above expenses to keep space for surprises.</p>
                        </div>
                        <div class="flex flex-col gap-2 text-sm font-semibold text-slate-700">
                            <span x-text="formatCurrency(totalExpenses())"></span>
                            <span class="text-xs text-slate-500" x-text="formatPercent(shareOfIncome(totalExpenses(), totalIncomeNet())) + ' of net income'"
                            ></span>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-3 rounded-lg border border-slate-200/80 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <span class="font-semibold">Buffer</span>
                        <label class="flex items-center gap-2">
                            <input class="h-8 w-20 rounded-md border border-slate-200 bg-white px-2" type="number" step="1" x-model.number="bufferPercent" />
                            <span>% of subtotal</span>
                        </label>
                        <span class="text-xs text-slate-500" x-text="'Adds ' + formatCurrency(expensesBuffer()) + ' total'"
                        ></span>
                    </div>
                    <div class="mt-6 grid gap-3">
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Category</div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(0)"></div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(1)"></div>
                        </div>
                        <template x-for="expense in expenses" :key="expense.label">
                            <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                                <div class="text-sm font-medium text-slate-800" x-text="expense.label"></div>
                                <input class="input-field" type="number" step="0.01" x-model.number="expense.values[0]" />
                                <input class="input-field" type="number" step="0.01" x-model.number="expense.values[1]" />
                            </div>
                        </template>
                        <div class="grid gap-3 border-t border-slate-200/70 pt-4 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-semibold text-slate-900">Subtotal + Buffer</div>
                            <div class="text-sm font-semibold text-slate-900" x-text="formatCurrency(totalExpenses(0))"></div>
                            <div class="text-sm font-semibold text-slate-900" x-text="formatCurrency(totalExpenses(1))"></div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Investing</h2>
                            <p class="text-sm text-slate-500">Percent of net income shown for focus.</p>
                        </div>
                        <div class="flex flex-col gap-2 text-sm font-semibold text-slate-700">
                            <span x-text="formatCurrency(totalInvesting())"></span>
                            <span class="text-xs text-slate-500" x-text="formatPercent(shareOfIncome(totalInvesting(), totalIncomeNet())) + ' of net income'"
                            ></span>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-3">
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Category</div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(0)"></div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(1)"></div>
                        </div>
                        <template x-for="item in investing" :key="item.label">
                            <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                                <div class="text-sm font-medium text-slate-800" x-text="item.label"></div>
                                <input class="input-field" type="number" step="0.01" x-model.number="item.values[0]" />
                                <input class="input-field" type="number" step="0.01" x-model.number="item.values[1]" />
                            </div>
                        </template>
                        <div class="grid gap-3 border-t border-slate-200/70 pt-4 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-semibold text-slate-900">Total Investing</div>
                            <div class="text-sm font-semibold text-slate-900" x-text="formatCurrency(totalInvesting(0))"></div>
                            <div class="text-sm font-semibold text-slate-900" x-text="formatCurrency(totalInvesting(1))"></div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Saving Goals</h2>
                            <p class="text-sm text-slate-500">Keep intentional goals in sight.</p>
                        </div>
                        <div class="flex flex-col gap-2 text-sm font-semibold text-slate-700">
                            <span x-text="formatCurrency(totalSavingGoals())"></span>
                            <span class="text-xs text-slate-500" x-text="formatPercent(shareOfIncome(totalSavingGoals(), totalIncomeNet())) + ' of net income'"
                            ></span>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-3">
                        <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Category</div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(0)"></div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" x-text="partnerName(1)"></div>
                        </div>
                        <template x-for="item in savingGoals" :key="item.label">
                            <div class="grid gap-3 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                                <div class="text-sm font-medium text-slate-800" x-text="item.label"></div>
                                <input class="input-field" type="number" step="0.01" x-model.number="item.values[0]" />
                                <input class="input-field" type="number" step="0.01" x-model.number="item.values[1]" />
                            </div>
                        </template>
                        <div class="grid gap-3 border-t border-slate-200/70 pt-4 md:grid-cols-[minmax(240px,1fr)_minmax(160px,0.7fr)_minmax(160px,0.7fr)]">
                            <div class="text-sm font-semibold text-slate-900">Total Saving Goals</div>
                            <div class="text-sm font-semibold text-slate-900" x-text="formatCurrency(totalSavingGoals(0))"></div>
                            <div class="text-sm font-semibold text-slate-900" x-text="formatCurrency(totalSavingGoals(1))"></div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Guilt-Free Spending</h2>
                            <p class="text-sm text-slate-500">What remains after essentials, investing, and savings.</p>
                        </div>
                        <div class="flex gap-6 text-sm font-semibold text-slate-700">
                            <span x-text="partnerName(0) + ': ' + formatCurrency(guiltyFreeSpending(0))"></span>
                            <span x-text="partnerName(1) + ': ' + formatCurrency(guiltyFreeSpending(1))"></span>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-3 rounded-lg border border-slate-200/70 bg-slate-50 px-4 py-5 text-sm text-slate-600">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span>Total guilt-free spending (combined)</span>
                            <span class="text-base font-semibold text-slate-900" x-text="formatCurrency(guiltyFreeSpending())"></span>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>

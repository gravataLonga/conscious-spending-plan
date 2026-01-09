@extends('layouts.app')

@section('title', 'Snapshot Overview')

@section('content')
    <div x-data="cspSnapshots()" x-cloak>


        <section class="space-y-6 animate-[rise_0.8s_ease-out]">
            <div class="inline-flex items-center gap-2 rounded-md border border-slate-200/80 bg-white/80 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700">
                Snapshot Overview
                <span class="inline-flex h-1.5 w-1.5 rounded-sm bg-slate-500"></span>
            </div>
            <div class="grid gap-6 lg:grid-cols-[1.25fr,0.9fr]">
                <div>
                    <h1 class="text-4xl font-semibold leading-tight text-slate-950 md:text-5xl lg:text-6xl font-['Fraunces',serif]">
                        Track how the plan shifts month to month.
                    </h1>
                    <p class="mt-4 text-base text-slate-700 md:text-lg">
                        Review snapshots over time and compare the four core numbers without editing the live plan.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                    <template x-if="latestSnapshot()">
                        <div class="grid gap-4">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Overview</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Total Net Worth</h2>
                                </div>
                                <div class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em]" :class="netWorthTrendBadgeClass()">
                                    <span x-show="netWorthDelta() !== null" x-text="formatNetWorthDelta()"></span>
                                    <span x-show="netWorthDelta() === null">--</span>
                                </div>
                            </div>
                            <div class="flex w-full flex-col items-center justify-center text-center">
                                <div class="flex flex-wrap items-center justify-center gap-4">
                                    <p class="text-5xl font-semibold text-slate-950 md:text-6xl lg:text-7xl" x-text="currentNetWorth === null ? '--' : formatCurrency(currentNetWorth)"></p>
                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full border text-sm" :class="netWorthTrendBadgeClass()">
                                    <x-heroicon-o-arrow-trending-up class="h-5 w-5" x-show="netWorthTrend() > 0" />
                                    <x-heroicon-o-arrow-trending-down class="h-5 w-5" x-show="netWorthTrend() < 0" />
                                    <x-heroicon-o-minus class="h-5 w-5" x-show="netWorthTrend() === 0" />
                                </span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">
                                    <span x-text="latestSnapshot().name || 'Snapshot'"></span>
                                    <span class="text-slate-400">•</span>
                                    <span x-text="formatDate(latestSnapshot().captured_at)"></span>
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-600" x-show="currentNetWorth !== null && previousSnapshot()">
                                    Previous snapshot net worth:
                                    <span class="text-slate-700" x-text="formatCurrency(previousSnapshotNetWorth())"></span>
                                    <span class="text-slate-400">•</span>
                                    <span x-text="previousSnapshot().name || 'Snapshot'"></span>
                                </p>
                                <p class="mt-2 text-sm text-slate-400" x-show="currentNetWorth !== null && !previousSnapshot()">No previous snapshot to compare yet.</p>
                            </div>
                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                <div class="rounded-xl border border-slate-200/70 bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Assets</p>
                                            <p class="mt-2 text-2xl font-semibold" :class="trendClass('assets')" x-text="formatCurrency(latestSnapshot().assets)"></p>
                                        </div>
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border text-sm" :class="trendBadgeClass('assets')">
                                        <x-heroicon-o-arrow-trending-up class="h-4 w-4" x-show="metricTrend('assets') > 0" />
                                        <x-heroicon-o-arrow-trending-down class="h-4 w-4" x-show="metricTrend('assets') < 0" />
                                        <x-heroicon-o-minus class="h-4 w-4" x-show="metricTrend('assets') === 0" />
                                    </span>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                        <span x-text="metricDelta('assets') === null ? 'No previous snapshot' : `vs previous ${formatDelta('assets')}`"></span>
                                    </p>
                                </div>
                                <div class="rounded-xl border border-slate-200/70 bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Expenses</p>
                                            <p class="mt-2 text-2xl font-semibold" :class="trendClass('expenses')" x-text="formatCurrency(latestSnapshot().expenses)"></p>
                                        </div>
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border text-sm" :class="trendBadgeClass('expenses')">
                                        <x-heroicon-o-arrow-trending-down class="h-4 w-4" x-show="metricTrend('expenses') > 0" />
                                        <x-heroicon-o-arrow-trending-up class="h-4 w-4" x-show="metricTrend('expenses') < 0" />
                                        <x-heroicon-o-minus class="h-4 w-4" x-show="metricTrend('expenses') === 0" />
                                    </span>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                        <span x-text="metricDelta('expenses') === null ? 'No previous snapshot' : `vs previous ${formatDelta('expenses')}`"></span>
                                    </p>
                                </div>
                                <div class="rounded-xl border border-slate-200/70 bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Investing</p>
                                            <p class="mt-2 text-2xl font-semibold" :class="trendClass('investing')" x-text="formatCurrency(latestSnapshot().investing)"></p>
                                        </div>
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border text-sm" :class="trendBadgeClass('investing')">
                                        <x-heroicon-o-arrow-trending-up class="h-4 w-4" x-show="metricTrend('investing') > 0" />
                                        <x-heroicon-o-arrow-trending-down class="h-4 w-4" x-show="metricTrend('investing') < 0" />
                                        <x-heroicon-o-minus class="h-4 w-4" x-show="metricTrend('investing') === 0" />
                                    </span>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                        <span x-text="metricDelta('investing') === null ? 'No previous snapshot' : `vs previous ${formatDelta('investing')}`"></span>
                                    </p>
                                </div>
                                <div class="rounded-xl border border-slate-200/70 bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Saving</p>
                                            <p class="mt-2 text-2xl font-semibold" :class="trendClass('saving')" x-text="formatCurrency(latestSnapshot().saving)"></p>
                                        </div>
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border text-sm" :class="trendBadgeClass('saving')">
                                        <x-heroicon-o-arrow-trending-up class="h-4 w-4" x-show="metricTrend('saving') > 0" />
                                        <x-heroicon-o-arrow-trending-down class="h-4 w-4" x-show="metricTrend('saving') < 0" />
                                        <x-heroicon-o-minus class="h-4 w-4" x-show="metricTrend('saving') === 0" />
                                    </span>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                        <span x-text="metricDelta('saving') === null ? 'No previous snapshot' : `vs previous ${formatDelta('saving')}`"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                    <p class="mt-4 text-sm text-slate-500" x-show="!latestSnapshot() && !loading">No snapshots yet. Create one from the plan.</p>
                    <p class="mt-4 text-sm text-slate-500" x-show="loading">Loading snapshot data...</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Date Range</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Filter snapshot history</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Narrow the summary and chart to a specific timeframe.
                    </p>
                </div>
                <div class="flex flex-wrap items-end gap-4">
                    <label class="grid gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" for="snapshot-range-start">
                        Start date
                        <input
                            id="snapshot-range-start"
                            name="range_start"
                            type="date"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none"
                            x-model="rangeStart"
                            :min="allSnapshots[0]?.captured_at?.split('T')[0] ?? ''"
                            :max="rangeEnd || (allSnapshots[allSnapshots.length - 1]?.captured_at?.split('T')[0] ?? '')"
                            @change="applyFilters()"
                        />
                    </label>
                    <label class="grid gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500" for="snapshot-range-end">
                        End date
                        <input
                            id="snapshot-range-end"
                            name="range_end"
                            type="date"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none"
                            x-model="rangeEnd"
                            :min="rangeStart || (allSnapshots[0]?.captured_at?.split('T')[0] ?? '')"
                            :max="allSnapshots[allSnapshots.length - 1]?.captured_at?.split('T')[0] ?? ''"
                            @change="applyFilters()"
                        />
                    </label>
                    <button
                        class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                        type="button"
                        @click="resetRange()"
                    >
                        All time
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200/70 bg-white/80 p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Snapshot Trends</h2>
                    <p class="text-sm text-slate-500">Each line tracks totals captured at each snapshot.</p>
                </div>
                <div class="flex flex-wrap gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-900"></span>Assets</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-500"></span>Expenses</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Investing</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Saving</span>
                </div>
            </div>
            <div class="mt-6">
                <div class="rounded-lg border border-slate-200/70 bg-white p-4" x-show="snapshots.length">
                    <div class="h-56 w-full" x-ref="chart"></div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                        <span x-text="`Showing ${snapshots.length} snapshot${snapshots.length === 1 ? '' : 's'}`"></span>
                        <span x-show="snapshots.length">
                        <span x-text="formatDate(snapshots[0]?.captured_at)"></span>
                        <span class="text-slate-400">→</span>
                        <span x-text="formatDate(snapshots[snapshots.length - 1]?.captured_at)"></span>
                    </span>
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-500" x-show="!snapshots.length && !loading">No snapshot trend data yet.</p>
                <p class="mt-4 text-sm text-slate-500" x-show="loading">Loading trend data...</p>
            </div>
        </section>
    </div>

@endsection

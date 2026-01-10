import './bootstrap';
import Alpine from 'alpinejs';
import { createChart, LineSeries } from 'lightweight-charts';

window.Alpine = Alpine;

window.cspPlan = function () {
    return {
        loading: true,
        saving: false,
        snapshotSaving: false,
        saveNotice: '',
        snapshotNotice: '',
        currencyCode: 'USD',
        currencies: [],
        snapshotModalOpen: false,
        snapshotNote: '',
        partners: [
            { id: null, name: 'Partner 1' },
            { id: null, name: 'Partner 2' },
        ],
        netWorth: {
            assets: [0, 0],
            invested: [0, 0],
            saving: [0, 0],
            debt: [0, 0],
        },
        income: {
            net: [0, 0],
            gross: [0, 0],
        },
        expenses: [
            { id: null, label: 'Rent or Mortgage', values: [0, 0] },
            { id: null, label: 'Utilities', values: [0, 0] },
            { id: null, label: 'Insurance', values: [0, 0] },
            { id: null, label: 'Car Payment', values: [0, 0] },
            { id: null, label: 'Groceries', values: [0, 0] },
            { id: null, label: 'Clothes', values: [0, 0] },
            { id: null, label: 'Phone', values: [0, 0] },
            { id: null, label: 'Subscriptions', values: [0, 0] },
            { id: null, label: 'Debt', values: [0, 0] },
        ],
        bufferPercent: 15,
        investing: [
            { id: null, label: 'Post-tax Retirement Saving', values: [0, 0] },
            { id: null, label: 'ETF', values: [0, 0] },
            { id: null, label: 'Other', values: [0, 0] },
        ],
        savingGoals: [
            { id: null, label: 'Vacation', values: [0, 0] },
            { id: null, label: 'Gifts', values: [0, 0] },
            { id: null, label: 'Long Term Emergency Fund', values: [0, 0] },
        ],
        init() {
            this.fetchPlan();
        },
        partnerName(index) {
            return this.partners[index]?.name || `Partner ${index + 1}`;
        },
        partnerKey(partner, index) {
            return partner?.id ?? `new-${index}`;
        },
        categoryKey(item, index) {
            return item?.id ?? `new-${index}`;
        },
        partnerColumnsStyle() {
            return { '--partner-count': Math.max(this.partners.length, 1) };
        },
        emptyValues(count) {
            return Array.from({ length: count }, () => 0);
        },
        sanitizeLabel(label, fallback = 'Category') {
            const trimmed = String(label ?? '').trim();
            return trimmed.length ? trimmed : fallback;
        },
        createCategory(label) {
            return {
                id: null,
                label: this.sanitizeLabel(label),
                values: this.emptyValues(this.partners.length),
            };
        },
        addPartner() {
            const nextIndex = this.partners.length;
            this.partners.push({ id: null, name: `Partner ${nextIndex + 1}` });
            this.netWorth.assets.push(0);
            this.netWorth.invested.push(0);
            this.netWorth.saving.push(0);
            this.netWorth.debt.push(0);
            this.income.net.push(0);
            this.income.gross.push(0);
            this.expenses.forEach((item) => item.values.push(0));
            this.investing.forEach((item) => item.values.push(0));
            this.savingGoals.forEach((item) => item.values.push(0));
        },
        removePartner(index) {
            if (this.partners.length <= 1) {
                return;
            }

            this.partners.splice(index, 1);
            this.netWorth.assets.splice(index, 1);
            this.netWorth.invested.splice(index, 1);
            this.netWorth.saving.splice(index, 1);
            this.netWorth.debt.splice(index, 1);
            this.income.net.splice(index, 1);
            this.income.gross.splice(index, 1);
            this.expenses.forEach((item) => item.values.splice(index, 1));
            this.investing.forEach((item) => item.values.splice(index, 1));
            this.savingGoals.forEach((item) => item.values.splice(index, 1));
        },
        addExpense() {
            this.expenses.push(this.createCategory('New Expense'));
        },
        removeExpense(index) {
            this.expenses.splice(index, 1);
        },
        addInvesting() {
            this.investing.push(this.createCategory('New Investing'));
        },
        removeInvesting(index) {
            this.investing.splice(index, 1);
        },
        addSavingGoal() {
            this.savingGoals.push(this.createCategory('New Goal'));
        },
        removeSavingGoal(index) {
            this.savingGoals.splice(index, 1);
        },
        openSnapshotModal() {
            this.snapshotModalOpen = true;
        },
        closeSnapshotModal() {
            this.snapshotModalOpen = false;
        },
        async submitSnapshot() {
            this.snapshotModalOpen = false;
            await this.createSnapshot();
        },
        toNumber(value) {
            return Number.isFinite(value) ? value : Number.parseFloat(value) || 0;
        },
        sumValues(values) {
            return values.reduce((total, value) => total + this.toNumber(value), 0);
        },
        sumItems(items, index) {
            return items.reduce((total, item) => total + this.toNumber(item.values?.[index] ?? 0), 0);
        },
        formatCurrency(value) {
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: this.currencyCode || 'USD',
                maximumFractionDigits: 0,
            });

            return formatter.format(Math.round(value));
        },
        formatPercent(value) {
            if (!Number.isFinite(value)) {
                return '0%';
            }

            return `${Math.round(value)}%`;
        },
        netWorthTotal(index) {
            const assets = this.toNumber(this.netWorth.assets[index]);
            const invested = this.toNumber(this.netWorth.invested[index]);
            const saving = this.toNumber(this.netWorth.saving[index]);
            const debt = this.toNumber(this.netWorth.debt[index]);

            return assets + invested + saving - debt;
        },
        netIncome(index) {
            return this.toNumber(this.income.net[index]);
        },
        grossIncome(index) {
            return this.toNumber(this.income.gross[index]);
        },
        totalIncomeNet(index) {
            if (index === undefined) {
                return this.sumValues(this.income.net);
            }

            return this.netIncome(index);
        },
        totalIncomeGross(index) {
            if (index === undefined) {
                return this.sumValues(this.income.gross);
            }

            return this.grossIncome(index);
        },
        expensesSubtotal(index) {
            if (index === undefined) {
                return this.partners.reduce((total, _partner, partnerIndex) => total + this.sumItems(this.expenses, partnerIndex), 0);
            }

            return this.sumItems(this.expenses, index);
        },
        expensesBuffer(index) {
            const base = this.expensesSubtotal(index);
            return (base * this.toNumber(this.bufferPercent)) / 100;
        },
        totalExpenses(index) {
            return this.expensesSubtotal(index) + this.expensesBuffer(index);
        },
        totalInvesting(index) {
            if (index === undefined) {
                return this.partners.reduce((total, _partner, partnerIndex) => total + this.sumItems(this.investing, partnerIndex), 0);
            }

            return this.sumItems(this.investing, index);
        },
        totalSavingGoals(index) {
            if (index === undefined) {
                return this.partners.reduce((total, _partner, partnerIndex) => total + this.sumItems(this.savingGoals, partnerIndex), 0);
            }

            return this.sumItems(this.savingGoals, index);
        },
        guiltyFreeSpending(index) {
            return this.totalIncomeNet(index) - this.totalExpenses(index) - this.totalInvesting(index) - this.totalSavingGoals(index);
        },
        shareOfIncome(value, income) {
            if (!income) {
                return 0;
            }

            return (value / income) * 100;
        },
        listValues(list, key, count) {
            return Array.from({ length: count }, (_, index) => this.toNumber(list?.[index]?.[key] ?? 0));
        },
        categoryValues(values, count) {
            return Array.from({ length: count }, (_, index) => this.toNumber(values?.[index] ?? 0));
        },
        applyCategoryData(source, fallback) {
            if (!Array.isArray(source)) {
                return fallback;
            }

            if (source.length === 0) {
                return [];
            }

            const count = this.partners.length;
            return source.map((item, index) => ({
                id: item.id ?? fallback[index]?.id ?? null,
                label: this.sanitizeLabel(item.label ?? fallback[index]?.label ?? 'Category'),
                values: this.categoryValues(item.values, count),
            }));
        },
        ensurePartnerCount(count) {
            if (!Number.isInteger(count) || count < 1) {
                return;
            }

            while (this.partners.length < count) {
                this.addPartner();
            }
        },
        applyPlan(data) {
            if (!data) {
                return;
            }

            if (Array.isArray(data.currencies)) {
                this.currencies = data.currencies;
            }

            if (data.plan?.currency) {
                this.currencyCode = data.plan.currency;
            }

            if (data.plan?.buffer_percent !== undefined) {
                this.bufferPercent = this.toNumber(data.plan.buffer_percent);
            }

            if (Array.isArray(data.partners) && data.partners.length > 0) {
                const partners = data.partners.map((partner, index) => ({
                    id: partner.id ?? null,
                    name: partner.name || `Partner ${index + 1}`,
                }));

                this.partners = partners;
            }

            this.ensurePartnerCount(Math.max(this.partners.length, 1));

            if (Array.isArray(data.netWorth)) {
                const count = this.partners.length;
                this.netWorth.assets = this.listValues(data.netWorth, 'assets', count);
                this.netWorth.invested = this.listValues(data.netWorth, 'invested', count);
                this.netWorth.saving = this.listValues(data.netWorth, 'saving', count);
                this.netWorth.debt = this.listValues(data.netWorth, 'debt', count);
            }

            if (Array.isArray(data.income)) {
                const count = this.partners.length;
                this.income.net = this.listValues(data.income, 'net', count);
                this.income.gross = this.listValues(data.income, 'gross', count);
            }

            this.expenses = this.applyCategoryData(data.expenses, this.expenses);
            this.investing = this.applyCategoryData(data.investing, this.investing);
            this.savingGoals = this.applyCategoryData(data.savingGoals, this.savingGoals);
        },
        buildPayload() {
            const count = this.partners.length;
            return {
                plan: {
                    buffer_percent: this.toNumber(this.bufferPercent),
                    currency: this.currencyCode,
                },
                partners: this.partners.map((partner, index) => ({
                    id: partner.id,
                    name: partner.name || `Partner ${index + 1}`,
                })),
                netWorth: Array.from({ length: count }, (_, index) => ({
                    assets: this.toNumber(this.netWorth.assets[index]),
                    invested: this.toNumber(this.netWorth.invested[index]),
                    saving: this.toNumber(this.netWorth.saving[index]),
                    debt: this.toNumber(this.netWorth.debt[index]),
                })),
                income: Array.from({ length: count }, (_, index) => ({
                    net: this.toNumber(this.income.net[index]),
                    gross: this.toNumber(this.income.gross[index]),
                })),
                expenses: this.expenses.map((item) => ({
                    id: item.id,
                    label: this.sanitizeLabel(item.label),
                    values: item.values.map((value) => this.toNumber(value)),
                })),
                investing: this.investing.map((item) => ({
                    id: item.id,
                    label: this.sanitizeLabel(item.label),
                    values: item.values.map((value) => this.toNumber(value)),
                })),
                savingGoals: this.savingGoals.map((item) => ({
                    id: item.id,
                    label: this.sanitizeLabel(item.label),
                    values: item.values.map((value) => this.toNumber(value)),
                })),
            };
        },
        async fetchPlan() {
            this.loading = true;
            try {
                const response = await window.axios.get('/plan/data');
                this.applyPlan(response.data);
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },
        async savePlan(options = {}) {
            this.saving = true;
            if (!options.silent) {
                this.saveNotice = '';
            }
            try {
                const response = await window.axios.post('/plan', this.buildPayload());
                this.applyPlan(response.data);
                if (!options.silent) {
                    this.saveNotice = 'Saved just now.';
                }
                return true;
            } catch (error) {
                console.error(error);
                if (!options.silent) {
                    this.saveNotice = 'Save failed. Try again.';
                }
                return false;
            } finally {
                this.saving = false;
                if (!options.silent) {
                    setTimeout(() => {
                        this.saveNotice = '';
                    }, 2500);
                }
            }
        },
        async createSnapshot() {
            this.snapshotSaving = true;
            this.snapshotNotice = '';

            const saved = await this.savePlan({ silent: true });
            if (!saved) {
                this.snapshotSaving = false;
                this.snapshotNotice = 'Snapshot failed. Please save again.';
                setTimeout(() => {
                    this.snapshotNotice = '';
                }, 2500);
                return;
            }

            try {
                await window.axios.post('/plan/snapshots', {
                    note: this.snapshotNote.trim() || null,
                });
                this.snapshotNotice = 'Snapshot created.';
                this.snapshotNote = '';
            } catch (error) {
                console.error(error);
                this.snapshotNotice = 'Snapshot failed. Try again.';
            } finally {
                this.snapshotSaving = false;
                setTimeout(() => {
                    this.snapshotNotice = '';
                }, 2500);
            }
        },
    };
};

window.cspSnapshots = function () {
    return {
        loading: true,
        allSnapshots: [],
        snapshots: [],
        currentNetWorth: null,
        currencyCode: 'USD',
        chart: null,
        series: {},
        resizeHandler: null,
        resizeObserver: null,
        rangeStart: '',
        rangeEnd: '',
        paginatedSnapshots: [],
        pagination: {
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 10,
        },
        paginationLoading: false,
        expandedSnapshotId: null,
        init() {
            this.fetchSummary();
            this.fetchSnapshotPage();
        },
        formatCurrency(value) {
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: this.currencyCode || 'USD',
                maximumFractionDigits: 0,
            });

            return formatter.format(Math.round(value ?? 0));
        },
        formatDate(value) {
            if (!value) {
                return '';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '';
            }

            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            }).format(date);
        },
        async fetchSummary() {
            this.loading = true;
            try {
                const summaryResponse = await window.axios.get('/plan/snapshots/summary/data');

                this.allSnapshots = summaryResponse.data.snapshots ?? [];
                this.currencyCode = summaryResponse.data.currency || this.currencyCode;
                this.setDefaultRange();
                this.applyFilters();
                this.$nextTick(() => {
                    this.renderChart();
                });
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },
        async fetchSnapshotPage(page = 1) {
            this.paginationLoading = true;
            try {
                const response = await window.axios.get('/plan/snapshots', {
                    params: {
                        page,
                        per_page: this.pagination.perPage,
                    },
                });

                this.paginatedSnapshots = response.data.snapshots ?? [];
                this.pagination = {
                    currentPage: response.data.meta?.current_page ?? page,
                    lastPage: response.data.meta?.last_page ?? page,
                    total: response.data.meta?.total ?? this.paginatedSnapshots.length,
                    perPage: response.data.meta?.per_page ?? this.pagination.perPage,
                };
            } catch (error) {
                console.error(error);
            } finally {
                this.paginationLoading = false;
            }
        },
        goToPage(page) {
            const target = Math.min(Math.max(page, 1), this.pagination.lastPage || 1);
            this.fetchSnapshotPage(target);
        },
        toggleSnapshotNote(snapshotId) {
            this.expandedSnapshotId = this.expandedSnapshotId === snapshotId ? null : snapshotId;
        },
        setDefaultRange() {
            if (!this.allSnapshots.length || (this.rangeStart && this.rangeEnd)) {
                return;
            }

            const dates = this.allSnapshots
                .map((snapshot) => snapshot.captured_at?.split('T')[0])
                .filter(Boolean);

            if (!dates.length) {
                return;
            }

            this.rangeStart = dates[0];
            this.rangeEnd = dates[dates.length - 1];
        },
        applyFilters() {
            const start = this.rangeStart || null;
            const end = this.rangeEnd || null;

            this.snapshots = this.allSnapshots.filter((snapshot) => {
                const capturedDate = snapshot.captured_at?.split('T')[0];
                if (!capturedDate) {
                    return false;
                }

                if (start && capturedDate < start) {
                    return false;
                }

                if (end && capturedDate > end) {
                    return false;
                }

                return true;
            });

            const latest = this.latestSnapshot();
            this.currentNetWorth = latest ? Number(latest.net_worth ?? 0) : null;
            this.$nextTick(() => {
                this.renderChart();
            });
        },
        resetRange() {
            this.rangeStart = '';
            this.rangeEnd = '';
            this.setDefaultRange();
            this.applyFilters();
        },
        latestSnapshot() {
            return this.snapshots.length ? this.snapshots[this.snapshots.length - 1] : null;
        },
        latestSnapshotNetWorth() {
            return Number(this.latestSnapshot()?.net_worth ?? 0);
        },
        previousSnapshot() {
            if (this.snapshots.length < 2) {
                return null;
            }

            return this.snapshots[this.snapshots.length - 2];
        },
        previousSnapshotNetWorth() {
            return Number(this.previousSnapshot()?.net_worth ?? 0);
        },
        netWorthDelta() {
            if (this.currentNetWorth === null || !this.previousSnapshot()) {
                return null;
            }

            return Number(this.currentNetWorth) - this.previousSnapshotNetWorth();
        },
        netWorthTrend() {
            const delta = this.netWorthDelta();

            if (delta === null || delta === 0) {
                return 0;
            }

            return delta > 0 ? 1 : -1;
        },
        metricDelta(key) {
            const latest = this.latestSnapshot();
            const previous = this.previousSnapshot();

            if (!latest || !previous) {
                return null;
            }

            return Number(latest[key] ?? 0) - Number(previous[key] ?? 0);
        },
        metricTrend(key) {
            const delta = this.metricDelta(key);

            if (delta === null) {
                return 0;
            }

            if (delta === 0) {
                return 0;
            }

            if (key === 'expenses') {
                return delta < 0 ? 1 : -1;
            }

            return delta > 0 ? 1 : -1;
        },
        trendClass(key) {
            const trend = this.metricTrend(key);

            if (trend > 0) {
                return 'text-emerald-600';
            }

            if (trend < 0) {
                return 'text-rose-600';
            }

            return 'text-slate-400';
        },
        trendBadgeClass(key) {
            const trend = this.metricTrend(key);

            if (trend > 0) {
                return 'bg-emerald-50 text-emerald-600 border-emerald-200';
            }

            if (trend < 0) {
                return 'bg-rose-50 text-rose-600 border-rose-200';
            }

            return 'bg-slate-50 text-slate-400 border-slate-200';
        },
        netWorthTrendClass() {
            const trend = this.netWorthTrend();

            if (trend > 0) {
                return 'text-emerald-600';
            }

            if (trend < 0) {
                return 'text-rose-600';
            }

            return 'text-slate-400';
        },
        netWorthTrendBadgeClass() {
            const trend = this.netWorthTrend();

            if (trend > 0) {
                return 'bg-emerald-50 text-emerald-600 border-emerald-200';
            }

            if (trend < 0) {
                return 'bg-rose-50 text-rose-600 border-rose-200';
            }

            return 'bg-slate-50 text-slate-400 border-slate-200';
        },
        formatNetWorthDelta() {
            const delta = this.netWorthDelta();

            if (delta === null) {
                return '';
            }

            const sign = delta > 0 ? '+' : '';
            const value = this.formatCurrency(Math.abs(delta));

            return `${sign}${value}`;
        },
        formatDelta(key) {
            const delta = this.metricDelta(key);

            if (delta === null) {
                return '';
            }

            const sign = delta > 0 ? '+' : '';
            const value = this.formatCurrency(Math.abs(delta));

            return `${sign}${value}`;
        },
        snapshotSeries(key) {
            return this.snapshots
                .map((snapshot) => {
                    const date = snapshot.captured_at ? new Date(snapshot.captured_at) : null;
                    if (!date || Number.isNaN(date.getTime())) {
                        return null;
                    }

                    return {
                        time: date.toISOString().split('T')[0],
                        value: Number(snapshot[key] ?? 0),
                    };
                })
                .filter(Boolean);
        },
        renderChart() {
            if (!this.$refs.chart) {
                return;
            }

            if (this.snapshots.length === 0) {
                if (this.chart) {
                    this.chart.remove();
                    this.chart = null;
                    this.series = {};
                }
                return;
            }

            if (this.chart) {
                this.chart.remove();
                this.chart = null;
                this.series = {};
            }

            const width = this.$refs.chart.clientWidth || 600;
            this.chart = createChart(this.$refs.chart, {
                width,
                height: 240,
                layout: {
                    background: { type: 'solid', color: 'transparent' },
                    textColor: '#64748b',
                },
                grid: {
                    vertLines: { color: 'rgba(148, 163, 184, 0.15)' },
                    horzLines: { color: 'rgba(148, 163, 184, 0.15)' },
                },
                rightPriceScale: {
                    borderColor: 'rgba(148, 163, 184, 0.4)',
                },
                timeScale: {
                    borderColor: 'rgba(148, 163, 184, 0.4)',
                },
                crosshair: {
                    mode: 0,
                },
            });

            this.series.assets = this.chart.addSeries(LineSeries, {
                color: '#0f172a',
                lineWidth: 2,
            });
            this.series.expenses = this.chart.addSeries(LineSeries, {
                color: '#64748b',
                lineWidth: 2,
            });
            this.series.investing = this.chart.addSeries(LineSeries, {
                color: '#10b981',
                lineWidth: 2,
            });
            this.series.saving = this.chart.addSeries(LineSeries, {
                color: '#f59e0b',
                lineWidth: 2,
            });

            this.series.assets.setData(this.snapshotSeries('assets'));
            this.series.expenses.setData(this.snapshotSeries('expenses'));
            this.series.investing.setData(this.snapshotSeries('investing'));
            this.series.saving.setData(this.snapshotSeries('saving'));

            this.chart.timeScale().fitContent();

            if (!this.resizeHandler) {
                this.resizeHandler = () => {
                    if (this.chart && this.$refs.chart) {
                        this.chart.applyOptions({ width: this.$refs.chart.clientWidth });
                    }
                };
                window.addEventListener('resize', this.resizeHandler);
            }

            if (!this.resizeObserver && window.ResizeObserver) {
                this.resizeObserver = new ResizeObserver(() => {
                    if (this.chart && this.$refs.chart) {
                        this.chart.applyOptions({ width: this.$refs.chart.clientWidth });
                    }
                });
                this.resizeObserver.observe(this.$refs.chart);
            }

            requestAnimationFrame(() => {
                if (this.chart && this.$refs.chart) {
                    this.chart.applyOptions({ width: this.$refs.chart.clientWidth });
                    this.chart.timeScale().fitContent();
                }
            });
        },
    };
};

Alpine.start();

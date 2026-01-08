import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.cspPlan = function () {
    return {
        loading: true,
        saving: false,
        saveNotice: '',
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
        toNumber(value) {
            return Number.isFinite(value) ? value : Number.parseFloat(value) || 0;
        },
        sumPair(values) {
            return values.reduce((total, value) => total + this.toNumber(value), 0);
        },
        sumItems(items, index) {
            return items.reduce((total, item) => total + this.toNumber(item.values[index]), 0);
        },
        formatCurrency(value) {
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
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
                return this.netIncome(0) + this.netIncome(1);
            }

            return this.netIncome(index);
        },
        totalIncomeGross(index) {
            if (index === undefined) {
                return this.grossIncome(0) + this.grossIncome(1);
            }

            return this.grossIncome(index);
        },
        expensesSubtotal(index) {
            if (index === undefined) {
                return this.sumItems(this.expenses, 0) + this.sumItems(this.expenses, 1);
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
                return this.sumItems(this.investing, 0) + this.sumItems(this.investing, 1);
            }

            return this.sumItems(this.investing, index);
        },
        totalSavingGoals(index) {
            if (index === undefined) {
                return this.sumItems(this.savingGoals, 0) + this.sumItems(this.savingGoals, 1);
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
        pairValues(list, key) {
            return [0, 1].map((index) => this.toNumber(list?.[index]?.[key] ?? 0));
        },
        categoryValues(values) {
            return [0, 1].map((index) => this.toNumber(values?.[index] ?? 0));
        },
        applyCategoryData(source, fallback) {
            if (!Array.isArray(source) || source.length === 0) {
                return fallback;
            }

            return source.map((item, index) => ({
                id: item.id ?? fallback[index]?.id ?? null,
                label: item.label ?? fallback[index]?.label ?? 'Category',
                values: this.categoryValues(item.values),
            }));
        },
        applyPlan(data) {
            if (!data) {
                return;
            }

            if (data.plan?.buffer_percent !== undefined) {
                this.bufferPercent = this.toNumber(data.plan.buffer_percent);
            }

            if (Array.isArray(data.partners) && data.partners.length > 0) {
                const partners = data.partners.map((partner, index) => ({
                    id: partner.id ?? null,
                    name: partner.name || `Partner ${index + 1}`,
                }));

                while (partners.length < 2) {
                    partners.push({ id: null, name: `Partner ${partners.length + 1}` });
                }

                this.partners = partners.slice(0, 2);
            }

            if (Array.isArray(data.netWorth)) {
                this.netWorth.assets = this.pairValues(data.netWorth, 'assets');
                this.netWorth.invested = this.pairValues(data.netWorth, 'invested');
                this.netWorth.saving = this.pairValues(data.netWorth, 'saving');
                this.netWorth.debt = this.pairValues(data.netWorth, 'debt');
            }

            if (Array.isArray(data.income)) {
                this.income.net = this.pairValues(data.income, 'net');
                this.income.gross = this.pairValues(data.income, 'gross');
            }

            this.expenses = this.applyCategoryData(data.expenses, this.expenses);
            this.investing = this.applyCategoryData(data.investing, this.investing);
            this.savingGoals = this.applyCategoryData(data.savingGoals, this.savingGoals);
        },
        buildPayload() {
            return {
                plan: {
                    buffer_percent: this.toNumber(this.bufferPercent),
                },
                partners: this.partners.map((partner, index) => ({
                    id: partner.id,
                    name: partner.name || `Partner ${index + 1}`,
                })),
                netWorth: [0, 1].map((index) => ({
                    assets: this.toNumber(this.netWorth.assets[index]),
                    invested: this.toNumber(this.netWorth.invested[index]),
                    saving: this.toNumber(this.netWorth.saving[index]),
                    debt: this.toNumber(this.netWorth.debt[index]),
                })),
                income: [0, 1].map((index) => ({
                    net: this.toNumber(this.income.net[index]),
                    gross: this.toNumber(this.income.gross[index]),
                })),
                expenses: this.expenses.map((item) => ({
                    id: item.id,
                    values: item.values.map((value) => this.toNumber(value)),
                })),
                investing: this.investing.map((item) => ({
                    id: item.id,
                    values: item.values.map((value) => this.toNumber(value)),
                })),
                savingGoals: this.savingGoals.map((item) => ({
                    id: item.id,
                    values: item.values.map((value) => this.toNumber(value)),
                })),
            };
        },
        async fetchPlan() {
            this.loading = true;
            try {
                const response = await window.axios.get('/plan');
                this.applyPlan(response.data);
            } catch (error) {
                console.error(error);
            } finally {
                this.loading = false;
            }
        },
        async savePlan() {
            this.saving = true;
            this.saveNotice = '';
            try {
                const response = await window.axios.post('/plan', this.buildPayload());
                this.applyPlan(response.data);
                this.saveNotice = 'Saved just now.';
            } catch (error) {
                console.error(error);
                this.saveNotice = 'Save failed. Try again.';
            } finally {
                this.saving = false;
                setTimeout(() => {
                    this.saveNotice = '';
                }, 2500);
            }
        },
    };
};

Alpine.start();

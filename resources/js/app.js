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

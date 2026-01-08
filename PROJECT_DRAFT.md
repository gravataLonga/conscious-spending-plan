# Project Draft: Conscious Spending Plan

## Overview
This project builds a "Conscious Spending Plan" inspired by Ramit Sethi's framework. The core is a single table that helps one or two partners organize income, expenses, investing, savings, and guilt-free spending, with automatic totals and percentage calculations.

## Goals
- Provide a clear, actionable view of household cash flow.
- Support one or two partners with side-by-side inputs.
- Auto-calculate totals, percentages, and remaining spend.

## Core Features
1) Net Worth Summary
- Total Net Worth = Assets + Invested + Saving - Debt.

2) Income
- Partner 1 and Partner 2 net and gross.
- Total combined annual income.

3) Total Expenses
- Fixed categories: Rent/Mortgage, Utilities, Insurance, Car Payment, Groceries, Clothes, Phone, Subscriptions, Debt.
- Buffer added automatically (default 15% of total expenses; adjustable).
- Percent of income displayed.

4) Investing
- Post-tax Retirement Saving, ETF, Other.
- Percent of income displayed.

5) Saving Goals
- Vacation, Gifts, Long Term Emergency Fund.
- Percent of income displayed.

6) Guilty-Free Spending
- Remainder after expenses, investing, and saving.

## Table Layout
- 3 columns per row: category/subcategory description, Partner 1 value, Partner 2 value.
- Category rows include calculated totals and percentages.

## Calculations
- Income total = P1 + P2 (net and gross).
- Expenses total = sum of fixed categories + buffer.
- Buffer = default 15% of expenses subtotal; user-adjustable.
- Investing/Saving totals = sum of subcategories.
- Guilty-free spending = Income - Expenses - Investing - Saving.
- Net worth = Assets + Invested + Saving - Debt.

## UX Notes
- Simple, single-page table.
- Clear section headers and totals.
- Inline percentage indicators for category totals.

## Open Questions
- Currency and locale formatting.
- Annual vs monthly input defaults.
- Ability to toggle buffer percentage globally.
- Optional export (CSV/PDF) for sharing.

## Next Steps
- Define data model (fields per category, per partner).
- Build calculations and validation rules.
- Draft UI for the table and totals.

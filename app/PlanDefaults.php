<?php

namespace App;

enum PlanDefaults
{
    case Expenses;
    case Investing;
    case SavingGoals;
    case Partners;

    /**
     * @return array<int, string>
     */
    public function labels(): array
    {
        return match ($this) {
            self::Expenses => [
                'Rent or Mortgage',
                'Utilities',
                'Insurance',
                'Car Payment',
                'Groceries',
                'Clothes',
                'Phone',
                'Subscriptions',
                'Debt',
            ],
            self::Investing => [
                'Post-tax Retirement Saving',
                'ETF',
                'Other',
            ],
            self::SavingGoals => [
                'Vacation',
                'Gifts',
                'Long Term Emergency Fund',
            ],
            self::Partners => [
                'Partner 1',
                'Partner 2',
            ],
        };
    }
}

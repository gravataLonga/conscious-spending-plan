<?php

namespace Tests\Unit;

use App\PlanDefaults;
use PHPUnit\Framework\TestCase;

class PlanDefaultsTest extends TestCase
{
    public function test_it_exposes_default_plan_labels(): void
    {
        $this->assertSame([
            'Rent or Mortgage',
            'Utilities',
            'Insurance',
            'Car Payment',
            'Groceries',
            'Clothes',
            'Phone',
            'Subscriptions',
            'Debt',
        ], PlanDefaults::Expenses->labels());

        $this->assertSame([
            'Post-tax Retirement Saving',
            'ETF',
            'Other',
        ], PlanDefaults::Investing->labels());

        $this->assertSame([
            'Vacation',
            'Gifts',
            'Long Term Emergency Fund',
        ], PlanDefaults::SavingGoals->labels());

        $this->assertSame([
            'Partner 1',
            'Partner 2',
        ], PlanDefaults::Partners->labels());
    }
}

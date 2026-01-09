<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <title>Conscious Spending Plan</title>
        <style>
            * {
                box-sizing: border-box;
            }
            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                color: #0f172a;
                font-size: 12px;
                margin: 24px;
            }
            h1 {
                font-size: 22px;
                margin: 0 0 6px;
            }
            .meta {
                color: #475569;
                font-size: 11px;
                margin-bottom: 18px;
            }
            .section {
                margin-top: 18px;
            }
            .section-title {
                font-size: 14px;
                font-weight: 700;
                margin: 0 0 6px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th,
            td {
                border: 1px solid #e2e8f0;
                padding: 6px 8px;
                text-align: right;
            }
            th:first-child,
            td:first-child {
                text-align: left;
            }
            thead th {
                background: #f8fafc;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #475569;
            }
        </style>
    </head>
    <body>
        @php
            $currency = $plan['currency'] ?: 'USD';
            $format = fn ($value) => number_format((float) $value, 2);
        @endphp

        <h1>Conscious Spending Plan</h1>
        <div class="meta">
            Exported {{ $exportedAt }} · Buffer {{ number_format((float) ($plan['buffer_percent'] ?? 0), 2) }}% · Currency {{ $currency }}
        </div>

        <div class="section">
            <div class="section-title">Net Worth</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        @foreach ($partners as $partner)
                            <th>{{ $partner['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Assets</td>
                        @foreach ($netWorth as $row)
                            <td>{{ $format($row['assets']) }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td>Invested</td>
                        @foreach ($netWorth as $row)
                            <td>{{ $format($row['invested']) }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td>Saving</td>
                        @foreach ($netWorth as $row)
                            <td>{{ $format($row['saving']) }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td>Debt</td>
                        @foreach ($netWorth as $row)
                            <td>{{ $format($row['debt']) }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Income</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        @foreach ($partners as $partner)
                            <th>{{ $partner['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Net Income (Annual)</td>
                        @foreach ($income as $row)
                            <td>{{ $format($row['net']) }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td>Gross Income (Annual)</td>
                        @foreach ($income as $row)
                            <td>{{ $format($row['gross']) }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Expenses</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        @foreach ($partners as $partner)
                            <th>{{ $partner['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expenses as $category)
                        <tr>
                            <td>{{ $category['label'] }}</td>
                            @foreach ($category['values'] as $value)
                                <td>{{ $format($value) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Investing</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        @foreach ($partners as $partner)
                            <th>{{ $partner['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($investing as $category)
                        <tr>
                            <td>{{ $category['label'] }}</td>
                            @foreach ($category['values'] as $value)
                                <td>{{ $format($value) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Saving Goals</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        @foreach ($partners as $partner)
                            <th>{{ $partner['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($savingGoals as $category)
                        <tr>
                            <td>{{ $category['label'] }}</td>
                            @foreach ($category['values'] as $value)
                                <td>{{ $format($value) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </body>
</html>

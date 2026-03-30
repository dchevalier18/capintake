<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>NPI Report</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 10px;
        }

        .header h1 { font-size: 16px; margin: 0 0 4px; }
        .header h2 { font-size: 12px; margin: 0 0 4px; font-weight: normal; }
        .header p { font-size: 11px; margin: 2px 0; color: #555; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }

        th {
            background-color: #2d3748;
            color: white;
            text-align: left;
            padding: 6px 8px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        th.num { text-align: right; }

        td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        tr.goal-row {
            background-color: #edf2f7;
            font-weight: bold;
        }

        tr.goal-row td {
            padding: 8px;
            border-bottom: 2px solid #cbd5e0;
            font-size: 11px;
        }

        tr.indicator-row td { padding-left: 20px; }

        tr.grand-total {
            background-color: #2d3748;
            color: white;
            font-weight: bold;
        }

        tr.grand-total td {
            padding: 8px;
            border: none;
            font-size: 11px;
        }

        .zero { color: #a0aec0; }

        h3.section-title {
            font-size: 12px;
            margin: 25px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e0;
        }

        .demo-table th {
            font-size: 7px;
            padding: 4px 3px;
            white-space: nowrap;
        }

        .demo-table td {
            font-size: 8px;
            padding: 3px;
            text-align: right;
        }

        .demo-table td:first-child { text-align: left; }

        .footer {
            margin-top: 30px;
            font-size: 8px;
            color: #999;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CSBG National Performance Indicators Report</h1>
        @if(!empty($programLabel))
            <h2>Program: {{ $programLabel }}</h2>
        @endif
        <p><strong>Reporting Period:</strong> {{ $startDate }} &ndash; {{ $endDate }}</p>
        <p><strong>Generated:</strong> {{ now()->format('m/d/Y h:i A') }}</p>
    </div>

    {{-- Main Summary Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 60px;">NPI Code</th>
                <th>Goal / Indicator</th>
                <th class="num" style="width: 100px;">Unduplicated<br>Individuals</th>
                <th class="num" style="width: 80px;">Total<br>Services</th>
                <th class="num" style="width: 80px;">Total<br>Value ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report as $goal)
                <tr class="goal-row">
                    <td>Goal {{ $goal['goal_number'] }}</td>
                    <td>{{ $goal['goal_name'] }}</td>
                    <td class="num">{{ number_format($goal['goal_total_clients']) }}</td>
                    <td class="num"></td>
                    <td class="num"></td>
                </tr>
                @foreach($goal['indicators'] as $indicator)
                    <tr class="indicator-row">
                        <td>{{ $indicator['indicator_code'] }}</td>
                        <td>{{ $indicator['indicator_name'] }}</td>
                        <td class="num {{ $indicator['unduplicated_clients'] === 0 ? 'zero' : '' }}">
                            {{ number_format($indicator['unduplicated_clients']) }}
                        </td>
                        <td class="num {{ $indicator['total_services'] === 0 ? 'zero' : '' }}">
                            {{ number_format($indicator['total_services']) }}
                        </td>
                        <td class="num {{ $indicator['total_value'] == 0 ? 'zero' : '' }}">
                            ${{ number_format($indicator['total_value'], 2) }}
                        </td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="grand-total">
                <td></td>
                <td>GRAND TOTAL (Unduplicated Across All Goals)</td>
                <td class="num">{{ number_format($grandTotal) }}</td>
                <td class="num"></td>
                <td class="num"></td>
            </tr>
        </tbody>
    </table>

    {{-- Demographic Breakdown --}}
    @php
        $raceLabels = ['white' => 'White', 'black' => 'Black/AA', 'asian' => 'Asian', 'native_american' => 'AI/AN', 'pacific_islander' => 'NH/PI', 'multi_racial' => '2+ Races', 'other' => 'Other'];
        $genderLabels = ['male' => 'M', 'female' => 'F', 'non_binary' => 'NB', 'other' => 'Oth'];
        $ageLabels = ['0-5', '6-12', '13-17', '18-24', '25-44', '45-54', '55-64', '65+'];
        $hasAnyDemographics = false;
        foreach ($report as $g) {
            if (collect($g['indicators'])->sum('unduplicated_clients') > 0) {
                $hasAnyDemographics = true;
                break;
            }
        }
    @endphp

    @if($hasAnyDemographics)
        <div class="page-break"></div>

        <div class="header">
            <h1>Demographic Breakdown</h1>
            <p>Unduplicated individuals by race, gender, and age range</p>
        </div>

        @foreach($report as $goal)
            @php $hasData = collect($goal['indicators'])->sum('unduplicated_clients') > 0; @endphp

            @if($hasData)
                <h3 class="section-title">Goal {{ $goal['goal_number'] }}: {{ $goal['goal_name'] }}</h3>

                <table class="demo-table">
                    <thead>
                        <tr>
                            <th style="text-align: left; width: 50px;">NPI</th>
                            <th>Total</th>
                            @foreach($raceLabels as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                            @foreach($genderLabels as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                            @foreach($ageLabels as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($goal['indicators'] as $indicator)
                            @if($indicator['unduplicated_clients'] > 0)
                                <tr>
                                    <td>{{ $indicator['indicator_code'] }}</td>
                                    <td>{{ $indicator['unduplicated_clients'] }}</td>
                                    @foreach(array_keys($raceLabels) as $key)
                                        <td class="{{ ($indicator['by_race'][$key] ?? 0) === 0 ? 'zero' : '' }}">{{ $indicator['by_race'][$key] ?? 0 }}</td>
                                    @endforeach
                                    @foreach(array_keys($genderLabels) as $key)
                                        <td class="{{ ($indicator['by_gender'][$key] ?? 0) === 0 ? 'zero' : '' }}">{{ $indicator['by_gender'][$key] ?? 0 }}</td>
                                    @endforeach
                                    @foreach($ageLabels as $key)
                                        <td class="{{ ($indicator['by_age'][$key] ?? 0) === 0 ? 'zero' : '' }}">{{ $indicator['by_age'][$key] ?? 0 }}</td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif

    <div class="footer">
        Generated by CAPIntake &mdash; {{ config('app.name') }} &mdash; {{ now()->format('m/d/Y h:i A') }}
    </div>
</body>
</html>

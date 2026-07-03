@extends('reports.csbg.layout')

@section('content')
<h2>Module 4, Section C — Characteristics of Individuals and Households Served</h2>

<p><strong>Total Unduplicated Individuals: {{ number_format($module4c['total_unduplicated_individuals'] ?? $module4c['total_unduplicated'] ?? 0) }}</strong></p>
<p><strong>Total Unduplicated Households: {{ number_format($module4c['total_unduplicated_households'] ?? 0) }}</strong></p>

@foreach([
    'by_gender' => 'Gender',
    'by_race' => 'Race',
    'by_ethnicity' => 'Ethnicity',
    'by_age' => 'Age Range',
    'by_education_level' => 'Education Level',
    'by_employment_status' => 'Work Status (18+)',
    'by_disabling_condition' => 'Disabling Condition',
    'by_housing_type' => 'Housing Type',
    'by_household_type' => 'Household Type',
    'by_household_size' => 'Household Size',
    'by_health_insurance_status' => 'Health Insurance',
    'by_health_insurance_source' => 'Health Insurance Source',
    'by_military_status' => 'Military Status',
    'by_fpl_bracket' => 'Level of Household Income (% of HHS Guideline)',
    'by_income_source_composite' => 'Sources of Household Income',
    'by_income_source_type' => 'Other Income Sources',
    'by_non_cash_benefit' => 'Non-Cash Benefits',
] as $key => $label)
    <h3>{{ $label }}</h3>
    <table>
        <thead>
            <tr><th>Category</th><th style="width: 100px;">Count</th></tr>
        </thead>
        <tbody>
            @forelse($module4c[$key] ?? [] as $val => $count)
                <tr>
                    @php
                        $lookupCategory = $key === 'by_income_source_type' ? 'income_source' : str_replace('by_', '', $key);
                    @endphp
                    <td>{{ in_array($key, ['by_fpl_bracket', 'by_age', 'by_household_size', 'by_disabling_condition', 'by_income_source_composite'], true) ? ucfirst(str_replace('_', ' ', (string) $val)) : (\App\Services\Lookup::label($lookupCategory, $val) ?? ucfirst(str_replace('_', ' ', (string) $val))) }}</td>
                    <td class="num">{{ number_format($count) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No data</td></tr>
            @endforelse
        </tbody>
    </table>
@endforeach

<h3>E. Unduplicated Individuals Served per Program</h3>
<table>
    <thead>
        <tr><th>Program</th><th style="width: 100px;">Individuals</th></tr>
    </thead>
    <tbody>
        @forelse($module4c['section_e'] ?? [] as $row)
            <tr>
                <td>{{ $row['program'] }}</td>
                <td class="num">{{ number_format($row['count']) }}</td>
            </tr>
        @empty
            <tr><td colspan="2">No data</td></tr>
        @endforelse
    </tbody>
</table>

<h3>F. Unduplicated Households Served per Program</h3>
<table>
    <thead>
        <tr><th>Program</th><th style="width: 100px;">Households</th></tr>
    </thead>
    <tbody>
        @forelse($module4c['section_f'] ?? [] as $row)
            <tr>
                <td>{{ $row['program'] }}</td>
                <td class="num">{{ number_format($row['count']) }}</td>
            </tr>
        @empty
            <tr><td colspan="2">No data</td></tr>
        @endforelse
    </tbody>
</table>
@endsection

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
    'by_employment_status' => 'Employment Status',
    'by_housing_type' => 'Housing Type',
    'by_health_insurance_status' => 'Health Insurance',
    'by_military_status' => 'Military Status',
    'by_fpl_bracket' => 'Federal Poverty Level',
] as $key => $label)
    <h3>{{ $label }}</h3>
    <table>
        <thead>
            <tr><th>Category</th><th style="width: 100px;">Count</th></tr>
        </thead>
        <tbody>
            @forelse($module4c[$key] ?? [] as $val => $count)
                <tr>
                    <td>{{ $key === 'by_fpl_bracket' || $key === 'by_age' ? $val : (\App\Services\Lookup::label(str_replace('by_', '', $key), $val) ?? ucfirst(str_replace('_', ' ', $val))) }}</td>
                    <td class="num">{{ number_format($count) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No data</td></tr>
            @endforelse
        </tbody>
    </table>
@endforeach
@endsection

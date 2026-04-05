@extends('reports.csbg.layout')

@section('content')
<h2>Module 3, Section A — Community Initiative Status</h2>

@if(empty($module3))
    <p>No community initiatives recorded for FFY {{ $fiscalYear }}.</p>
@else
    @foreach($module3 as $init)
        <h3>{{ $init['name'] }}</h3>
        <table>
            <tr><th style="width: 150px;">Domain</th><td>{{ ucfirst(str_replace('_', ' ', $init['domain'])) }}</td></tr>
            <tr><th>Year of Initiative</th><td>{{ $init['year_number'] }}</td></tr>
            <tr><th>Community</th><td>{{ $init['identified_community'] ?? 'N/A' }}</td></tr>
            <tr><th>Expected Duration</th><td>{{ $init['expected_duration'] ?? 'N/A' }}</td></tr>
            <tr><th>Partnership Type</th><td>{{ ucfirst(str_replace('_', ' ', $init['partnership_type'] ?? 'N/A')) }}</td></tr>
            <tr><th>Progress Status</th><td>{{ ucfirst(str_replace('_', ' ', $init['progress_status'] ?? 'N/A')) }}</td></tr>
            <tr><th>Problem Statement</th><td>{{ $init['problem_statement'] ?? 'N/A' }}</td></tr>
            <tr><th>Goal Statement</th><td>{{ $init['goal_statement'] ?? 'N/A' }}</td></tr>
            @if($init['impact_narrative'])
                <tr><th>Impact</th><td>{{ $init['impact_narrative'] }}</td></tr>
            @endif
            @if($init['lessons_learned'])
                <tr><th>Lessons Learned</th><td>{{ $init['lessons_learned'] }}</td></tr>
            @endif
        </table>
    @endforeach
@endif
@endsection

@extends('reports.csbg.layout')

@section('content')
<h2>Module 4, Section A — Family National Performance Indicators (FNPIs)</h2>

@foreach($module4a as $goal)
    <h3>Goal {{ $goal['goal_number'] }}: {{ $goal['goal_name'] }} ({{ $goal['goal_total_clients'] }} unduplicated)</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Code</th>
                <th>Indicator</th>
                <th style="width: 80px;">Unduplicated</th>
                <th style="width: 70px;">Services</th>
                <th style="width: 80px;">Value ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($goal['indicators'] as $ind)
                <tr>
                    <td>{{ $ind['indicator_code'] }}</td>
                    <td>{{ $ind['indicator_name'] }}</td>
                    <td class="num">{{ number_format($ind['unduplicated_clients']) }}</td>
                    <td class="num">{{ number_format($ind['total_services']) }}</td>
                    <td class="num">${{ number_format($ind['total_value'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach
@endsection

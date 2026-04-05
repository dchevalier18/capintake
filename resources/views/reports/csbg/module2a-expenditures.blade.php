@extends('reports.csbg.layout')

@section('content')
<h2>Module 2, Section A — CSBG Expenditures</h2>

@if(empty($module2a))
    <p>No expenditure data available for FFY {{ $fiscalYear }}.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Domain</th>
                <th style="width: 120px;">CSBG Funds</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($module2a as $exp)
                @php $total += $exp['csbg_funds']; @endphp
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $exp['domain'])) }}</td>
                    <td class="num">${{ number_format($exp['csbg_funds'], 2) }}</td>
                    <td>{{ $exp['notes'] ?? '' }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold;">
                <td>Total</td>
                <td class="num">${{ number_format($total, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
@endif
@endsection

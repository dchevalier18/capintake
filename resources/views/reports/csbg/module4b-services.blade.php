@extends('reports.csbg.layout')

@section('content')
<h2>Module 4, Section B — Services</h2>

@foreach($module4b as $domain)
    <h3>{{ ucfirst(str_replace('_', ' ', $domain['domain'])) }} ({{ $domain['domain_total'] }} unduplicated)</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Code</th>
                <th>Service</th>
                <th style="width: 90px;">Unduplicated</th>
                <th style="width: 80px;">Total Svc</th>
            </tr>
        </thead>
        <tbody>
            @foreach($domain['categories'] as $cat)
                <tr>
                    <td>{{ $cat['code'] }}</td>
                    <td>{{ $cat['name'] }}</td>
                    <td class="num">{{ number_format($cat['unduplicated_clients']) }}</td>
                    <td class="num">{{ number_format($cat['total_services']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach
@endsection

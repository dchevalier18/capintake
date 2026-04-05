<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">FNPI Targets vs. Actuals (FY {{ $this->fiscalYear }})</x-slot>

        @if(empty($this->indicators))
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No FNPI targets set for FY {{ $this->fiscalYear }}. Set targets on the FNPI Targets page.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-xs uppercase text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-3">Indicator</th>
                        <th class="py-2 px-2 text-right">Served</th>
                        <th class="py-2 px-2 text-right">Target</th>
                        <th class="py-2 px-2 text-right">Achieved</th>
                        <th class="py-2 px-2 text-right">Progress</th>
                        <th class="py-2 pl-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->indicators as $ind)
                        <tr class="border-b border-gray-100 dark:border-gray-700">
                            <td class="py-1.5 pr-3">
                                <span class="font-mono text-xs text-gray-500">{{ $ind['indicator_code'] }}</span>
                                <span class="ml-1 text-gray-700 dark:text-gray-300">{{ Str::limit($ind['indicator_name'], 40) }}</span>
                            </td>
                            <td class="py-1.5 px-2 text-right">{{ number_format($ind['served']) }}</td>
                            <td class="py-1.5 px-2 text-right">{{ number_format($ind['target']) }}</td>
                            <td class="py-1.5 px-2 text-right font-semibold">{{ number_format($ind['actual']) }}</td>
                            <td class="py-1.5 px-2 text-right">{{ number_format($ind['pct_of_target'], 1) }}%</td>
                            <td class="py-1.5 pl-2">
                                @php
                                    $color = match($ind['status']) {
                                        'on_track' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'at_risk' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'behind' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                    };
                                    $label = match($ind['status']) {
                                        'on_track' => 'On Track',
                                        'at_risk' => 'At Risk',
                                        'behind' => 'Behind',
                                        default => 'No Target',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $color }}">{{ $label }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

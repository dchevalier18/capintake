<x-filament-panels::page>
    @if($this->overview)
        {{-- Overview Stats --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Clients</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->overview['total_clients']) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Avg. Completeness</p>
                <p class="text-2xl font-bold {{ $this->overview['avg_score'] >= 80 ? 'text-green-600' : ($this->overview['avg_score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $this->overview['avg_score'] }}%
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Fully Complete</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $this->overview['fully_complete'] }}
                    <span class="text-sm font-normal text-gray-500">({{ $this->overview['pct_fully_complete'] }}%)</span>
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Potential Duplicates</p>
                <p class="text-2xl font-bold {{ count($this->duplicates ?? []) > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ count($this->duplicates ?? []) }}
                </p>
            </div>
        </div>

        {{-- Most Common Missing Fields --}}
        @if(!empty($this->overview['missing_summary']))
            <x-filament::section>
                <x-slot name="heading">Most Commonly Missing Fields</x-slot>
                <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                    @foreach(array_slice($this->overview['missing_summary'], 0, 8) as $field => $count)
                        <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $field)) }}</p>
                            <p class="text-lg font-bold text-red-600">{{ $count }} <span class="text-xs font-normal text-gray-400">clients</span></p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Least Complete Clients --}}
        @if(!empty($this->leastComplete))
            <x-filament::section collapsible>
                <x-slot name="heading">Clients Needing Attention (Lowest Completeness)</x-slot>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-gray-500">
                            <th class="px-4 py-2">Client</th>
                            <th class="px-4 py-2 text-right">Score</th>
                            <th class="px-4 py-2">Missing Fields</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->leastComplete as $item)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="px-4 py-1.5">
                                    <a href="{{ \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $item['id']]) }}" class="text-primary-600 underline">
                                        {{ $item['name'] }}
                                    </a>
                                </td>
                                <td class="px-4 py-1.5 text-right font-semibold {{ $item['score'] >= 80 ? 'text-green-600' : ($item['score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $item['score'] }}%
                                </td>
                                <td class="px-4 py-1.5 text-xs text-gray-500">
                                    {{ implode(', ', array_map(fn ($f) => ucfirst(str_replace('_', ' ', $f)), $item['missing'])) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
        @endif

        {{-- Duplicate Detection --}}
        @if(!empty($this->duplicates))
            <x-filament::section collapsible>
                <x-slot name="heading">Potential Duplicate Clients ({{ count($this->duplicates) }} groups)</x-slot>
                @foreach($this->duplicates as $group)
                    <div class="mb-3 rounded-lg border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-800 dark:bg-yellow-900/20">
                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            {{ $group['match_type'] }}: {{ $group['match_key'] }}
                        </p>
                        <ul class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            @foreach($group['clients'] as $client)
                                <li>
                                    <a href="{{ \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $client['id']]) }}" class="text-primary-600 underline">
                                        {{ $client['name'] }}
                                    </a>
                                    (Born: {{ $client['dob_year'] }})
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </x-filament::section>
        @endif
    @else
        <div class="rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
            <p class="text-gray-500">Loading data quality metrics...</p>
        </div>
    @endif
</x-filament-panels::page>

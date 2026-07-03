<x-filament-panels::page>
    @if($this->overview)
        {{-- Overview Stats --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Clients</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->overview['total_clients']) }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg. Completeness</p>
                <p class="mt-1 text-2xl font-bold {{ $this->overview['avg_score'] >= 80 ? 'text-green-600 dark:text-green-400' : ($this->overview['avg_score'] >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ $this->overview['avg_score'] }}%
                </p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fully Complete</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $this->overview['fully_complete'] }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $this->overview['pct_fully_complete'] }}%)</span>
                </p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Potential Duplicates</p>
                <p class="mt-1 text-2xl font-bold {{ count($this->duplicates ?? []) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    {{ count($this->duplicates ?? []) }}
                </p>
            </div>
        </div>

        {{-- Most Common Missing Fields --}}
        @if(!empty($this->overview['missing_summary']))
            <x-filament::section>
                <x-slot name="heading">Most Commonly Missing Fields</x-slot>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    @foreach(array_slice($this->overview['missing_summary'], 0, 8) as $field => $count)
                        <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $field)) }}</p>
                            <p class="mt-0.5 text-lg font-bold text-red-600 dark:text-red-400">{{ $count }} <span class="text-xs font-normal text-gray-400 dark:text-gray-500">clients</span></p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Household Characteristics Completeness --}}
        @if($this->householdCompleteness && $this->householdCompleteness['total_households'] > 0)
            <x-filament::section>
                <x-slot name="heading">Household Characteristics (CSBG Section C)</x-slot>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Households Served</p>
                        <p class="mt-0.5 text-lg font-bold text-gray-900 dark:text-white">{{ number_format($this->householdCompleteness['total_households']) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Missing Household Type</p>
                        <p class="mt-0.5 text-lg font-bold {{ $this->householdCompleteness['missing_household_type'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $this->householdCompleteness['missing_household_type'] }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Missing Housing Type</p>
                        <p class="mt-0.5 text-lg font-bold {{ $this->householdCompleteness['missing_housing_type'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $this->householdCompleteness['missing_housing_type'] }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Least Complete Clients --}}
        @if(!empty($this->leastComplete))
            <x-filament::section collapsible>
                <x-slot name="heading">Clients Needing Attention (Lowest Completeness)</x-slot>
                <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Client</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Missing Fields</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                @foreach($this->leastComplete as $item)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <a href="{{ \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $item['id']]) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                                                {{ $item['name'] }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold {{ $item['score'] >= 80 ? 'text-green-600 dark:text-green-400' : ($item['score'] >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                            {{ $item['score'] }}%
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                            {{ implode(', ', array_map(fn ($f) => ucfirst(str_replace('_', ' ', $f)), $item['missing'])) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Duplicate Detection --}}
        @if(!empty($this->duplicates))
            <x-filament::section collapsible>
                <x-slot name="heading">Potential Duplicate Clients ({{ count($this->duplicates) }} groups)</x-slot>
                <div class="space-y-3">
                    @foreach($this->duplicates as $group)
                        <div class="rounded-xl bg-yellow-50 p-4 ring-1 ring-yellow-200 dark:bg-yellow-900/20 dark:ring-yellow-800">
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                {{ $group['match_type'] }}: {{ $group['match_key'] }}
                            </p>
                            <ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                @foreach($group['clients'] as $client)
                                    <li>
                                        <a href="{{ \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $client['id']]) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                                            {{ $client['name'] }}
                                        </a>
                                        <span class="text-gray-400 dark:text-gray-500">(Born: {{ $client['dob_year'] }})</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    @else
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-gray-500 dark:text-gray-400">Loading data quality metrics...</p>
        </div>
    @endif
</x-filament-panels::page>

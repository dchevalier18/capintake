<x-filament-panels::page>
    {{-- Header --}}
    @php $info = $this->getAgencyInfo(); @endphp
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $info['entity_name'] }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    {{ $info['state'] }} &middot; UEI: {{ $info['uei'] ?: 'Not set' }} &middot; Period: {{ $info['reporting_period'] }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Fiscal Year:</label>
                <select wire:model.live="fiscalYear" class="fi-input rounded-lg border-none bg-white py-2 pe-8 ps-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20">
                    @for ($y = now()->year + 1; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}">FFY {{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3">
        @if(!$this->reportData)
            <x-filament::button wire:click="generateReport" icon="heroicon-o-play">
                Generate Report
            </x-filament::button>
        @else
            <x-filament::button wire:click="regenerateReport" color="gray" icon="heroicon-o-arrow-path">
                Regenerate
            </x-filament::button>
            <x-filament::button tag="a" href="{{ route('csbg.export.csv', ['year' => $this->fiscalYear]) }}" icon="heroicon-o-arrow-down-tray" color="success">
                Export CSV
            </x-filament::button>
            <x-filament::button tag="a" href="{{ route('csbg.export.pdf', ['year' => $this->fiscalYear]) }}" icon="heroicon-o-document-arrow-down" color="danger">
                Export PDF
            </x-filament::button>
        @endif

        @if($this->generatedAt)
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Generated: {{ $this->generatedAt }}
            </span>
        @endif
    </div>

    {{-- Report Tabs --}}
    @if($this->reportData)
        <div x-data="{ tab: 'fnpi' }">
            <x-filament::tabs>
                <x-filament::tabs.item alpine-active="tab === 'fnpi'" x-on:click="tab = 'fnpi'">
                    Module 4A: FNPIs
                </x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'services'" x-on:click="tab = 'services'">
                    Module 4B: Services
                </x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'characteristics'" x-on:click="tab = 'characteristics'">
                    Module 4C: Characteristics
                </x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'expenditures'" x-on:click="tab = 'expenditures'">
                    Module 2A: Expenditures
                </x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'capacity'" x-on:click="tab = 'capacity'">
                    Module 2B: Capacity
                </x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'funding'" x-on:click="tab = 'funding'">
                    Module 2C: Resources
                </x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'initiatives'" x-on:click="tab = 'initiatives'">
                    Module 3A: Initiatives
                </x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'cnpis'" x-on:click="tab = 'cnpis'">
                    Module 3B: CNPIs
                </x-filament::tabs.item>
                <x-filament::tabs.item alpine-active="tab === 'strategies'" x-on:click="tab = 'strategies'">
                    Module 3C: Strategies
                </x-filament::tabs.item>
            </x-filament::tabs>

            <div class="mt-4">
                {{-- FNPI Tab --}}
                <div x-show="tab === 'fnpi'" class="space-y-4">
                    @foreach($this->reportData['module4a'] as $goal)
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    Goal {{ $goal['goal_number'] }}: {{ $goal['goal_name'] }}
                                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">({{ $goal['goal_total_clients'] }} unduplicated)</span>
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50/50 dark:bg-white/[0.02]">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Indicator</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">I. Served</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">II. Target</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">III. Results</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">IV. % Achieving</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">V. Accuracy</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                        @foreach($goal['indicators'] as $ind)
                                            <tr>
                                                <td class="px-4 py-2.5 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $ind['indicator_code'] }}</td>
                                                <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $ind['indicator_name'] }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($ind['individuals_served'] ?? $ind['unduplicated_clients']) }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($ind['target'] ?? 0) }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold {{ ($ind['actual_results'] ?? 0) > 0 ? 'text-green-700 dark:text-green-400' : 'text-gray-300 dark:text-gray-600' }}">{{ number_format($ind['actual_results'] ?? 0) }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ ($ind['pct_achieving'] ?? 0) > 0 ? number_format($ind['pct_achieving'], 1) . '%' : '-' }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ ($ind['target_accuracy'] ?? 0) > 0 ? number_format($ind['target_accuracy'], 1) . '%' : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Services Tab --}}
                <div x-show="tab === 'services'" x-cloak class="space-y-4">
                    @foreach($this->reportData['module4b'] as $domain)
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ ucfirst(str_replace('_', ' ', $domain['domain'])) }}
                                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">({{ $domain['domain_total'] }} unduplicated)</span>
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50/50 dark:bg-white/[0.02]">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Service</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Unduplicated</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Services</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                        @foreach($domain['categories'] as $cat)
                                            <tr>
                                                <td class="px-4 py-2.5 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $cat['code'] }}</td>
                                                <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $cat['name'] }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ $cat['unduplicated_clients'] }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $cat['total_services'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Characteristics Tab --}}
                <div x-show="tab === 'characteristics'" x-cloak>
                    @php $chars = $this->reportData['module4c']; @endphp

                    {{-- Section A & B: Totals --}}
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Unduplicated Individuals</span>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($chars['total_unduplicated_individuals'] ?? $chars['total_unduplicated'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Unduplicated Households</span>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($chars['total_unduplicated_households'] ?? 0) }}</p>
                        </div>
                        @if(($chars['disconnected_youth_count'] ?? 0) > 0)
                            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Disconnected Youth</span>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($chars['disconnected_youth_count']) }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Section C: Individual Level --}}
                    <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">C. Individual Level Characteristics</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach(['by_gender' => 'Sex', 'by_race' => 'Race', 'by_ethnicity' => 'Ethnicity', 'by_age' => 'Age', 'by_employment_status' => 'Work Status (18+)', 'by_disabling_condition' => 'Disabling Condition', 'by_health_insurance_status' => 'Health Insurance', 'by_health_insurance_source' => 'Health Insurance Source', 'by_military_status' => 'Military Status'] as $key => $label)
                            <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                                <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $label }}</h4>
                                </div>
                                <table class="w-full text-sm">
                                    <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                        @forelse($chars[$key] ?? [] as $val => $count)
                                            <tr>
                                                <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ \App\Services\Lookup::label(str_replace('by_', '', $key), $val) ?? ucfirst(str_replace('_', ' ', $val)) }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endforeach

                        {{-- Education by age split --}}
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Education Level (Ages 14-24)</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @forelse($chars['by_education_14_24'] ?? [] as $val => $count)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ \App\Services\Lookup::label('education_level', $val) ?? ucfirst(str_replace('_', ' ', $val)) }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Education Level (Ages 25+)</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @forelse($chars['by_education_25_plus'] ?? [] as $val => $count)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ \App\Services\Lookup::label('education_level', $val) ?? ucfirst(str_replace('_', ' ', $val)) }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Section D: Household Level --}}
                    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">D. Household Level Characteristics</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach(['by_household_type' => 'Household Type', 'by_household_size' => 'Household Size', 'by_housing_type' => 'Housing'] as $key => $label)
                            <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                                <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $label }}</h4>
                                </div>
                                <table class="w-full text-sm">
                                    <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                        @forelse($chars[$key] ?? [] as $val => $count)
                                            <tr>
                                                <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $val)) }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endforeach

                        {{-- FPL Brackets --}}
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Federal Poverty Level</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @forelse($chars['by_fpl_bracket'] ?? [] as $bracket => $count)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $bracket }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Income Source Composite --}}
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Sources of Household Income</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @forelse($chars['by_income_source_composite'] ?? [] as $val => $count)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $val)) }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Income Source Types --}}
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Other Income Sources</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @forelse($chars['by_income_source_type'] ?? [] as $val => $count)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ \App\Services\Lookup::label('income_source', $val) ?? ucfirst(str_replace('_', ' ', $val)) }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Non-Cash Benefits --}}
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h4 class="font-semibold text-gray-900 dark:text-white">Non-Cash Benefits</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @forelse($chars['by_non_cash_benefit'] ?? [] as $val => $count)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ \App\Services\Lookup::label('non_cash_benefit', $val) ?? strtoupper(str_replace('_', ' ', $val)) }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Sections E/F: per-program unduplicated counts --}}
                    <h3 class="mb-3 mt-8 text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">E / F. Individuals and Households Served per Program</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h4 class="font-semibold text-gray-900 dark:text-white">E. Unduplicated Individuals per Program</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @forelse($chars['section_e'] ?? [] as $row)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $row['program'] }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($row['count']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                <h4 class="font-semibold text-gray-900 dark:text-white">F. Unduplicated Households per Program</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @forelse($chars['section_f'] ?? [] as $row)
                                        <tr>
                                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $row['program'] }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($row['count']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-3 text-gray-400 dark:text-gray-500" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Expenditures Tab --}}
                <div x-show="tab === 'expenditures'" x-cloak>
                    @if(empty($this->reportData['module2a']))
                        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-gray-500 dark:text-gray-400">No expenditure data for FFY {{ $this->fiscalYear }}. <a href="{{ \App\Filament\Resources\CsbgExpenditureResource::getUrl('index') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Add expenditures</a>.</p>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-white/5">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Domain</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">CSBG Funds</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                        @foreach($this->reportData['module2a'] as $exp)
                                            <tr>
                                                <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $exp['domain'])) }}</td>
                                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">${{ number_format($exp['csbg_funds'], 2) }}</td>
                                                <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $exp['notes'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Capacity Building Tab --}}
                <div x-show="tab === 'capacity'" x-cloak>
                    @php $cap = $this->reportData['module2b'] ?? []; @endphp
                    @if(empty($cap))
                        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-gray-500 dark:text-gray-400">No capacity building data for FFY {{ $this->fiscalYear }}.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            @foreach($cap as $type => $metrics)
                                <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                                    <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ \App\Models\AgencyCapacityMetric::TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type)) }}</h4>
                                    </div>
                                    <table class="w-full text-sm">
                                        <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                            @foreach($metrics as $key => $value)
                                                <tr>
                                                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ number_format($value, $type === 'staff_certifications' || $type === 'partner_organizations' ? 0 : 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Funding Sources Tab --}}
                <div x-show="tab === 'funding'" x-cloak class="space-y-4">
                    @php $fund = $this->reportData['module2c'] ?? []; @endphp
                    @if(empty($fund['by_type'] ?? []))
                        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-gray-500 dark:text-gray-400">No funding source data for FFY {{ $this->fiscalYear }}. <a href="{{ \App\Filament\Resources\FundingSourceResource::getUrl('index') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Add funding sources</a>.</p>
                        </div>
                    @else
                        @foreach($fund['by_type'] as $group)
                            <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                                <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $group['type_label'] }}
                                        <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">(Total: ${{ number_format($group['total'], 2) }})</span>
                                    </h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50/50 dark:bg-white/[0.02]">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Source</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">CFDA</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                            @foreach($group['sources'] as $src)
                                                <tr>
                                                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $src['source_name'] }}</td>
                                                    <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $src['cfda_number'] ?? '' }}</td>
                                                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">${{ number_format($src['amount'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                            Grand Total All Resources: ${{ number_format($fund['grand_total'] ?? 0, 2) }}
                        </p>
                    @endif
                </div>

                {{-- Community Initiatives Tab --}}
                <div x-show="tab === 'initiatives'" x-cloak class="space-y-3">
                    @if(empty($this->reportData['module3']))
                        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-gray-500 dark:text-gray-400">No community initiatives for FFY {{ $this->fiscalYear }}. <a href="{{ \App\Filament\Resources\CommunityInitiativeResource::getUrl('index') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Add initiatives</a>.</p>
                        </div>
                    @else
                        @foreach($this->reportData['module3'] as $init)
                            <div class="rounded-xl p-5 ring-1 ring-gray-950/5 dark:ring-white/10">
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $init['name'] }}</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Domain: {{ ucfirst(str_replace('_', ' ', $init['domain'])) }} &middot; Year {{ $init['year_number'] }} &middot; Status: {{ $init['progress_status'] ?? 'N/A' }}</p>
                                @if($init['problem_statement'])
                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300"><span class="font-medium">Problem:</span> {{ $init['problem_statement'] }}</p>
                                @endif
                                @if($init['goal_statement'])
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300"><span class="font-medium">Goal:</span> {{ $init['goal_statement'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Community NPIs Tab --}}
                <div x-show="tab === 'cnpis'" x-cloak class="space-y-4">
                    @if(empty($this->reportData['module3b']))
                        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-gray-500 dark:text-gray-400">No community NPI data for FFY {{ $this->fiscalYear }}.</p>
                        </div>
                    @else
                        @foreach($this->reportData['module3b'] as $domain)
                            <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                                <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ ucfirst(str_replace('_', ' ', $domain['domain'])) }}
                                    </h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50/50 dark:bg-white/[0.02]">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Indicator</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Target</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actual</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Accuracy %</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                            @foreach($domain['indicators'] as $ind)
                                                <tr>
                                                    <td class="px-4 py-2.5 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $ind['code'] }}</td>
                                                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $ind['name'] }}</td>
                                                    <td class="px-4 py-2.5">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $ind['type'] === 'count_of_change' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400' }}">
                                                            {{ $ind['type'] === 'count_of_change' ? 'Count' : 'Rate' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $ind['target'] !== null ? number_format($ind['target']) : '-' }}</td>
                                                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ $ind['actual_result'] !== null ? number_format($ind['actual_result']) : '-' }}</td>
                                                    <td class="px-4 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $ind['performance_accuracy'] !== null ? number_format($ind['performance_accuracy'], 1) . '%' : '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Community Strategies Tab --}}
                <div x-show="tab === 'strategies'" x-cloak class="space-y-4">
                    @if(empty($this->reportData['module3c']))
                        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-gray-500 dark:text-gray-400">No community strategy data for FFY {{ $this->fiscalYear }}.</p>
                        </div>
                    @else
                        @foreach($this->reportData['module3c'] as $group)
                            <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                                <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $group['group_code'] }}: {{ $group['group_name'] }}
                                    </h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50/50 dark:bg-white/[0.02]">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Strategy</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Initiatives</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-950/5 dark:divide-white/5">
                                            @foreach($group['strategies'] as $str)
                                                <tr>
                                                    <td class="px-4 py-2.5 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $str['code'] }}</td>
                                                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $str['name'] }}</td>
                                                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold {{ $str['initiative_count'] > 0 ? 'text-green-700 dark:text-green-400' : 'text-gray-300 dark:text-gray-600' }}">
                                                        {{ $str['initiative_count'] }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-gray-500 dark:text-gray-400">Click "Generate Report" to build the CSBG Annual Report for FFY {{ $this->fiscalYear }}.</p>
        </div>
    @endif
</x-filament-panels::page>

<x-filament-panels::page>
    {{-- Header --}}
    @php $info = $this->getAgencyInfo(); @endphp
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $info['entity_name'] }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $info['state'] }} &middot; UEI: {{ $info['uei'] ?: 'Not set' }} &middot; Period: {{ $info['reporting_period'] }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Fiscal Year:</label>
                <select wire:model.live="fiscalYear" class="fi-input rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @for ($y = now()->year + 1; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}">FFY {{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="mb-6 flex flex-wrap gap-3">
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
            <span class="self-center text-sm text-gray-500 dark:text-gray-400">
                Generated: {{ $this->generatedAt }}
            </span>
        @endif
    </div>

    {{-- Report Tabs --}}
    @if($this->reportData)
        <x-filament::tabs>
            {{-- Module 4A: FNPI --}}
            <x-filament::tabs.item :active="true" alpine-active="tab === 'fnpi'" x-on:click="tab = 'fnpi'">
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

        <div x-data="{ tab: 'fnpi' }" class="mt-4">
            {{-- FNPI Tab --}}
            <div x-show="tab === 'fnpi'">
                @foreach($this->reportData['module4a'] as $goal)
                    <div class="mb-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                Goal {{ $goal['goal_number'] }}: {{ $goal['goal_name'] }}
                                <span class="ml-2 text-sm font-normal text-gray-500">({{ $goal['goal_total_clients'] }} unduplicated)</span>
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-xs uppercase text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-2">Code</th>
                                    <th class="px-4 py-2">Indicator</th>
                                    <th class="px-4 py-2 text-right">I. Served</th>
                                    <th class="px-4 py-2 text-right">II. Target</th>
                                    <th class="px-4 py-2 text-right">III. Results</th>
                                    <th class="px-4 py-2 text-right">IV. % Achieving</th>
                                    <th class="px-4 py-2 text-right">V. Accuracy</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($goal['indicators'] as $ind)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-1.5 font-mono text-xs">{{ $ind['indicator_code'] }}</td>
                                        <td class="px-4 py-1.5">{{ $ind['indicator_name'] }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($ind['individuals_served'] ?? $ind['unduplicated_clients']) }}</td>
                                        <td class="px-4 py-1.5 text-right">{{ number_format($ind['target'] ?? 0) }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold {{ ($ind['actual_results'] ?? 0) > 0 ? 'text-green-700 dark:text-green-400' : '' }}">{{ number_format($ind['actual_results'] ?? 0) }}</td>
                                        <td class="px-4 py-1.5 text-right">{{ ($ind['pct_achieving'] ?? 0) > 0 ? number_format($ind['pct_achieving'], 1) . '%' : '-' }}</td>
                                        <td class="px-4 py-1.5 text-right">{{ ($ind['target_accuracy'] ?? 0) > 0 ? number_format($ind['target_accuracy'], 1) . '%' : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Services Tab --}}
            <div x-show="tab === 'services'" x-cloak>
                @foreach($this->reportData['module4b'] as $domain)
                    <div class="mb-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ ucfirst(str_replace('_', ' ', $domain['domain'])) }}
                                <span class="ml-2 text-sm font-normal text-gray-500">({{ $domain['domain_total'] }} unduplicated)</span>
                            </h3>
                        </div>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-xs uppercase text-gray-500">
                                    <th class="px-4 py-2">Code</th>
                                    <th class="px-4 py-2">Service</th>
                                    <th class="px-4 py-2 text-right">Unduplicated</th>
                                    <th class="px-4 py-2 text-right">Total Services</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($domain['categories'] as $cat)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-1.5 font-mono text-xs">{{ $cat['code'] }}</td>
                                        <td class="px-4 py-1.5">{{ $cat['name'] }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold">{{ $cat['unduplicated_clients'] }}</td>
                                        <td class="px-4 py-1.5 text-right">{{ $cat['total_services'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>

            {{-- Characteristics Tab --}}
            <div x-show="tab === 'characteristics'" x-cloak>
                @php $chars = $this->reportData['module4c']; @endphp

                {{-- Section A & B: Totals --}}
                <div class="mb-4 flex gap-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <span class="text-sm text-gray-500">Total Unduplicated Individuals:</span>
                        <span class="ml-1 text-lg font-bold text-gray-900 dark:text-white">{{ number_format($chars['total_unduplicated_individuals'] ?? $chars['total_unduplicated'] ?? 0) }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Total Unduplicated Households:</span>
                        <span class="ml-1 text-lg font-bold text-gray-900 dark:text-white">{{ number_format($chars['total_unduplicated_households'] ?? 0) }}</span>
                    </div>
                    @if(($chars['disconnected_youth_count'] ?? 0) > 0)
                        <div>
                            <span class="text-sm text-gray-500">Disconnected Youth:</span>
                            <span class="ml-1 text-lg font-bold text-gray-900 dark:text-white">{{ number_format($chars['disconnected_youth_count']) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Section C: Individual Level --}}
                <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">C. Individual Level Characteristics</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach(['by_gender' => 'Sex', 'by_race' => 'Race', 'by_ethnicity' => 'Ethnicity', 'by_age' => 'Age', 'by_employment_status' => 'Work Status', 'by_health_insurance_status' => 'Health Insurance', 'by_health_insurance_source' => 'Health Insurance Source', 'by_military_status' => 'Military Status'] as $key => $label)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $label }}</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody>
                                    @forelse($chars[$key] ?? [] as $val => $count)
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="px-4 py-1.5">{{ \App\Services\Lookup::label(str_replace('by_', '', $key), $val) ?? ucfirst(str_replace('_', ' ', $val)) }}</td>
                                            <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-2 text-gray-400" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    {{-- Education by age split --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <h4 class="font-semibold text-gray-900 dark:text-white">Education Level (Ages 14-24)</h4>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse($chars['by_education_14_24'] ?? [] as $val => $count)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-1.5">{{ \App\Services\Lookup::label('education_level', $val) ?? ucfirst(str_replace('_', ' ', $val)) }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-2 text-gray-400" colspan="2">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <h4 class="font-semibold text-gray-900 dark:text-white">Education Level (Ages 25+)</h4>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse($chars['by_education_25_plus'] ?? [] as $val => $count)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-1.5">{{ \App\Services\Lookup::label('education_level', $val) ?? ucfirst(str_replace('_', ' ', $val)) }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-2 text-gray-400" colspan="2">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Section D: Household Level --}}
                <h3 class="mb-3 mt-6 text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">D. Household Level Characteristics</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach(['by_household_type' => 'Household Type', 'by_household_size' => 'Household Size', 'by_housing_type' => 'Housing'] as $key => $label)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $label }}</h4>
                            </div>
                            <table class="w-full text-sm">
                                <tbody>
                                    @forelse($chars[$key] ?? [] as $val => $count)
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="px-4 py-1.5">{{ ucfirst(str_replace('_', ' ', $val)) }}</td>
                                            <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($count) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-2 text-gray-400" colspan="2">No data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    {{-- FPL Brackets --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <h4 class="font-semibold text-gray-900 dark:text-white">Federal Poverty Level</h4>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse($chars['by_fpl_bracket'] ?? [] as $bracket => $count)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-1.5">{{ $bracket }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-2 text-gray-400" colspan="2">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Income Source Composite --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <h4 class="font-semibold text-gray-900 dark:text-white">Sources of Household Income</h4>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse($chars['by_income_source_composite'] ?? [] as $val => $count)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-1.5">{{ ucfirst(str_replace('_', ' ', $val)) }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-2 text-gray-400" colspan="2">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Income Source Types --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <h4 class="font-semibold text-gray-900 dark:text-white">Other Income Sources</h4>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse($chars['by_income_source_type'] ?? [] as $val => $count)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-1.5">{{ \App\Services\Lookup::label('income_source', $val) ?? ucfirst(str_replace('_', ' ', $val)) }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-2 text-gray-400" colspan="2">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Non-Cash Benefits --}}
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <h4 class="font-semibold text-gray-900 dark:text-white">Non-Cash Benefits</h4>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse($chars['by_non_cash_benefit'] ?? [] as $val => $count)
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-1.5">{{ \App\Services\Lookup::label('non_cash_benefit', $val) ?? strtoupper(str_replace('_', ' ', $val)) }}</td>
                                        <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-2 text-gray-400" colspan="2">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Expenditures Tab --}}
            <div x-show="tab === 'expenditures'" x-cloak>
                @if(empty($this->reportData['module2a']))
                    <div class="rounded-lg border border-gray-200 p-6 text-center text-gray-500 dark:border-gray-700">
                        No expenditure data for FFY {{ $this->fiscalYear }}. <a href="{{ \App\Filament\Resources\CsbgExpenditureResource::getUrl('index') }}" class="text-primary-600 underline">Add expenditures</a>.
                    </div>
                @else
                    <table class="w-full rounded-lg border text-sm">
                        <thead>
                            <tr class="border-b bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-800">
                                <th class="px-4 py-2">Domain</th>
                                <th class="px-4 py-2 text-right">CSBG Funds</th>
                                <th class="px-4 py-2">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->reportData['module2a'] as $exp)
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-1.5">{{ ucfirst(str_replace('_', ' ', $exp['domain'])) }}</td>
                                    <td class="px-4 py-1.5 text-right font-semibold">${{ number_format($exp['csbg_funds'], 2) }}</td>
                                    <td class="px-4 py-1.5 text-gray-500">{{ $exp['notes'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Capacity Building Tab --}}
            <div x-show="tab === 'capacity'" x-cloak>
                @php $cap = $this->reportData['module2b'] ?? []; @endphp
                @if(empty($cap))
                    <div class="rounded-lg border border-gray-200 p-6 text-center text-gray-500 dark:border-gray-700">
                        No capacity building data for FFY {{ $this->fiscalYear }}.
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach($cap as $type => $metrics)
                            <div class="rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ \App\Models\AgencyCapacityMetric::TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type)) }}</h4>
                                </div>
                                <table class="w-full text-sm">
                                    <tbody>
                                        @foreach($metrics as $key => $value)
                                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                                <td class="px-4 py-1.5">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                <td class="px-4 py-1.5 text-right font-semibold">{{ number_format($value, $type === 'staff_certifications' || $type === 'partner_organizations' ? 0 : 2) }}</td>
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
            <div x-show="tab === 'funding'" x-cloak>
                @php $fund = $this->reportData['module2c'] ?? []; @endphp
                @if(empty($fund['by_type'] ?? []))
                    <div class="rounded-lg border border-gray-200 p-6 text-center text-gray-500 dark:border-gray-700">
                        No funding source data for FFY {{ $this->fiscalYear }}. <a href="{{ \App\Filament\Resources\FundingSourceResource::getUrl('index') }}" class="text-primary-600 underline">Add funding sources</a>.
                    </div>
                @else
                    @foreach($fund['by_type'] as $group)
                        <div class="mb-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $group['type_label'] }}
                                    <span class="ml-2 text-sm font-normal text-gray-500">(Total: ${{ number_format($group['total'], 2) }})</span>
                                </h3>
                            </div>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-xs uppercase text-gray-500">
                                        <th class="px-4 py-2">Source</th>
                                        <th class="px-4 py-2">CFDA</th>
                                        <th class="px-4 py-2 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group['sources'] as $src)
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="px-4 py-1.5">{{ $src['source_name'] }}</td>
                                            <td class="px-4 py-1.5 text-gray-500">{{ $src['cfda_number'] ?? '' }}</td>
                                            <td class="px-4 py-1.5 text-right font-semibold">${{ number_format($src['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                    <p class="mt-3 text-sm font-bold text-gray-700 dark:text-gray-300">
                        Grand Total All Resources: ${{ number_format($fund['grand_total'] ?? 0, 2) }}
                    </p>
                @endif
            </div>

            {{-- Community Initiatives Tab --}}
            <div x-show="tab === 'initiatives'" x-cloak>
                @if(empty($this->reportData['module3']))
                    <div class="rounded-lg border border-gray-200 p-6 text-center text-gray-500 dark:border-gray-700">
                        No community initiatives for FFY {{ $this->fiscalYear }}. <a href="{{ \App\Filament\Resources\CommunityInitiativeResource::getUrl('index') }}" class="text-primary-600 underline">Add initiatives</a>.
                    </div>
                @else
                    @foreach($this->reportData['module3'] as $init)
                        <div class="mb-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $init['name'] }}</h4>
                            <p class="text-sm text-gray-500">Domain: {{ ucfirst(str_replace('_', ' ', $init['domain'])) }} &middot; Year {{ $init['year_number'] }} &middot; Status: {{ $init['progress_status'] ?? 'N/A' }}</p>
                            @if($init['problem_statement'])
                                <p class="mt-1 text-sm"><span class="font-medium">Problem:</span> {{ $init['problem_statement'] }}</p>
                            @endif
                            @if($init['goal_statement'])
                                <p class="mt-1 text-sm"><span class="font-medium">Goal:</span> {{ $init['goal_statement'] }}</p>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Community NPIs Tab --}}
            <div x-show="tab === 'cnpis'" x-cloak>
                @if(empty($this->reportData['module3b']))
                    <div class="rounded-lg border border-gray-200 p-6 text-center text-gray-500 dark:border-gray-700">
                        No community NPI data for FFY {{ $this->fiscalYear }}.
                    </div>
                @else
                    @foreach($this->reportData['module3b'] as $domain)
                        <div class="mb-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ ucfirst(str_replace('_', ' ', $domain['domain'])) }}
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-xs uppercase text-gray-500">
                                        <th class="px-4 py-2">Code</th>
                                        <th class="px-4 py-2">Indicator</th>
                                        <th class="px-4 py-2">Type</th>
                                        <th class="px-4 py-2 text-right">Target</th>
                                        <th class="px-4 py-2 text-right">Actual</th>
                                        <th class="px-4 py-2 text-right">Accuracy %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($domain['indicators'] as $ind)
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="px-4 py-1.5 font-mono text-xs">{{ $ind['code'] }}</td>
                                            <td class="px-4 py-1.5">{{ $ind['name'] }}</td>
                                            <td class="px-4 py-1.5">
                                                <span class="rounded-full px-2 py-0.5 text-xs {{ $ind['type'] === 'count_of_change' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                                    {{ $ind['type'] === 'count_of_change' ? 'Count' : 'Rate' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-1.5 text-right">{{ $ind['target'] !== null ? number_format($ind['target']) : '-' }}</td>
                                            <td class="px-4 py-1.5 text-right font-semibold">{{ $ind['actual_result'] !== null ? number_format($ind['actual_result']) : '-' }}</td>
                                            <td class="px-4 py-1.5 text-right">{{ $ind['performance_accuracy'] !== null ? number_format($ind['performance_accuracy'], 1) . '%' : '-' }}</td>
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
            <div x-show="tab === 'strategies'" x-cloak>
                @if(empty($this->reportData['module3c']))
                    <div class="rounded-lg border border-gray-200 p-6 text-center text-gray-500 dark:border-gray-700">
                        No community strategy data for FFY {{ $this->fiscalYear }}.
                    </div>
                @else
                    @foreach($this->reportData['module3c'] as $group)
                        <div class="mb-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="bg-gray-50 px-4 py-2 dark:bg-gray-800">
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $group['group_code'] }}: {{ $group['group_name'] }}
                                </h3>
                            </div>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-xs uppercase text-gray-500">
                                        <th class="px-4 py-2">Code</th>
                                        <th class="px-4 py-2">Strategy</th>
                                        <th class="px-4 py-2 text-right">Initiatives</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group['strategies'] as $str)
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="px-4 py-1.5 font-mono text-xs">{{ $str['code'] }}</td>
                                            <td class="px-4 py-1.5">{{ $str['name'] }}</td>
                                            <td class="px-4 py-1.5 text-right font-semibold {{ $str['initiative_count'] > 0 ? 'text-green-700 dark:text-green-400' : 'text-gray-400' }}">
                                                {{ $str['initiative_count'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @else
        <div class="rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">Click "Generate Report" to build the CSBG Annual Report for FFY {{ $this->fiscalYear }}.</p>
        </div>
    @endif
</x-filament-panels::page>

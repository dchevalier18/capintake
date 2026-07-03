<x-filament-panels::page>
    <div class="flex items-center gap-3">
        <x-filament::button wire:click="saveMappings" icon="heroicon-o-check">
            Save All Mappings
        </x-filament::button>

        @if(\App\Models\CsbgSrvCategory::activeReportVersion() !== '2.1')
            <x-filament::button wire:click="copyMappingsFromV21" color="gray" icon="heroicon-o-document-duplicate">
                Copy mappings from 2.1
            </x-filament::button>
        @endif
    </div>

    @php
        $serviceOptions = $this->getServiceOptions();
        $currentDomain = null;
        $currentGroup = null;
    @endphp

    <div class="space-y-8">
        @foreach($mappings as $idx => $mapping)
            @if($mapping['domain'] !== $currentDomain)
                @php $currentDomain = $mapping['domain']; $currentGroup = null; @endphp
                @if(!$loop->first)</div></div>@endif
                <div>
                    <h2 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">
                        {{ ucfirst(str_replace('_', ' ', $mapping['domain'])) }}
                    </h2>
                    <div class="space-y-4">
            @endif

            @if($mapping['group_name'] !== $currentGroup)
                @php $currentGroup = $mapping['group_name']; @endphp
                <h3 class="mt-2 text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $mapping['group_name'] }}</h3>
            @endif

            <div class="flex items-start gap-4 rounded-xl p-4 ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="w-20 shrink-0">
                    <span class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ $mapping['code'] }}</span>
                </div>
                <div class="w-64 shrink-0 text-sm text-gray-700 dark:text-gray-300">{{ $mapping['name'] }}</div>
                <div class="flex-1">
                    <select
                        wire:model="mappings.{{ $idx }}.service_ids"
                        multiple
                        class="w-full rounded-lg border-none bg-white text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
                        size="3"
                    >
                        @foreach($serviceOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endforeach
        @if($currentDomain !== null)</div></div>@endif
    </div>
</x-filament-panels::page>

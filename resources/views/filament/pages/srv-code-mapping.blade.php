<x-filament-panels::page>
    <div class="mb-4">
        <x-filament::button wire:click="saveMappings" icon="heroicon-o-check">
            Save All Mappings
        </x-filament::button>
    </div>

    @php
        $serviceOptions = $this->getServiceOptions();
        $currentDomain = null;
        $currentGroup = null;
    @endphp

    @foreach($mappings as $idx => $mapping)
        @if($mapping['domain'] !== $currentDomain)
            @php $currentDomain = $mapping['domain']; $currentGroup = null; @endphp
            <h2 class="mt-6 mb-2 text-lg font-bold text-gray-900 dark:text-white">
                {{ ucfirst(str_replace('_', ' ', $mapping['domain'])) }}
            </h2>
        @endif

        @if($mapping['group_name'] !== $currentGroup)
            @php $currentGroup = $mapping['group_name']; @endphp
            <h3 class="mt-3 mb-1 text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $mapping['group_name'] }}</h3>
        @endif

        <div class="flex items-start gap-4 border-b border-gray-100 py-2 dark:border-gray-700">
            <div class="w-20 shrink-0">
                <span class="font-mono text-xs text-gray-500">{{ $mapping['code'] }}</span>
            </div>
            <div class="w-64 shrink-0 text-sm">{{ $mapping['name'] }}</div>
            <div class="flex-1">
                <select
                    wire:model="mappings.{{ $idx }}.service_ids"
                    multiple
                    class="fi-input w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    size="3"
                >
                    @foreach($serviceOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endforeach
</x-filament-panels::page>

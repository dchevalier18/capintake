<x-filament-panels::page>
    <div class="mb-4 flex items-center gap-4">
        <label class="text-sm font-medium">Fiscal Year:</label>
        <select wire:model.live="fiscalYear" class="fi-input rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            @for ($y = now()->year + 2; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}">FFY {{ $y }}</option>
            @endfor
        </select>

        <x-filament::button wire:click="copyFromPreviousYear" color="gray" icon="heroicon-o-document-duplicate" size="sm">
            Copy from Previous Year
        </x-filament::button>

        <x-filament::button wire:click="saveTargets" icon="heroicon-o-check" size="sm">
            Save All
        </x-filament::button>
    </div>

    @php $currentGoal = null; @endphp
    @foreach($targets as $idx => $target)
        @if($target['goal_number'] !== $currentGoal)
            @php $currentGoal = $target['goal_number']; @endphp
            <div class="mt-4 mb-2 rounded-t-lg bg-gray-50 px-4 py-2 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Goal {{ $target['goal_number'] }}: {{ $target['goal_name'] }}
                </h3>
            </div>
        @endif
        <div class="flex items-center gap-4 border-b border-gray-100 px-4 py-2 dark:border-gray-700">
            <span class="w-24 font-mono text-xs text-gray-500">{{ $target['code'] }}</span>
            <span class="flex-1 text-sm">{{ $target['name'] }}</span>
            <input
                type="number"
                wire:model.lazy="targets.{{ $idx }}.target"
                min="0"
                class="fi-input w-24 rounded-lg border-gray-300 text-right text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                placeholder="0"
            >
        </div>
    @endforeach
</x-filament-panels::page>

<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Fiscal Year:</label>
        <select wire:model.live="fiscalYear" class="fi-input rounded-lg border-none bg-white py-2 pe-8 ps-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20">
            @for ($y = now()->year + 2; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}">FFY {{ $y }}</option>
            @endfor
        </select>

        <div class="flex gap-2 sm:ml-auto">
            <x-filament::button wire:click="saveMetrics" icon="heroicon-o-check" size="sm">
                Save All
            </x-filament::button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="bg-gray-50 px-4 py-3 dark:bg-white/5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                Module 2, Section B — Agency Capacity Building (FFY {{ $fiscalYear }})
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                These figures feed the Eligible Entity Capacity section of the CSBG Annual Report. Leave a value blank to omit it.
            </p>
        </div>
        <div class="divide-y divide-gray-950/5 dark:divide-white/5">
            @foreach($metrics as $idx => $metric)
                <div class="flex flex-wrap items-center gap-4 px-4 py-3">
                    <span class="w-64 shrink-0 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $metric['label'] }}</span>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        wire:model.lazy="metrics.{{ $idx }}.value"
                        class="fi-input w-32 rounded-lg border-none bg-white py-1.5 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
                        placeholder="—"
                    />
                    <input
                        type="text"
                        wire:model.lazy="metrics.{{ $idx }}.notes"
                        class="fi-input min-w-48 flex-1 rounded-lg border-none bg-white py-1.5 px-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
                        placeholder="Notes (optional)"
                    />
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

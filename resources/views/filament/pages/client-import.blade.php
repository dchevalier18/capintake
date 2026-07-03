<x-filament-panels::page>
    {{-- Step 1: Upload --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">1. Upload CSV</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Export clients from your previous system (CAP60, empowOR, or a spreadsheet) as CSV with a header row. Max 10&nbsp;MB.
        </p>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <input type="file" wire:model="csvFile" accept=".csv,.txt"
                class="text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-600 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-primary-500 dark:text-gray-300" />
            <x-filament::button wire:click="analyze" icon="heroicon-o-magnifying-glass" :disabled="! $csvFile">
                Analyze File
            </x-filament::button>
            <span wire:loading wire:target="csvFile,analyze" class="text-sm text-gray-500">Working…</span>
        </div>
        @error('csvFile') <p class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
    </div>

    {{-- Step 2: Map columns --}}
    @if(!empty($headers))
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">2. Map Columns ({{ count($rows) }} data rows found)</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Columns were auto-matched from your headers — adjust as needed. First and Last Name are required; unmapped columns are ignored.
            </p>
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach($headers as $index => $header)
                    <div class="flex items-center gap-2">
                        <span class="w-40 shrink-0 truncate text-sm font-medium text-gray-700 dark:text-gray-300" title="{{ $header }}">{{ $header }}</span>
                        <select wire:model="mapping.{{ $index }}"
                            class="fi-input flex-1 rounded-lg border-none bg-white py-1.5 pe-8 ps-3 text-sm shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20">
                            <option value="">(ignore)</option>
                            @foreach($this->targetFields as $field => $label)
                                <option value="{{ $field }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex gap-3">
                <x-filament::button wire:click="dryRun" color="gray" icon="heroicon-o-eye">
                    Preview (Dry Run)
                </x-filament::button>
            </div>
        </div>
    @endif

    {{-- Step 3: Preview + confirm --}}
    @if($preview)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">3. Preview</h3>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Would be created</p>
                    <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ $preview['created'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Duplicates skipped</p>
                    <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ $preview['skipped_duplicates'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Rows with issues</p>
                    <p class="text-lg font-bold {{ count($preview['errors']) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ count($preview['errors']) }}</p>
                </div>
            </div>

            @if(!empty($preview['errors']))
                <div class="mt-4 max-h-48 overflow-y-auto rounded-lg bg-warning-50 p-3 text-sm text-warning-800 ring-1 ring-warning-200 dark:bg-warning-900/20 dark:text-warning-200 dark:ring-warning-800">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($preview['errors'] as $rowNumber => $message)
                            <li><span class="font-medium">Row {{ $rowNumber }}:</span> {{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-4">
                <x-filament::button wire:click="import" icon="heroicon-o-check" wire:confirm="Import {{ $preview['created'] }} clients now? This cannot be undone in bulk.">
                    Import {{ $preview['created'] }} Clients
                </x-filament::button>
            </div>
        </div>
    @endif

    {{-- Result --}}
    @if($result)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-green-700 dark:text-green-400">Import complete</h3>
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                {{ $result['created'] }} clients created, {{ $result['skipped_duplicates'] }} duplicates skipped, {{ count($result['errors']) }} rows with issues.
                Imported clients are marked intake-complete; review them under <a href="{{ \App\Filament\Resources\ClientResource::getUrl('index') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Clients</a> and use the Data Quality dashboard to fill gaps.
            </p>
        </div>
    @endif
</x-filament-panels::page>

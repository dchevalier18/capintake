<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">My Follow-Ups</x-slot>

        @if(!empty($this->overdue))
            <div class="mb-4">
                <h4 class="mb-2 text-sm font-semibold text-red-600">Overdue ({{ count($this->overdue) }})</h4>
                @foreach($this->overdue as $item)
                    <div class="mb-1 flex items-center justify-between rounded border border-red-200 bg-red-50 px-3 py-2 dark:border-red-800 dark:bg-red-900/20">
                        <div>
                            <a href="{{ \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $item['client_id']]) }}" class="font-medium text-red-700 underline dark:text-red-300">{{ $item['client_name'] }}</a>
                            <span class="ml-2 text-sm text-red-600 dark:text-red-400">{{ $item['type'] }}</span>
                        </div>
                        <span class="text-xs text-red-500">{{ $item['days_overdue'] }} days overdue</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($this->upcoming))
            <h4 class="mb-2 text-sm font-semibold text-gray-600 dark:text-gray-400">Upcoming (Next 7 Days)</h4>
            @foreach($this->upcoming as $item)
                <div class="mb-1 flex items-center justify-between rounded border border-gray-200 px-3 py-2 dark:border-gray-700">
                    <div>
                        <a href="{{ \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $item['client_id']]) }}" class="font-medium text-primary-600 underline">{{ $item['client_name'] }}</a>
                        <span class="ml-2 text-sm text-gray-500">{{ $item['type'] }}</span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $item['scheduled_date'] }}</span>
                </div>
            @endforeach
        @endif

        @if(empty($this->overdue) && empty($this->upcoming))
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No upcoming or overdue follow-ups.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

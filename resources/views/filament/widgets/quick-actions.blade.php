<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-3">
                <x-filament::button
                    :href="$this->getNewIntakeUrl()"
                    tag="a"
                    icon="heroicon-o-clipboard-document-list"
                    color="primary"
                    size="lg"
                >
                    New Intake
                </x-filament::button>

                <x-filament::button
                    :href="$this->getNewServiceUrl()"
                    tag="a"
                    icon="heroicon-o-document-plus"
                    color="success"
                    size="lg"
                >
                    Record Service
                </x-filament::button>

                @if($this->getDraftCount() > 0)
                    <x-filament::button
                        :href="$this->getDraftUrl()"
                        tag="a"
                        icon="heroicon-o-pencil-square"
                        color="warning"
                        size="lg"
                    >
                        {{ $this->getDraftCount() }} Draft{{ $this->getDraftCount() > 1 ? 's' : '' }} In Progress
                    </x-filament::button>
                @endif
            </div>

            {{-- Client Search --}}
            <div class="w-full sm:w-80">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.25rem;height:1.25rem;min-width:1.25rem;">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search clients by name, SSN, or phone..."
                        class="fi-input block w-full rounded-lg border-none bg-white py-2.5 pe-3 ps-10 text-sm shadow-sm ring-1 ring-gray-950/10 transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:placeholder:text-gray-500"
                    >
                </div>

                @if(strlen($this->search) >= 2)
                    <div class="mt-2 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        @forelse($this->getSearchResults() as $client)
                            <a
                                href="{{ $this->getClientEditUrl($client->id) }}"
                                wire:navigate
                                class="flex items-center justify-between px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-gray-700 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}"
                            >
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $client->fullName() }}</span>
                                    @if($client->phone)
                                        <span class="ml-2 text-gray-500 dark:text-gray-400">{{ $client->phone }}</span>
                                    @endif
                                </div>
                                @if($client->ssn_last_four)
                                    <span class="text-xs text-gray-400">***-**-{{ $client->ssn_last_four }}</span>
                                @endif
                            </a>
                        @empty
                            <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                No clients found for "{{ $this->search }}"
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

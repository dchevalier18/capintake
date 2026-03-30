<x-filament-panels::page>
    @if($this->getDraftClients()->isNotEmpty() && !$this->clientId)
        <x-filament::section class="mb-6" icon="heroicon-o-document-text" icon-color="warning">
            <x-slot name="heading">Draft Intakes In Progress</x-slot>
            <div class="space-y-1">
                @foreach($this->getDraftClients() as $draft)
                    <a href="{{ static::getUrl(['client' => $draft->id]) }}"
                       wire:navigate
                       class="block text-sm text-primary-600 dark:text-primary-400 hover:underline">
                        {{ $draft->fullName() }} &mdash; Step {{ $draft->intake_step }} of 5
                        (started {{ $draft->created_at->diffForHumans() }})
                    </a>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    <form wire:submit="submit">
        {{ $this->form }}
    </form>
</x-filament-panels::page>

<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\IntakeStatus;
use App\Filament\Pages\IntakeWizard;
use App\Filament\Resources\ServiceRecordResource;
use App\Models\Client;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class QuickActions extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions';

    public string $search = '';

    public function getSearchResults(): Collection
    {
        if (strlen($this->search) < 2) {
            return collect();
        }

        return Client::complete()
            ->where(function ($query): void {
                $query->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('ssn_last_four', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            })
            ->limit(8)
            ->get();
    }

    public function getDraftCount(): int
    {
        return Client::draft()->count();
    }

    public function getDraftUrl(): string
    {
        $latestDraft = Client::draft()->latest()->first();

        if ($latestDraft) {
            return IntakeWizard::getUrl(['client' => $latestDraft->id]);
        }

        return IntakeWizard::getUrl();
    }

    public function getNewIntakeUrl(): string
    {
        return IntakeWizard::getUrl();
    }

    public function getNewServiceUrl(): string
    {
        return ServiceRecordResource::getUrl('create');
    }

    public function getClientEditUrl(int $clientId): string
    {
        return \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $clientId]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use Filament\Widgets\Widget;

class UpcomingFollowUps extends Widget
{
    protected string $view = 'filament.widgets.upcoming-follow-ups';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public ?array $upcoming = null;

    public ?array $overdue = null;

    public function mount(): void
    {
        $userId = auth()->id();

        $this->upcoming = FollowUp::with('client')
            ->where('assigned_to', $userId)
            ->upcoming(7)
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get()
            ->map(fn (FollowUp $f) => [
                'id' => $f->id,
                'client_name' => $f->client->fullName(),
                'client_id' => $f->client_id,
                'type' => FollowUp::TYPES[$f->follow_up_type] ?? $f->follow_up_type,
                'scheduled_date' => $f->scheduled_date->format('M j, Y'),
                'notes' => $f->notes,
            ])
            ->toArray();

        $this->overdue = FollowUp::with('client')
            ->where('assigned_to', $userId)
            ->overdue()
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get()
            ->map(fn (FollowUp $f) => [
                'id' => $f->id,
                'client_name' => $f->client->fullName(),
                'client_id' => $f->client_id,
                'type' => FollowUp::TYPES[$f->follow_up_type] ?? $f->follow_up_type,
                'scheduled_date' => $f->scheduled_date->format('M j, Y'),
                'days_overdue' => $f->scheduled_date->diffInDays(now()),
            ])
            ->toArray();
    }
}

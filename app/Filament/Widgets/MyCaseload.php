<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Enums\IntakeStatus;
use App\Models\Client;
use App\Models\Enrollment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyCaseload extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'My Caseload';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Enrollment::query()
                    ->where('caseworker_id', Auth::id())
                    ->where('status', EnrollmentStatus::Active)
                    ->with(['client', 'program', 'serviceRecords'])
            )
            ->columns([
                TextColumn::make('client.first_name')
                    ->label('Client')
                    ->formatStateUsing(fn ($record): string => $record->client?->fullName() ?? 'N/A')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'client',
                        fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                    ))
                    ->url(fn ($record): ?string => $record->client
                        ? \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $record->client])
                        : null
                    ),

                TextColumn::make('program.name')
                    ->label('Program')
                    ->limit(25),

                TextColumn::make('enrolled_at')
                    ->label('Enrolled')
                    ->date('m/d/Y')
                    ->sortable(),

                TextColumn::make('last_service')
                    ->label('Last Service')
                    ->state(function ($record): string {
                        $latest = $record->serviceRecords()->latest('service_date')->first();

                        return $latest
                            ? $latest->service_date->format('m/d/Y')
                            : 'No services yet';
                    })
                    ->color(function ($record): string {
                        $latest = $record->serviceRecords()->latest('service_date')->first();
                        if (! $latest) {
                            return 'danger';
                        }

                        return $latest->service_date->lt(now()->subDays(30)) ? 'warning' : 'success';
                    }),

                TextColumn::make('service_count')
                    ->label('Services')
                    ->state(fn ($record): int => $record->serviceRecords()->count())
                    ->alignCenter(),
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->emptyStateHeading('No active enrollments')
            ->emptyStateDescription('Clients assigned to you will appear here. Start by creating a new intake.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateActions([
                \Filament\Actions\Action::make('new_intake')
                    ->label('New Intake')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->url(\App\Filament\Pages\IntakeWizard::getUrl()),
            ])
            ->paginated([5, 10, 25]);
    }
}

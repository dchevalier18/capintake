<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\IncomeFrequency;
use App\Filament\Resources\ClientResource;
use App\Models\Enrollment;
use App\Models\IncomeRecord;
use App\Models\Service;
use App\Models\ServiceRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function resolveRecord(int | string $key): Model
    {
        $record = parent::resolveRecord($key);

        $record->load([
            'household.clients',
            'household.members.incomeRecords',
            'enrollments.program',
            'enrollments.caseworker',
            'serviceRecords.service',
            'serviceRecords.enrollment.program',
            'serviceRecords.provider',
            'incomeRecords',
        ]);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordService')
                ->label('Record Service')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Select::make('enrollment_id')
                        ->label('Enrollment')
                        ->options(fn (): array => $this->getRecord()
                            ->activeEnrollments()
                            ->with('program')
                            ->get()
                            ->mapWithKeys(fn (Enrollment $e): array => [
                                $e->id => $e->program->name . ' (' . $e->enrolled_at->format('m/d/Y') . ')',
                            ])
                            ->toArray())
                        ->required(),
                    Select::make('service_id')
                        ->label('Service')
                        ->options(fn (): array => Service::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray())
                        ->required()
                        ->searchable(),
                    Select::make('provided_by')
                        ->label('Provider')
                        ->options(fn (): array => \App\Models\User::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray())
                        ->searchable(),
                    DatePicker::make('service_date')
                        ->default(now())
                        ->required(),
                    TextInput::make('quantity')
                        ->numeric()
                        ->default(1),
                    TextInput::make('value')
                        ->numeric()
                        ->prefix('$'),
                    Textarea::make('notes'),
                ])
                ->action(function (array $data): void {
                    ServiceRecord::create([
                        'client_id' => $this->getRecord()->id,
                        'enrollment_id' => $data['enrollment_id'],
                        'service_id' => $data['service_id'],
                        'provided_by' => $data['provided_by'] ?? auth()->id(),
                        'service_date' => $data['service_date'],
                        'quantity' => $data['quantity'] ?? 1,
                        'value' => $data['value'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);
                }),

            Action::make('newEnrollment')
                ->label('New Enrollment')
                ->icon('heroicon-o-academic-cap')
                ->color('primary')
                ->form([
                    Select::make('program_id')
                        ->label('Program')
                        ->options(fn (): array => \App\Models\Program::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray())
                        ->required()
                        ->searchable(),
                    Select::make('caseworker_id')
                        ->label('Caseworker')
                        ->options(fn (): array => \App\Models\User::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray())
                        ->searchable(),
                    DatePicker::make('enrolled_at')
                        ->label('Enrollment Date')
                        ->default(now())
                        ->required(),
                    Select::make('status')
                        ->options(collect(EnrollmentStatus::cases())
                            ->mapWithKeys(fn (EnrollmentStatus $s): array => [$s->value => $s->label()])
                            ->toArray())
                        ->default(EnrollmentStatus::Active->value)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $enrollment = Enrollment::create([
                        'client_id' => $this->getRecord()->id,
                        'program_id' => $data['program_id'],
                        'caseworker_id' => $data['caseworker_id'] ?? auth()->id(),
                        'enrolled_at' => $data['enrolled_at'],
                        'status' => $data['status'],
                    ]);

                    $enrollment->snapshotEligibility();
                }),

            Action::make('updateIncome')
                ->label('Update Income')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->form([
                    Select::make('source')
                        ->options(fn (): array => \App\Services\Lookup::options('income_source'))
                        ->required()
                        ->searchable(),
                    TextInput::make('amount')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    Select::make('frequency')
                        ->options(collect(IncomeFrequency::cases())
                            ->mapWithKeys(fn (IncomeFrequency $f): array => [$f->value => $f->label()])
                            ->toArray())
                        ->required(),
                    DatePicker::make('effective_date')
                        ->default(now())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    IncomeRecord::create([
                        'client_id' => $this->getRecord()->id,
                        'source' => $data['source'],
                        'amount' => $data['amount'],
                        'frequency' => $data['frequency'],
                        'effective_date' => $data['effective_date'],
                    ]);
                }),

            Action::make('editClient')
                ->label('Edit Client')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => ClientResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}

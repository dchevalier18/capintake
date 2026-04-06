<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Models\Program;
use App\Services\Lookup;
use App\Models\Household;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Client Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'last_name';

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->fullName();
    }

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        return $record?->fullName();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('middle_name')
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),

                        DatePicker::make('date_of_birth')
                            ->required()
                            ->maxDate(now()),

                        TextInput::make('ssn_encrypted')
                            ->label('SSN')
                            ->password()
                            ->revealable()
                            ->maxLength(11)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ?: null),

                        TextInput::make('ssn_last_four')
                            ->label('SSN Last 4')
                            ->maxLength(4)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Section::make('Contact Information')
                    ->schema([
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        Select::make('preferred_language')
                            ->options([
                                'en' => 'English',
                                'es' => 'Spanish',
                                'zh' => 'Chinese',
                                'vi' => 'Vietnamese',
                                'ar' => 'Arabic',
                                'other' => 'Other',
                            ])
                            ->default('en'),
                    ])
                    ->columns(3),

                Section::make('Demographics')
                    ->schema([
                        Select::make('gender')
                            ->options(fn () => Lookup::options('gender')),

                        Select::make('race')
                            ->options(fn () => Lookup::options('race')),

                        Select::make('ethnicity')
                            ->options(fn () => Lookup::options('ethnicity')),

                        Toggle::make('is_veteran')
                            ->label('Veteran')
                            ->default(false),

                        Toggle::make('is_disabled')
                            ->label('Disabled')
                            ->default(false),

                        Toggle::make('is_head_of_household')
                            ->label('Head of Household')
                            ->default(false),

                        TextInput::make('relationship_to_head')
                            ->label('Relationship to Head of Household')
                            ->maxLength(100),
                    ])
                    ->columns(3),

                Section::make('Household Assignment')
                    ->schema([
                        Select::make('household_id')
                            ->label('Household')
                            ->relationship('household', 'address_line_1')
                            ->getOptionLabelFromRecordUsing(fn (Household $record): string => $record->fullAddress())
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('address_line_1')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('address_line_2')
                                    ->maxLength(255),
                                TextInput::make('city')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('state')
                                    ->required()
                                    ->maxLength(2)
                                    ->default('PA'),
                                TextInput::make('zip')
                                    ->required()
                                    ->maxLength(10),
                                TextInput::make('county')
                                    ->maxLength(255),
                                TextInput::make('household_size')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),
                            ]),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                // --- Header: Name, contact, flags ---
                Section::make()
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Name')
                            ->getStateUsing(fn (Client $record): string => $record->fullName())
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('age_dob')
                            ->label('Age / DOB')
                            ->getStateUsing(fn (Client $record): string => $record->date_of_birth
                                ? 'Age ' . $record->age() . ' (DOB: ' . $record->date_of_birth->format('m/d/Y') . ')'
                                : 'Unknown'),
                        TextEntry::make('phone')
                            ->label('Phone'),
                        TextEntry::make('email')
                            ->label('Email'),
                        IconEntry::make('is_veteran')
                            ->label('Veteran')
                            ->boolean()
                            ->visible(fn (Client $record): bool => (bool) $record->is_veteran),
                        IconEntry::make('is_disabled')
                            ->label('Disabled')
                            ->boolean()
                            ->visible(fn (Client $record): bool => (bool) $record->is_disabled),
                    ])
                    ->columns(6)
                    ->compact()
                    ->columnSpanFull(),

                // --- Two-column: Household + Income ---
                Flex::make([
                    Group::make([
                        // Household section
                        Section::make('Household')
                            ->schema([
                                TextEntry::make('household_address')
                                    ->label('Address')
                                    ->getStateUsing(fn (Client $record): string => $record->household?->fullAddress() ?? 'No household assigned'),
                                TextEntry::make('household.housing_type')
                                    ->label('Housing Type')
                                    ->formatStateUsing(fn (?string $state): string => $state ? Lookup::label('housing_type', $state) ?? ucfirst(str_replace('_', ' ', $state)) : '—'),
                                TextEntry::make('household.county')
                                    ->label('County')
                                    ->default('—'),
                                TextEntry::make('household.household_size')
                                    ->label('Household Size'),
                                RepeatableEntry::make('household.members')
                                    ->label('Household Members')
                                    ->schema([
                                        TextEntry::make('full_name')
                                            ->label('Name')
                                            ->getStateUsing(fn ($record): string => $record->fullName()),
                                        TextEntry::make('relationship_to_client')
                                            ->label('Relationship'),
                                        TextEntry::make('age')
                                            ->label('Age')
                                            ->getStateUsing(function ($record): string {
                                                if ($record->age() !== null) {
                                                    return (string) $record->age();
                                                }
                                                if ($record->birth_year) {
                                                    return (string) (now()->year - $record->birth_year);
                                                }
                                                return '—';
                                            }),
                                    ])
                                    ->columns(3),
                            ])
                            ->compact(),

                        // Demographics section
                        Section::make('Demographics')
                            ->schema([
                                TextEntry::make('gender')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? Lookup::label('gender', $state) ?? ucfirst(str_replace('_', ' ', $state)) : '—'),
                                TextEntry::make('race')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? Lookup::label('race', $state) ?? ucfirst(str_replace('_', ' ', $state)) : '—'),
                                TextEntry::make('ethnicity')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? Lookup::label('ethnicity', $state) ?? ucfirst(str_replace('_', ' ', $state)) : '—'),
                                TextEntry::make('preferred_language')
                                    ->label('Language')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'en' => 'English',
                                        'es' => 'Spanish',
                                        'zh' => 'Chinese',
                                        'vi' => 'Vietnamese',
                                        'ar' => 'Arabic',
                                        default => $state ?? '—',
                                    }),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->collapsed()
                            ->compact(),
                    ]),

                    Group::make([
                        // Income & Eligibility section
                        Section::make('Income & Eligibility')
                            ->schema([
                                TextEntry::make('total_income')
                                    ->label('Total Household Income')
                                    ->getStateUsing(fn (Client $record): string => '$' . number_format($record->household?->totalAnnualIncome() ?? 0, 2)),
                                TextEntry::make('hh_size')
                                    ->label('HH Size')
                                    ->getStateUsing(fn (Client $record): string => (string) ($record->household?->household_size ?? '—')),
                                TextEntry::make('fpl_percent')
                                    ->label('FPL %')
                                    ->badge()
                                    ->getStateUsing(function (Client $record): string {
                                        $fpl = null;
                                        try {
                                            $fpl = $record->fplPercent();
                                        } catch (\Throwable) {
                                        }

                                        return $fpl !== null ? $fpl . '%' : 'N/A';
                                    })
                                    ->color(function (Client $record): string {
                                        $fpl = null;
                                        try {
                                            $fpl = $record->fplPercent();
                                        } catch (\Throwable) {
                                        }

                                        if ($fpl === null) {
                                            return 'gray';
                                        }

                                        return match (true) {
                                            $fpl <= 100 => 'success',
                                            $fpl <= 150 => 'warning',
                                            default => 'danger',
                                        };
                                    }),
                                RepeatableEntry::make('incomeRecords')
                                    ->label('Income Sources')
                                    ->schema([
                                        TextEntry::make('source')
                                            ->formatStateUsing(fn (?string $state): string => $state ? Lookup::label('income_source', $state) ?? ucfirst(str_replace('_', ' ', $state)) : '—'),
                                        TextEntry::make('annual_amount')
                                            ->label('Annual')
                                            ->formatStateUsing(fn ($state): string => '$' . number_format((float) $state, 2)),
                                        TextEntry::make('frequency')
                                            ->formatStateUsing(fn ($state): string => $state instanceof \App\Enums\IncomeFrequency ? $state->label() : (string) ($state ?? '—')),
                                    ])
                                    ->table([
                                        TableColumn::make('Source'),
                                        TableColumn::make('Annual'),
                                        TableColumn::make('Frequency'),
                                    ]),
                                TextEntry::make('income_last_updated')
                                    ->label('Income Last Updated')
                                    ->getStateUsing(function (Client $record): string {
                                        $latest = $record->incomeRecords->sortByDesc('updated_at')->first();

                                        return $latest ? $latest->updated_at->format('m/d/Y') : 'No records';
                                    }),
                            ])
                            ->compact(),
                    ]),
                ])
                    ->columnSpanFull(),

                // --- Active Enrollments ---
                Section::make('Active Enrollments')
                    ->schema([
                        RepeatableEntry::make('enrollments')
                            ->label('')
                            ->getStateUsing(fn (Client $record): array => $record->enrollments
                                ->filter(fn ($e) => in_array($e->status, [EnrollmentStatus::Active, EnrollmentStatus::Pending]))
                                ->values()
                                ->toArray())
                            ->schema([
                                TextEntry::make('program.name')
                                    ->label('Program'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state instanceof EnrollmentStatus ? $state->label() : ucfirst((string) $state))
                                    ->color(fn ($state): string => match (true) {
                                        $state === EnrollmentStatus::Active, $state === 'active' => 'success',
                                        $state === EnrollmentStatus::Pending, $state === 'pending' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('enrolled_at')
                                    ->label('Enrolled')
                                    ->date('m/d/Y'),
                                TextEntry::make('caseworker.name')
                                    ->label('Caseworker'),
                                TextEntry::make('fpl_percent_at_enrollment')
                                    ->label('FPL% at Enrollment')
                                    ->suffix('%'),
                            ])
                            ->columns(5),
                    ])
                    ->compact()
                    ->columnSpanFull(),

                // --- Recent Services ---
                Section::make('Recent Services')
                    ->schema([
                        RepeatableEntry::make('serviceRecords')
                            ->label('')
                            ->getStateUsing(fn (Client $record): array => $record->serviceRecords
                                ->sortByDesc('service_date')
                                ->take(10)
                                ->values()
                                ->toArray())
                            ->schema([
                                TextEntry::make('service_date')
                                    ->label('Date')
                                    ->date('m/d/Y'),
                                TextEntry::make('service.name')
                                    ->label('Service'),
                                TextEntry::make('enrollment.program.name')
                                    ->label('Program'),
                                TextEntry::make('provider.name')
                                    ->label('Provider'),
                                TextEntry::make('value')
                                    ->label('Value')
                                    ->formatStateUsing(fn ($state): string => $state ? '$' . number_format((float) $state, 2) : '—'),
                            ])
                            ->columns(5),
                    ])
                    ->compact()
                    ->columnSpanFull(),

                // --- Notes (collapsed) ---
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->default('No notes recorded.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

                // --- Completed/Archived Enrollments (collapsed) ---
                Section::make('Completed / Archived Enrollments')
                    ->schema([
                        RepeatableEntry::make('enrollments')
                            ->label('')
                            ->getStateUsing(fn (Client $record): array => $record->enrollments
                                ->filter(fn ($e) => in_array($e->status, [EnrollmentStatus::Completed, EnrollmentStatus::Withdrawn, EnrollmentStatus::Denied]))
                                ->values()
                                ->toArray())
                            ->schema([
                                TextEntry::make('program.name')
                                    ->label('Program'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state instanceof EnrollmentStatus ? $state->label() : ucfirst((string) $state)),
                                TextEntry::make('enrolled_at')
                                    ->label('Enrolled')
                                    ->date('m/d/Y'),
                                TextEntry::make('completed_at')
                                    ->label('Completed')
                                    ->date('m/d/Y'),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->state(fn (Client $record): string => $record->fullName())
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($query) use ($search): void {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('ssn_last_four', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderBy('last_name', $direction)
                            ->orderBy('first_name', $direction);
                    }),

                TextColumn::make('age')
                    ->label('Age')
                    ->getStateUsing(fn (Client $record): ?string => $record->age() !== null ? (string) $record->age() : null),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('household.address_line_1')
                    ->label('Address')
                    ->limit(30),

                TextColumn::make('active_programs')
                    ->label('Active Programs')
                    ->badge()
                    ->getStateUsing(fn (Client $record): array => $record->activeEnrollments
                        ->map(fn ($e) => $e->program->name)
                        ->toArray()),

                TextColumn::make('fpl_percent')
                    ->label('FPL %')
                    ->badge()
                    ->getStateUsing(function (Client $record): string {
                        $fpl = null;
                        try {
                            $fpl = $record->fplPercent();
                        } catch (\Throwable) {
                        }

                        return $fpl !== null ? $fpl . '%' : 'N/A';
                    })
                    ->color(function (Client $record): string {
                        $fpl = null;
                        try {
                            $fpl = $record->fplPercent();
                        } catch (\Throwable) {
                        }

                        if ($fpl === null) {
                            return 'gray';
                        }

                        return match (true) {
                            $fpl <= 100 => 'success',
                            $fpl <= 150 => 'warning',
                            default => 'danger',
                        };
                    }),

                TextColumn::make('last_service_date')
                    ->label('Last Service')
                    ->getStateUsing(fn (Client $record): ?string => $record->serviceRecords
                        ->sortByDesc('service_date')
                        ->first()
                        ?->service_date
                        ?->format('m/d/Y'))
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('program')
                    ->label('Program')
                    ->options(fn (): array => Program::where('is_active', true)->pluck('name', 'id')->toArray())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn (Builder $q, string $programId): Builder => $q->whereHas(
                            'activeEnrollments',
                            fn (Builder $eq): Builder => $eq->where('program_id', $programId)
                        )
                    )),

                TernaryFilter::make('has_active_enrollment')
                    ->label('Has Active Enrollment')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('activeEnrollments'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('activeEnrollments'),
                    ),

                SelectFilter::make('county')
                    ->label('County')
                    ->options(fn (): array => \App\Models\Household::whereNotNull('county')
                        ->where('county', '!=', '')
                        ->distinct()
                        ->orderBy('county')
                        ->pluck('county', 'county')
                        ->toArray())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn (Builder $q, string $county): Builder => $q->whereHas(
                            'household',
                            fn (Builder $hq): Builder => $hq->where('county', $county)
                        )
                    )),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
            RelationManagers\ServiceRecordsRelationManager::class,
            RelationManagers\IncomeRecordsRelationManager::class,
            RelationManagers\OutcomesRelationManager::class,
            RelationManagers\CasePlansRelationManager::class,
            RelationManagers\ReferralsRelationManager::class,
            RelationManagers\FollowUpsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('intake_status', \App\Enums\IntakeStatus::Complete)
            ->with(['activeEnrollments.program', 'serviceRecords', 'household']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}

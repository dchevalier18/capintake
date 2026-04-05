<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\IntakeStatus;
use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Services\Lookup;
use App\Models\Household;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
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
                                ->orWhere('middle_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderBy('last_name', $direction)
                            ->orderBy('first_name', $direction);
                    }),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('household.address_line_1')
                    ->label('Address')
                    ->limit(30),

                BooleanColumn::make('is_veteran')
                    ->label('Veteran'),

                BooleanColumn::make('is_disabled')
                    ->label('Disabled'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_veteran')
                    ->label('Veteran Status'),

                TernaryFilter::make('is_disabled')
                    ->label('Disability Status'),
            ])
            ->actions([
                EditAction::make(),
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
        return parent::getEloquentQuery()->where('intake_status', IntakeStatus::Complete);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}

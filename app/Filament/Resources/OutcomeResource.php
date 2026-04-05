<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\IntakeStatus;
use App\Enums\OutcomeStatus;
use App\Filament\Resources\OutcomeResource\Pages;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\Outcome;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OutcomeResource extends Resource
{
    protected static ?string $model = Outcome::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static string|\UnitEnum|null $navigationGroup = 'Program Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Outcomes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Outcome Details')
                    ->schema([
                        Select::make('client_id')
                            ->label('Client')
                            ->relationship(
                                'client',
                                'first_name',
                                fn ($query) => $query->where('intake_status', IntakeStatus::Complete),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Client $record): string => $record->fullName())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),

                        Select::make('npi_indicator_id')
                            ->label('NPI Indicator')
                            ->relationship('indicator', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->indicator_code} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('enrollment_id')
                            ->label('Enrollment')
                            ->relationship(
                                'enrollment',
                                'id',
                                fn ($query, Get $get) => $query->when(
                                    $get('client_id'),
                                    fn ($q, $clientId) => $q->where('client_id', $clientId),
                                ),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Enrollment $record): string => "#{$record->id} - {$record->program->name} ({$record->status->label()})")
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->options(collect(OutcomeStatus::cases())->mapWithKeys(
                                fn (OutcomeStatus $s) => [$s->value => $s->label()]
                            ))
                            ->required()
                            ->default(OutcomeStatus::InProgress->value),
                    ])
                    ->columns(2),

                Section::make('Dates & Progress')
                    ->schema([
                        DatePicker::make('achieved_date')
                            ->label('Date Achieved'),

                        DatePicker::make('target_date')
                            ->label('Target Date'),

                        TextInput::make('baseline_value')
                            ->label('Baseline (Before)')
                            ->maxLength(255),

                        TextInput::make('result_value')
                            ->label('Result (After)')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.first_name')
                    ->label('Client')
                    ->formatStateUsing(fn ($record): string => $record->client->fullName())
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('client', function ($query) use ($search): void {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('indicator.indicator_code')
                    ->label('NPI Code')
                    ->sortable(),

                TextColumn::make('indicator.name')
                    ->label('Indicator')
                    ->limit(40)
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (OutcomeStatus $state): string => match ($state) {
                        OutcomeStatus::Achieved => 'success',
                        OutcomeStatus::Maintained => 'success',
                        OutcomeStatus::InProgress => 'warning',
                        OutcomeStatus::NotAchieved => 'danger',
                    })
                    ->formatStateUsing(fn (OutcomeStatus $state): string => $state->label()),

                TextColumn::make('achieved_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('fiscal_year')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(OutcomeStatus::cases())->mapWithKeys(
                        fn (OutcomeStatus $s) => [$s->value => $s->label()]
                    )),

                SelectFilter::make('fiscal_year')
                    ->options(fn () => Outcome::query()
                        ->distinct()
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->mapWithKeys(fn ($fy) => [$fy => "FY {$fy}"])
                        ->toArray()
                    ),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutcomes::route('/'),
            'create' => Pages\CreateOutcome::route('/create'),
            'edit' => Pages\EditOutcome::route('/{record}/edit'),
        ];
    }
}

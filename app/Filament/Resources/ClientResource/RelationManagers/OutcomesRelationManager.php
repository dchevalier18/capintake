<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Enums\OutcomeStatus;
use App\Models\NpiGoal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OutcomesRelationManager extends RelationManager
{
    protected static string $relationship = 'outcomes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('npi_indicator_id')
                    ->label('NPI Indicator')
                    ->relationship('indicator', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->indicator_code} - {$record->name}")
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('enrollment_id')
                    ->label('Enrollment')
                    ->relationship('enrollment', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "#{$record->id} - {$record->program->name}")
                    ->searchable()
                    ->preload(),

                Select::make('status')
                    ->options(collect(OutcomeStatus::cases())->mapWithKeys(
                        fn (OutcomeStatus $s) => [$s->value => $s->label()]
                    ))
                    ->required()
                    ->default(OutcomeStatus::InProgress->value),

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

                Textarea::make('notes')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
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
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(OutcomeStatus::cases())->mapWithKeys(
                        fn (OutcomeStatus $s) => [$s->value => $s->label()]
                    )),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('achieved_date', 'desc');
    }
}

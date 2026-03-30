<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Enums\EnrollmentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('program_id')
                    ->relationship('program', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('caseworker_id')
                    ->relationship('caseworker', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('status')
                    ->options(EnrollmentStatus::class)
                    ->required()
                    ->default(EnrollmentStatus::Pending),

                DatePicker::make('enrolled_at')
                    ->required()
                    ->default(now()),

                DatePicker::make('completed_at'),

                Toggle::make('income_eligible')
                    ->default(false),

                TextInput::make('household_income_at_enrollment')
                    ->label('Household Income')
                    ->numeric()
                    ->prefix('$'),

                TextInput::make('household_size_at_enrollment')
                    ->label('Household Size')
                    ->numeric()
                    ->minValue(1),

                TextInput::make('fpl_percent_at_enrollment')
                    ->label('FPL %')
                    ->numeric()
                    ->suffix('%'),

                Textarea::make('eligibility_notes')
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Textarea::make('denial_reason')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('program.name')
                    ->sortable(),

                TextColumn::make('caseworker.name')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('enrolled_at')
                    ->date()
                    ->sortable(),

                BooleanColumn::make('income_eligible')
                    ->label('Eligible'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

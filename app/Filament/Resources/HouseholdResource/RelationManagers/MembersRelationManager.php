<?php

declare(strict_types=1);

namespace App\Filament\Resources\HouseholdResource\RelationManagers;

use App\Models\HouseholdMember;
use App\Services\Lookup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('date_of_birth')
                    ->maxDate(now()),

                Select::make('gender')
                    ->options(fn () => Lookup::options('gender')),

                Select::make('race')
                    ->options(fn () => Lookup::options('race')),

                Select::make('ethnicity')
                    ->options(fn () => Lookup::options('ethnicity')),

                Select::make('relationship_to_client')
                    ->label('Relationship')
                    ->options(fn () => Lookup::options('relationship_to_head'))
                    ->required(),

                Select::make('employment_status')
                    ->options(fn () => Lookup::options('employment_status')),

                Toggle::make('is_veteran')
                    ->label('Veteran')
                    ->default(false),

                Toggle::make('is_disabled')
                    ->label('Disabled')
                    ->default(false),

                Toggle::make('is_student')
                    ->label('Student')
                    ->default(false),

                Select::make('education_level')
                    ->options(fn () => Lookup::options('education_level')),

                Select::make('health_insurance')
                    ->label('Health Insurance')
                    ->options(fn () => Lookup::options('health_insurance_source')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->state(fn (HouseholdMember $record): string => $record->fullName()),

                TextColumn::make('relationship_to_client')
                    ->label('Relationship'),

                TextColumn::make('date_of_birth')
                    ->date(),

                TextColumn::make('employment_status')
                    ->badge(),

                BooleanColumn::make('is_veteran')
                    ->label('Veteran'),

                BooleanColumn::make('is_disabled')
                    ->label('Disabled'),
            ])
            ->filters([
                //
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
            ]);
    }
}

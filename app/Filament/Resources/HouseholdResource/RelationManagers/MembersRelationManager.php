<?php

declare(strict_types=1);

namespace App\Filament\Resources\HouseholdResource\RelationManagers;

use App\Models\HouseholdMember;
use App\Services\Lookup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
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

                Select::make('health_insurance_status')
                    ->label('Health Insurance')
                    ->options(fn () => Lookup::options('health_insurance_status'))
                    ->live(),

                Select::make('health_insurance_source')
                    ->label('Insurance Source')
                    ->options(fn () => Lookup::options('health_insurance_source'))
                    ->visible(fn (Get $get): bool => $get('health_insurance_status') === 'yes'),

                Select::make('military_status')
                    ->label('Military Status')
                    ->options(fn () => Lookup::options('military_status')),
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
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

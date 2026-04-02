<?php

declare(strict_types=1);

namespace App\Filament\Resources\HouseholdResource\RelationManagers;

use App\Enums\EmploymentStatus;
use App\Models\HouseholdMember;
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
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'non_binary' => 'Non-Binary',
                        'other' => 'Other',
                        'prefer_not_to_say' => 'Prefer Not to Say',
                    ]),

                Select::make('race')
                    ->options([
                        'white' => 'White',
                        'black' => 'Black or African American',
                        'asian' => 'Asian',
                        'native_american' => 'American Indian or Alaska Native',
                        'pacific_islander' => 'Native Hawaiian or Pacific Islander',
                        'multi_racial' => 'Two or More Races',
                        'other' => 'Other',
                    ]),

                Select::make('ethnicity')
                    ->options([
                        'hispanic' => 'Hispanic or Latino',
                        'not_hispanic' => 'Not Hispanic or Latino',
                    ]),

                TextInput::make('relationship_to_client')
                    ->required()
                    ->maxLength(100),

                Select::make('employment_status')
                    ->options(EmploymentStatus::class),

                Toggle::make('is_veteran')
                    ->label('Veteran')
                    ->default(false),

                Toggle::make('is_disabled')
                    ->label('Disabled')
                    ->default(false),

                Toggle::make('is_student')
                    ->label('Student')
                    ->default(false),

                TextInput::make('education_level')
                    ->maxLength(100),

                TextInput::make('health_insurance')
                    ->maxLength(255),
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

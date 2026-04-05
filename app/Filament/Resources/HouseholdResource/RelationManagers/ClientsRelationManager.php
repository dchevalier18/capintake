<?php

declare(strict_types=1);

namespace App\Filament\Resources\HouseholdResource\RelationManagers;

use App\Models\Client;
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

class ClientsRelationManager extends RelationManager
{
    protected static string $relationship = 'clients';

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

                TextInput::make('middle_name')
                    ->maxLength(255),

                DatePicker::make('date_of_birth')
                    ->required()
                    ->maxDate(now()),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                TextInput::make('email')
                    ->email()
                    ->maxLength(255),

                Select::make('gender')
                    ->options(fn () => Lookup::options('gender')),

                Toggle::make('is_head_of_household')
                    ->label('Head of Household')
                    ->default(false),

                TextInput::make('relationship_to_head')
                    ->maxLength(100),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->state(fn (Client $record): string => $record->fullName())
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($query) use ($search): void {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('phone'),

                TextColumn::make('email'),

                BooleanColumn::make('is_head_of_household')
                    ->label('HOH'),

                TextColumn::make('relationship_to_head')
                    ->label('Relationship'),
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

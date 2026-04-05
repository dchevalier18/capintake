<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\HouseholdResource\Pages;
use App\Filament\Resources\HouseholdResource\RelationManagers;
use App\Models\Household;
use App\Services\Lookup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HouseholdResource extends Resource
{
    protected static ?string $model = Household::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|\UnitEnum|null $navigationGroup = 'Client Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'address_line_1';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Address')
                    ->schema([
                        TextInput::make('address_line_1')
                            ->label('Address Line 1')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('address_line_2')
                            ->label('Address Line 2')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('state')
                            ->required()
                            ->maxLength(2)
                            ->default('PA'),

                        TextInput::make('zip')
                            ->label('ZIP Code')
                            ->required()
                            ->maxLength(10),

                        TextInput::make('county')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Section::make('Household Details')
                    ->schema([
                        Select::make('housing_type')
                            ->options(fn () => Lookup::options('housing_type')),

                        TextInput::make('household_size')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(20),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('address_line_1')
                    ->label('Address')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('city')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('county')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('household_size')
                    ->label('Size')
                    ->sortable(),

                TextColumn::make('housing_type')
                    ->label('Housing Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('clients_count')
                    ->label('Clients')
                    ->counts('clients')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('housing_type')
                    ->options(fn () => Lookup::options('housing_type')),

                SelectFilter::make('county')
                    ->options(fn (): array => Household::query()
                        ->whereNotNull('county')
                        ->distinct()
                        ->pluck('county', 'county')
                        ->toArray()),
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
            RelationManagers\ClientsRelationManager::class,
            RelationManagers\MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHouseholds::route('/'),
            'create' => Pages\CreateHousehold::route('/create'),
            'edit' => Pages\EditHousehold::route('/{record}/edit'),
        ];
    }
}

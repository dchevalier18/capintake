<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceRecords';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('enrollment_id')
                    ->relationship('enrollment', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "#{$record->id} - {$record->program->name}")
                    ->searchable()
                    ->preload(),

                Select::make('provided_by')
                    ->relationship('provider', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                DatePicker::make('service_date')
                    ->required()
                    ->default(now()),

                TextInput::make('quantity')
                    ->numeric()
                    ->minValue(0),

                TextInput::make('value')
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0),

                Textarea::make('notes')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('service.name')
                    ->sortable(),

                TextColumn::make('provider.name')
                    ->label('Provider')
                    ->sortable(),

                TextColumn::make('service_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('value')
                    ->money('USD'),
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

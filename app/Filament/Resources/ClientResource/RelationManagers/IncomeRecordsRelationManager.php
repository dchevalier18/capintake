<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Enums\IncomeFrequency;
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

class IncomeRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'incomeRecords';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('source')
                    ->options([
                        'employment' => 'Employment',
                        'self_employment' => 'Self-Employment',
                        'social_security' => 'Social Security',
                        'ssi' => 'SSI',
                        'ssdi' => 'SSDI',
                        'pension' => 'Pension',
                        'unemployment' => 'Unemployment',
                        'child_support' => 'Child Support',
                        'alimony' => 'Alimony',
                        'tanf' => 'TANF',
                        'other' => 'Other',
                    ])
                    ->required(),

                TextInput::make('source_description')
                    ->maxLength(255),

                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->minValue(0),

                Select::make('frequency')
                    ->options(IncomeFrequency::class)
                    ->required(),

                TextInput::make('annual_amount')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),

                Toggle::make('is_verified')
                    ->default(false),

                TextInput::make('verification_method')
                    ->maxLength(255),

                DatePicker::make('verified_at'),

                DatePicker::make('effective_date'),

                DatePicker::make('expiration_date'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('source')
            ->columns([
                TextColumn::make('source')
                    ->sortable(),

                TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('frequency')
                    ->badge()
                    ->sortable(),

                TextColumn::make('annual_amount')
                    ->money('USD')
                    ->sortable(),

                BooleanColumn::make('is_verified')
                    ->label('Verified'),

                TextColumn::make('effective_date')
                    ->date()
                    ->sortable(),
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

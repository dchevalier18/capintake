<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Services\Lookup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NonCashBenefitsRelationManager extends RelationManager
{
    protected static string $relationship = 'nonCashBenefits';

    protected static ?string $title = 'Non-Cash Benefits';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('benefit_type')
                    ->label('Benefit')
                    ->options(fn () => Lookup::options('non_cash_benefit'))
                    ->required(),

                DatePicker::make('effective_date')
                    ->default(now()),

                DatePicker::make('expiration_date'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('benefit_type')
            ->columns([
                TextColumn::make('benefit_type')
                    ->label('Benefit')
                    ->formatStateUsing(fn (?string $state): string => $state ? Lookup::label('non_cash_benefit', $state) ?? ucfirst(str_replace('_', ' ', $state)) : '—')
                    ->sortable(),

                TextColumn::make('effective_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('expiration_date')
                    ->date()
                    ->sortable(),

                BooleanColumn::make('is_active')
                    ->label('Active'),
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

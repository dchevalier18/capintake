<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FederalPovertyLevelResource\Pages;
use App\Models\FederalPovertyLevel;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FederalPovertyLevelResource extends Resource
{
    protected static ?string $model = FederalPovertyLevel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?int $navigationSort = 21;

    protected static ?string $navigationLabel = 'FPL Guidelines';

    protected static ?string $modelLabel = 'FPL Guideline';

    protected static ?string $pluralModelLabel = 'FPL Guidelines';

    public static function table(Table $table): Table
    {
        return $table
            ->query(FederalPovertyLevel::query()->orderByDesc('year')->orderBy('region')->orderBy('household_size'))
            ->columns([
                TextColumn::make('year')
                    ->sortable(),

                TextColumn::make('region')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'continental' => 'primary',
                        'alaska' => 'info',
                        'hawaii' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('household_size')
                    ->label('HH Size')
                    ->sortable(),

                TextColumn::make('poverty_guideline')
                    ->label('Guideline Amount')
                    ->money('USD', divideBy: 100)
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->options(fn () => FederalPovertyLevel::query()
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->toArray()),

                SelectFilter::make('region')
                    ->options([
                        'continental' => 'Continental US',
                        'alaska' => 'Alaska',
                        'hawaii' => 'Hawaii',
                    ]),
            ])
            ->groups([
                \Filament\Tables\Grouping\Group::make('year')
                    ->collapsible(),
            ])
            ->defaultGroup('year')
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFederalPovertyLevels::route('/'),
            'create' => Pages\CreateFederalPovertyLevel::route('/create'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CsbgExpenditureResource\Pages;
use App\Models\CsbgExpenditure;
use App\Services\Lookup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CsbgExpenditureResource extends Resource
{
    protected static ?string $model = CsbgExpenditure::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'CSBG Reports';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Expenditures';

    public static function form(Schema $schema): Schema
    {
        $domainOptions = array_merge(
            Lookup::options('community_domain'),
            [
                'multi_domain' => 'Multiple Domains',
                'linkages' => 'Linkages',
                'capacity_building' => 'Capacity Building',
                'other' => 'Other',
            ]
        );

        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('fiscal_year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2040)
                            ->default(now()->month >= 10 ? now()->year + 1 : now()->year),

                        Select::make('reporting_period')
                            ->options([
                                'oct_sep' => 'October - September',
                                'jul_jun' => 'July - June',
                                'jan_dec' => 'January - December',
                            ])
                            ->required()
                            ->default('oct_sep'),

                        Select::make('domain')
                            ->options($domainOptions)
                            ->required(),

                        TextInput::make('csbg_funds')
                            ->label('CSBG Funds')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0),

                        Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fiscal_year')
                    ->sortable(),

                TextColumn::make('domain')
                    ->badge()
                    ->sortable(),

                TextColumn::make('csbg_funds')
                    ->label('CSBG Funds')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('reporting_period')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('fiscal_year')
                    ->options(fn () => CsbgExpenditure::distinct()->pluck('fiscal_year', 'fiscal_year')->toArray()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fiscal_year', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCsbgExpenditures::route('/'),
            'create' => Pages\CreateCsbgExpenditure::route('/create'),
            'edit' => Pages\EditCsbgExpenditure::route('/{record}/edit'),
        ];
    }
}

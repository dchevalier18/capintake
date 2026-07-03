<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CnpiResultResource\Pages;
use App\Models\CnpiIndicator;
use App\Models\CnpiResult;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CnpiResultResource extends Resource
{
    protected static ?string $model = CnpiResult::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'CSBG Reports';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Community NPI Results';

    protected static ?string $modelLabel = 'Community NPI Result';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Indicator')
                    ->schema([
                        Select::make('cnpi_indicator_id')
                            ->label('CNPI Indicator')
                            ->options(fn (): array => CnpiIndicator::forVersion()
                                ->orderBy('sort_order')
                                ->get()
                                ->groupBy(fn (CnpiIndicator $i): string => ucfirst(str_replace('_', ' ', $i->domain)))
                                ->map(fn ($group) => $group->mapWithKeys(
                                    fn (CnpiIndicator $i): array => [$i->id => "{$i->indicator_code} — {$i->name}"]
                                )->toArray())
                                ->toArray())
                            ->required()
                            ->searchable()
                            ->live(),

                        TextInput::make('fiscal_year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2040)
                            ->default(now()->month >= 10 ? now()->year + 1 : now()->year),

                        TextInput::make('identified_community')
                            ->label('Identified Community')
                            ->maxLength(255)
                            ->helperText('The community this result applies to (e.g. county, neighborhood, service area).'),

                        Select::make('community_initiative_id')
                            ->label('Community Initiative')
                            ->relationship('communityInitiative', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Section::make('Count of Change')
                    ->description('For count-of-change indicators: the target and actual number achieved. Performance accuracy is calculated automatically.')
                    ->schema([
                        TextInput::make('target')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('actual_result')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('performance_accuracy')
                            ->numeric()
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-calculated (actual / target × 100).'),
                    ])
                    ->columns(3)
                    ->visible(fn (Get $get): bool => self::indicatorType($get('cnpi_indicator_id')) !== 'rate_of_change'),

                Section::make('Rate of Change')
                    ->description('For rate-of-change indicators: the baseline and expected/actual percentage change.')
                    ->schema([
                        TextInput::make('baseline_value')
                            ->numeric(),

                        TextInput::make('expected_change_pct')
                            ->label('Expected Change')
                            ->numeric()
                            ->suffix('%'),

                        TextInput::make('actual_change_pct')
                            ->label('Actual Change')
                            ->numeric()
                            ->suffix('%'),
                    ])
                    ->columns(3)
                    ->visible(fn (Get $get): bool => self::indicatorType($get('cnpi_indicator_id')) !== 'count_of_change'),

                Section::make('Documentation')
                    ->schema([
                        TextInput::make('data_source')
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    protected static function indicatorType(mixed $indicatorId): ?string
    {
        if (! $indicatorId) {
            return null;
        }

        $type = CnpiIndicator::find($indicatorId)?->cnpi_type;

        return $type instanceof \BackedEnum ? $type->value : $type;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('indicator.indicator_code')
                    ->label('Indicator')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('indicator.name')
                    ->label('Name')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('fiscal_year')
                    ->label('FFY')
                    ->sortable(),

                TextColumn::make('identified_community')
                    ->label('Community')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('target')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('actual_result')
                    ->label('Actual')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('performance_accuracy')
                    ->label('Accuracy')
                    ->suffix('%')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('fiscal_year')
                    ->options(fn (): array => CnpiResult::query()
                        ->distinct()
                        ->orderByDesc('fiscal_year')
                        ->pluck('fiscal_year', 'fiscal_year')
                        ->toArray()),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCnpiResults::route('/'),
            'create' => Pages\CreateCnpiResult::route('/create'),
            'edit' => Pages\EditCnpiResult::route('/{record}/edit'),
        ];
    }
}

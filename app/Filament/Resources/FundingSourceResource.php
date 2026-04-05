<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FundingSourceResource\Pages;
use App\Models\FundingSource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class FundingSourceResource extends Resource
{
    protected static ?string $model = FundingSource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'CSBG Reports';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Funding Sources';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Funding Source Details')
                    ->schema([
                        TextInput::make('fiscal_year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2040)
                            ->default(now()->month >= 10 ? now()->year + 1 : now()->year),

                        Select::make('source_type')
                            ->options(FundingSource::SOURCE_TYPES)
                            ->required(),

                        TextInput::make('source_name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., LIHEAP, United Way, County General Fund'),

                        TextInput::make('cfda_number')
                            ->label('CFDA Number')
                            ->maxLength(10)
                            ->placeholder('e.g., 93.569'),

                        TextInput::make('amount')
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

                TextColumn::make('source_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FundingSource::SOURCE_TYPES[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('source_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cfda_number')
                    ->label('CFDA'),

                TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('fiscal_year')
                    ->options(fn () => FundingSource::distinct()->pluck('fiscal_year', 'fiscal_year')->mapWithKeys(fn ($fy) => [$fy => "FY {$fy}"])->toArray()),

                SelectFilter::make('source_type')
                    ->options(FundingSource::SOURCE_TYPES),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => Pages\ListFundingSources::route('/'),
            'create' => Pages\CreateFundingSource::route('/create'),
            'edit' => Pages\EditFundingSource::route('/{record}/edit'),
        ];
    }
}

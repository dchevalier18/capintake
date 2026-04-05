<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CommunityInitiativeResource\Pages;
use App\Models\CommunityInitiative;
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

class CommunityInitiativeResource extends Resource
{
    protected static ?string $model = CommunityInitiative::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'CSBG Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Community Initiatives';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Initiative Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('fiscal_year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2040)
                            ->default(now()->month >= 10 ? now()->year + 1 : now()->year),

                        TextInput::make('year_number')
                            ->label('Year Number (of initiative)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),

                        Select::make('domain')
                            ->options(fn () => Lookup::options('community_domain'))
                            ->required(),

                        TextInput::make('identified_community')
                            ->maxLength(255),

                        TextInput::make('expected_duration')
                            ->maxLength(255)
                            ->placeholder('e.g., 3 years'),

                        Select::make('partnership_type')
                            ->options([
                                'independent' => 'Independent',
                                'core_organizer' => 'Core Organizer',
                                'active_partner' => 'Active Partner',
                            ]),

                        Select::make('progress_status')
                            ->options([
                                'no_outcomes' => 'No Outcomes Yet',
                                'interim' => 'Interim Outcomes',
                                'final' => 'Final Outcomes',
                            ]),

                        Select::make('final_status')
                            ->options([
                                'active' => 'Active',
                                'ended_early' => 'Ended Early',
                                'ended_planned' => 'Ended as Planned',
                                'completed_delivering' => 'Completed and Delivering',
                            ]),
                    ])
                    ->columns(3),

                Section::make('Community Strategies')
                    ->schema([
                        Select::make('strCategories')
                            ->label('Community Strategies (STR Codes)')
                            ->relationship('strCategories', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->columnSpanFull(),
                    ]),

                Section::make('Narrative')
                    ->schema([
                        Textarea::make('problem_statement')
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Textarea::make('goal_statement')
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Textarea::make('partners')
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Textarea::make('impact_narrative')
                            ->maxLength(5000)
                            ->columnSpanFull(),

                        Textarea::make('lessons_learned')
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('domain')
                    ->badge()
                    ->sortable(),

                TextColumn::make('fiscal_year')
                    ->sortable(),

                TextColumn::make('year_number')
                    ->label('Year #'),

                TextColumn::make('progress_status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'final' => 'success',
                        'interim' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('final_status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('fiscal_year')
                    ->options(fn () => CommunityInitiative::distinct()->pluck('fiscal_year', 'fiscal_year')->toArray()),
                SelectFilter::make('domain')
                    ->options(fn () => Lookup::options('community_domain')),
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
            'index' => Pages\ListCommunityInitiatives::route('/'),
            'create' => Pages\CreateCommunityInitiative::route('/create'),
            'edit' => Pages\EditCommunityInitiative::route('/{record}/edit'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LookupCategoryResource\Pages;
use App\Models\LookupCategory;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LookupCategoryResource extends Resource
{
    protected static ?string $model = LookupCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Lookup Values';

    protected static ?string $modelLabel = 'Lookup Category';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->schema([
                        TextInput::make('key')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (?LookupCategory $record): bool => $record?->is_system ?? false)
                            ->dehydrated(),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('description')
                            ->maxLength(1000),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Section::make('Values')
                    ->description('Manage the dropdown options for this category.')
                    ->schema([
                        Repeater::make('values')
                            ->relationship()
                            ->schema([
                                TextInput::make('key')
                                    ->required()
                                    ->maxLength(100)
                                    ->disabled(fn ($record): bool => (bool) ($record?->is_system ?? false))
                                    ->dehydrated(),

                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('csbg_report_code')
                                    ->label('CSBG Report Code')
                                    ->maxLength(255)
                                    ->helperText('If different from label, the value used in CSBG reports.'),

                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('Add Value')
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->deleteAction(
                                fn ($action) => $action->requiresConfirmation()
                                    ->hidden(fn (array $arguments, Repeater $component): bool => ($component->getItemState($arguments['item'])['is_system'] ?? false) === true)
                            )
                            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? '') . (($state['is_active'] ?? true) ? '' : ' (inactive)'))
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

                TextColumn::make('key')
                    ->label('Key')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('values_count')
                    ->label('Values')
                    ->counts('values')
                    ->sortable(),

                TextColumn::make('active_values_count')
                    ->label('Active')
                    ->counts('activeValues')
                    ->sortable(),

                BooleanColumn::make('is_system')
                    ->label('System'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLookupCategories::route('/'),
            'edit' => Pages\EditLookupCategory::route('/{record}/edit'),
            'create' => Pages\CreateLookupCategory::route('/create'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRecordResource\Pages;
use App\Models\Client;
use App\Models\ServiceRecord;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceRecordResource extends Resource
{
    protected static ?string $model = ServiceRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Program Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Service Records';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service Details')
                    ->schema([
                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Client $record): string => $record->fullName())
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('enrollment_id')
                            ->label('Enrollment')
                            ->relationship('enrollment', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "#{$record->id} - {$record->program->name}")
                            ->searchable()
                            ->preload(),

                        Select::make('provided_by')
                            ->label('Provider')
                            ->relationship('provider', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        DatePicker::make('service_date')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),

                Section::make('Quantity & Value')
                    ->schema([
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('value')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0),
                    ])
                    ->columns(2),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.first_name')
                    ->label('Client')
                    ->formatStateUsing(fn ($record): string => $record->client->fullName())
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('client', function ($query) use ($search): void {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('service.name')
                    ->sortable(),

                TextColumn::make('enrollment.program.name')
                    ->label('Program')
                    ->sortable(),

                TextColumn::make('provider.name')
                    ->label('Provider')
                    ->sortable(),

                TextColumn::make('service_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('value')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('service_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('service_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('service_date', '<=', $date),
                            );
                    }),

                SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name'),

                SelectFilter::make('provided_by')
                    ->label('Provider')
                    ->relationship('provider', 'name'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRecords::route('/'),
            'create' => Pages\CreateServiceRecord::route('/create'),
            'edit' => Pages\EditServiceRecord::route('/{record}/edit'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\EnrollmentResource\Pages;
use App\Models\Client;
use App\Models\Enrollment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Program Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Enrollment Details')
                    ->schema([
                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Client $record): string => $record->fullName())
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('program_id')
                            ->relationship('program', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('caseworker_id')
                            ->label('Caseworker')
                            ->relationship('caseworker', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->options(EnrollmentStatus::class)
                            ->required()
                            ->default(EnrollmentStatus::Pending),

                        DatePicker::make('enrolled_at')
                            ->required()
                            ->default(now()),

                        DatePicker::make('completed_at'),
                    ])
                    ->columns(3),

                Section::make('Eligibility Information')
                    ->schema([
                        Toggle::make('income_eligible')
                            ->label('Income Eligible')
                            ->default(false),

                        TextInput::make('household_income_at_enrollment')
                            ->label('Household Income at Enrollment')
                            ->numeric()
                            ->prefix('$'),

                        TextInput::make('household_size_at_enrollment')
                            ->label('Household Size at Enrollment')
                            ->numeric()
                            ->minValue(1),

                        TextInput::make('fpl_percent_at_enrollment')
                            ->label('FPL % at Enrollment')
                            ->numeric()
                            ->suffix('%'),
                    ])
                    ->columns(2),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('eligibility_notes')
                            ->label('Eligibility Notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Textarea::make('denial_reason')
                            ->label('Denial Reason')
                            ->maxLength(1000)
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

                TextColumn::make('program.name')
                    ->sortable(),

                TextColumn::make('caseworker.name')
                    ->label('Caseworker')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('enrolled_at')
                    ->date()
                    ->sortable(),

                BooleanColumn::make('income_eligible')
                    ->label('Eligible'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnrollmentStatus::class),

                SelectFilter::make('program_id')
                    ->label('Program')
                    ->relationship('program', 'name'),

                SelectFilter::make('caseworker_id')
                    ->label('Caseworker')
                    ->relationship('caseworker', 'name'),
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
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}

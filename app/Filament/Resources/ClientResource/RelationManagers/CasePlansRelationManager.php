<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Enums\CasePlanStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CasePlansRelationManager extends RelationManager
{
    protected static string $relationship = 'casePlans';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Select::make('status')
                    ->options(collect(CasePlanStatus::cases())->mapWithKeys(
                        fn (CasePlanStatus $s) => [$s->value => $s->label()]
                    ))
                    ->required()
                    ->default(CasePlanStatus::Active->value),

                DatePicker::make('start_date')
                    ->required()
                    ->default(now()),

                DatePicker::make('target_completion_date'),

                Textarea::make('notes')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->limit(40),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (CasePlanStatus $state): string => match ($state) {
                        CasePlanStatus::Active => 'success',
                        CasePlanStatus::Completed => 'info',
                        CasePlanStatus::Closed => 'gray',
                    })
                    ->formatStateUsing(fn (CasePlanStatus $state): string => $state->label()),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('goals_count')
                    ->counts('goals')
                    ->label('Goals'),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('start_date', 'desc');
    }
}

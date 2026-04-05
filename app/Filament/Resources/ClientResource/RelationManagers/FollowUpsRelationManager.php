<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'followUps';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('follow_up_type')
                    ->options(FollowUp::TYPES)
                    ->required(),

                DatePicker::make('scheduled_date')
                    ->required()
                    ->default(now()->addWeeks(1)),

                Select::make('status')
                    ->options(collect(FollowUpStatus::cases())->mapWithKeys(
                        fn (FollowUpStatus $s) => [$s->value => $s->label()]
                    ))
                    ->required()
                    ->default(FollowUpStatus::Scheduled->value),

                DatePicker::make('completed_date'),

                Select::make('assigned_to')
                    ->relationship('assignedToUser', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id()),

                Textarea::make('notes')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('follow_up_type')
            ->columns([
                TextColumn::make('follow_up_type')
                    ->formatStateUsing(fn (string $state): string => FollowUp::TYPES[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('scheduled_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record): string => $record->status === FollowUpStatus::Scheduled && $record->scheduled_date->isPast() ? 'danger' : 'gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (FollowUpStatus $state): string => match ($state) {
                        FollowUpStatus::Completed => 'success',
                        FollowUpStatus::Scheduled => 'warning',
                        FollowUpStatus::Missed => 'danger',
                        FollowUpStatus::Rescheduled => 'info',
                    })
                    ->formatStateUsing(fn (FollowUpStatus $state): string => $state->label()),

                TextColumn::make('assignedToUser.name')
                    ->label('Assigned To'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(FollowUpStatus::cases())->mapWithKeys(
                        fn (FollowUpStatus $s) => [$s->value => $s->label()]
                    )),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('scheduled_date', 'asc');
    }
}

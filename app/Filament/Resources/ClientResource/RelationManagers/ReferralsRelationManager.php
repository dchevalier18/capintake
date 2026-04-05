<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Enums\ReferralStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralsRelationManager extends RelationManager
{
    protected static string $relationship = 'referrals';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('referred_to_agency')
                    ->required()
                    ->maxLength(255),

                TextInput::make('referred_to_contact')
                    ->maxLength(255),

                TextInput::make('referred_to_phone')
                    ->tel()
                    ->maxLength(20),

                DatePicker::make('referral_date')
                    ->required()
                    ->default(now()),

                Select::make('status')
                    ->options(collect(ReferralStatus::cases())->mapWithKeys(
                        fn (ReferralStatus $s) => [$s->value => $s->label()]
                    ))
                    ->required()
                    ->default(ReferralStatus::Pending->value),

                DatePicker::make('follow_up_date'),

                Textarea::make('referral_reason')
                    ->maxLength(2000)
                    ->columnSpanFull(),

                Textarea::make('outcome')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('referred_to_agency')
            ->columns([
                TextColumn::make('referred_to_agency')
                    ->sortable(),

                TextColumn::make('referral_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ReferralStatus $state): string => match ($state) {
                        ReferralStatus::Completed => 'success',
                        ReferralStatus::Accepted => 'info',
                        ReferralStatus::Pending => 'warning',
                        ReferralStatus::Declined, ReferralStatus::NoResponse => 'danger',
                    })
                    ->formatStateUsing(fn (ReferralStatus $state): string => $state->label()),

                TextColumn::make('follow_up_date')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['referred_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('referral_date', 'desc');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\SelfSufficiencyAssessment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SelfSufficiencyAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'selfSufficiencyAssessments';

    protected static ?string $title = 'Self-Sufficiency Assessments';

    public function form(Schema $schema): Schema
    {
        $scoreOptions = [
            1 => '1 — In Crisis',
            2 => '2 — Vulnerable',
            3 => '3 — Safe',
            4 => '4 — Building Capacity',
            5 => '5 — Empowered',
        ];

        return $schema
            ->components([
                DatePicker::make('assessment_date')
                    ->required()
                    ->default(now()),

                Select::make('assessed_by')
                    ->label('Assessed By')
                    ->relationship('assessedByUser', 'name')
                    ->default(fn () => Auth::id())
                    ->required(),

                Section::make('Domain Scores')
                    ->description('Score each life domain from 1 (In Crisis) to 5 (Empowered). The total is calculated on save.')
                    ->schema(collect(SelfSufficiencyAssessment::DOMAINS)
                        ->map(fn (string $label, string $key) => Select::make("domain_scores.{$key}")
                            ->label($label)
                            ->options($scoreOptions))
                        ->values()
                        ->toArray())
                    ->columns(3),

                Textarea::make('notes')
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('assessment_date')
            ->defaultSort('assessment_date', 'desc')
            ->columns([
                TextColumn::make('assessment_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('assessedByUser.name')
                    ->label('Assessed By'),

                TextColumn::make('total_score')
                    ->label('Total Score')
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 44 => 'success',
                        $state >= 33 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->headerActions([
                CreateAction::make(),
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
}

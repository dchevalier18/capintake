<?php

declare(strict_types=1);

namespace App\Filament\Pages\IntakeWizard\Steps;

use App\Filament\Pages\IntakeWizard;
use App\Models\FederalPovertyLevel;
use App\Models\Program;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Auth;

class EnrollmentStep
{
    public static function make(IntakeWizard $page): Step
    {
        return Step::make('Program Enrollment')
            ->icon('heroicon-o-academic-cap')
            ->description('Enroll in eligible programs')
            ->schema([
                Section::make('Select Programs')
                    ->description('Choose programs to enroll this client in. Eligibility is shown based on income data from the previous step.')
                    ->schema([
                        Repeater::make('program_enrollments')
                            ->label('')
                            ->schema([
                                Select::make('program_id')
                                    ->label('Program')
                                    ->options(function () use ($page): array {
                                        $totalIncome = $page->calculateTotalIncome($page->data['income_sources'] ?? []);
                                        $householdSize = count($page->data['household_members'] ?? []) + 1;
                                        $fplPercent = FederalPovertyLevel::fplPercent($totalIncome, $householdSize);

                                        return Program::active()
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function (Program $program) use ($fplPercent): array {
                                                $label = $program->name.' ('.$program->code.')';

                                                if (! $program->requires_income_eligibility) {
                                                    $label .= ' — No income requirement';
                                                } elseif ($fplPercent === null) {
                                                    $label .= ' — FPL data unavailable';
                                                } elseif ($fplPercent <= $program->fpl_threshold_percent) {
                                                    $label .= ' — Eligible ('.$fplPercent.'% / '.$program->fpl_threshold_percent.'% max)';
                                                } else {
                                                    $label .= ' — INELIGIBLE ('.$fplPercent.'% / '.$program->fpl_threshold_percent.'% max)';
                                                }

                                                return [$program->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->disableOptionWhen(function (string $value) use ($page): bool {
                                        $program = Program::find($value);
                                        if (! $program || ! $program->requires_income_eligibility) {
                                            return false;
                                        }

                                        $totalIncome = $page->calculateTotalIncome($page->data['income_sources'] ?? []);
                                        $householdSize = count($page->data['household_members'] ?? []) + 1;
                                        $fplPercent = FederalPovertyLevel::fplPercent($totalIncome, $householdSize);

                                        return $fplPercent !== null && $fplPercent > $program->fpl_threshold_percent;
                                    })
                                    ->required()
                                    ->searchable(),

                                DatePicker::make('enrolled_at')
                                    ->label('Enrollment Date')
                                    ->default(now()->format('Y-m-d'))
                                    ->required(),

                                Select::make('caseworker_id')
                                    ->label('Caseworker')
                                    ->options(fn (): array => User::query()
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray()
                                    )
                                    ->default(fn () => Auth::id())
                                    ->required()
                                    ->searchable(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add program enrollment')
                            ->reorderable(false)
                            ->defaultItems(0),
                    ]),
            ])
            ->afterValidation(function () use ($page): void {
                $page->saveDraftStep4();
            });
    }
}

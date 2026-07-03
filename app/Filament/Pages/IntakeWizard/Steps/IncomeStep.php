<?php

declare(strict_types=1);

namespace App\Filament\Pages\IntakeWizard\Steps;

use App\Enums\IncomeFrequency;
use App\Filament\Pages\IntakeWizard;
use App\Models\FederalPovertyLevel;
use App\Services\Lookup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class IncomeStep
{
    public static function make(IntakeWizard $page): Step
    {
        return Step::make('Income & Eligibility')
            ->icon('heroicon-o-currency-dollar')
            ->description('Income sources and FPL eligibility')
            ->schema([
                Section::make('Income Sources')
                    ->description('Add all income sources for the client')
                    ->schema([
                        Repeater::make('income_sources')
                            ->label('')
                            ->schema([
                                Select::make('source')
                                    ->options(fn () => Lookup::options('income_source'))
                                    ->required(),

                                TextInput::make('source_description')
                                    ->label('Employer / Description')
                                    ->maxLength(255),

                                TextInput::make('amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->live(onBlur: true),

                                Select::make('frequency')
                                    ->options(collect(IncomeFrequency::cases())
                                        ->mapWithKeys(fn (IncomeFrequency $f): array => [$f->value => $f->label()])
                                        ->toArray()
                                    )
                                    ->required()
                                    ->live(),

                                Placeholder::make('annual_display')
                                    ->label('Annual')
                                    ->content(function (Get $get): string {
                                        $amount = (float) ($get('amount') ?? 0);
                                        $freq = $get('frequency');
                                        if (! $freq || $amount <= 0) {
                                            return '$0.00';
                                        }

                                        $annual = $amount * IncomeFrequency::from($freq)->annualMultiplier();

                                        return '$'.number_format($annual, 2);
                                    }),
                            ])
                            ->columns(5)
                            ->columnSpanFull()
                            ->addActionLabel('Add income source')
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->live(),
                    ]),

                Section::make('Eligibility Summary')
                    ->schema([
                        Placeholder::make('total_income_display')
                            ->label('Total Annual Household Income')
                            ->content(function (Get $get) use ($page): HtmlString {
                                $total = $page->calculateTotalIncome($get('income_sources') ?? []);

                                return new HtmlString(
                                    '<span class="text-lg font-bold">$'.number_format($total, 2).'</span>'
                                );
                            }),

                        Placeholder::make('household_size_reminder')
                            ->label('Household Size')
                            ->content(function (Get $get): string {
                                $members = $get('household_members') ?? [];

                                return (string) (count($members) + 1);
                            }),

                        Placeholder::make('fpl_status')
                            ->label('Federal Poverty Level Status')
                            ->content(function (Get $get) use ($page): HtmlString {
                                $total = $page->calculateTotalIncome($get('income_sources') ?? []);
                                $householdSize = count($get('household_members') ?? []) + 1;
                                $fplPercent = FederalPovertyLevel::fplPercent($total, $householdSize);

                                if ($fplPercent === null) {
                                    return new HtmlString(
                                        '<span class="text-gray-500">FPL data not available for current year. Seed the federal_poverty_levels table.</span>'
                                    );
                                }

                                if ($total == 0 && $householdSize >= 1) {
                                    return new HtmlString(
                                        '<span class="font-bold text-success-600 dark:text-success-400">0% FPL — No income reported</span>'
                                    );
                                }

                                if ($fplPercent <= 125) {
                                    $color = 'text-success-600 dark:text-success-400';
                                    $label = 'Eligible for most programs';
                                } elseif ($fplPercent <= 200) {
                                    $color = 'text-warning-600 dark:text-warning-400';
                                    $label = 'Eligible for some programs';
                                } else {
                                    $color = 'text-danger-600 dark:text-danger-400';
                                    $label = 'Over income for most programs';
                                }

                                return new HtmlString(
                                    "<span class=\"font-bold {$color}\">{$fplPercent}% FPL — {$label}</span>"
                                );
                            }),

                        Placeholder::make('documentation_flag')
                            ->label('')
                            ->content(function (Get $get): HtmlString {
                                $incomes = $get('income_sources') ?? [];
                                $flags = [];

                                foreach ($incomes as $income) {
                                    $source = $income['source'] ?? '';
                                    $amount = (float) ($income['amount'] ?? 0);
                                    if ($source === 'self_employment' && $amount > 0) {
                                        $flags[] = 'Self-employment income may require tax return or profit/loss statement';
                                    }
                                }

                                if (empty($incomes)) {
                                    $flags[] = 'No income reported — self-declaration form may be required';
                                }

                                if (empty($flags)) {
                                    return new HtmlString('');
                                }

                                $html = '<div class="rounded-lg bg-info-50 dark:bg-info-900/20 border border-info-300 dark:border-info-700 p-3 text-sm text-info-700 dark:text-info-300">'
                                    .'<div class="font-medium mb-1">Documentation Notes:</div><ul class="list-disc list-inside">';
                                foreach ($flags as $flag) {
                                    $html .= '<li>'.e($flag).'</li>';
                                }
                                $html .= '</ul></div>';

                                return new HtmlString($html);
                            }),
                    ])
                    ->columns(3),
            ])
            ->afterValidation(function () use ($page): void {
                $page->saveDraftStep3();
            });
    }
}

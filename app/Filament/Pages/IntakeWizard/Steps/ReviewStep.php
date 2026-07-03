<?php

declare(strict_types=1);

namespace App\Filament\Pages\IntakeWizard\Steps;

use App\Enums\IncomeFrequency;
use App\Filament\Pages\IntakeWizard;
use App\Models\FederalPovertyLevel;
use App\Models\Household;
use App\Models\Program;
use App\Models\User;
use App\Services\Lookup;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class ReviewStep
{
    public static function make(IntakeWizard $page): Step
    {
        return Step::make('Review & Submit')
            ->icon('heroicon-o-check-circle')
            ->description('Review all information before completing intake')
            ->schema([
                Section::make('Client Information')
                    ->schema([
                        Placeholder::make('review_client')
                            ->label('')
                            ->content(function () use ($page): HtmlString {
                                $d = $page->data;
                                $name = trim(($d['first_name'] ?? '').' '.($d['middle_name'] ?? '').' '.($d['last_name'] ?? ''));
                                $ssn = ! empty($d['ssn_encrypted'])
                                    ? '***-**-'.substr(preg_replace('/\D/', '', $d['ssn_encrypted']), -4)
                                    : 'Not provided';

                                $dobFormatted = ! empty($d['date_of_birth'])
                                    ? date('m/d/Y', strtotime($d['date_of_birth']))
                                    : 'N/A';

                                $rows = [
                                    'Name' => e($name),
                                    'Date of Birth' => $dobFormatted,
                                    'SSN' => $ssn,
                                    'Phone' => e($d['phone'] ?? 'N/A'),
                                    'Email' => e($d['email'] ?? 'N/A'),
                                    'Gender' => e(Lookup::label('gender', $d['gender'] ?? null) ?? 'N/A'),
                                    'Race' => e(Lookup::label('race', $d['race'] ?? null) ?? 'N/A'),
                                    'Ethnicity' => e(Lookup::label('ethnicity', $d['ethnicity'] ?? null) ?? 'N/A'),
                                    'Veteran' => ($d['is_veteran'] ?? false) ? 'Yes' : 'No',
                                    'Disabled' => ($d['is_disabled'] ?? false) ? 'Yes' : 'No',
                                ];

                                return new HtmlString(self::buildReviewTable($rows));
                            }),
                    ]),

                Section::make('Household')
                    ->schema([
                        Placeholder::make('review_household')
                            ->label('')
                            ->content(function () use ($page): HtmlString {
                                $d = $page->data;

                                if (($d['household_mode'] ?? 'new') === 'existing' && ! empty($d['existing_household_id'])) {
                                    $h = Household::find($d['existing_household_id']);
                                    $address = $h ? $h->fullAddress() : 'Unknown';
                                } else {
                                    $address = implode(', ', array_filter([
                                        $d['address_line_1'] ?? '',
                                        $d['address_line_2'] ?? '',
                                        $d['city'] ?? '',
                                        ($d['state'] ?? '').' '.($d['zip'] ?? ''),
                                    ]));
                                }

                                $members = $d['household_members'] ?? [];
                                $size = count($members) + 1;

                                $county = $d['county'] ?? null;
                                $rows = [
                                    'Address' => e($address),
                                    'County' => e($county ?: 'N/A'),
                                    'Housing Type' => e(Lookup::label('housing_type', $d['housing_type'] ?? null) ?? 'N/A'),
                                    'Household Size' => (string) $size,
                                    'Head of Household' => ($d['is_head_of_household'] ?? false) ? 'Yes' : 'No',
                                ];

                                $html = self::buildReviewTable($rows);

                                if (! empty($members)) {
                                    $html .= '<div class="mt-3 text-sm font-medium">Members:</div><ul class="list-disc list-inside text-sm">';
                                    foreach ($members as $m) {
                                        $details = [e(Lookup::label('relationship_to_head', $m['relationship_to_client'] ?? null) ?? ($m['relationship_to_client'] ?? ''))];
                                        if (! empty($m['gender'])) {
                                            $details[] = Lookup::label('gender', $m['gender']) ?? ucfirst($m['gender']);
                                        }
                                        if (! empty($m['employment_status'])) {
                                            $details[] = Lookup::label('employment_status', $m['employment_status']) ?? ucfirst($m['employment_status']);
                                        }
                                        $html .= '<li>'.e(($m['first_name'] ?? '').' '.($m['last_name'] ?? ''))
                                            .' — '.implode(', ', $details).'</li>';
                                    }
                                    $html .= '</ul>';
                                }

                                return new HtmlString($html);
                            }),
                    ]),

                Section::make('Income & Eligibility')
                    ->schema([
                        Placeholder::make('review_income')
                            ->label('')
                            ->content(function () use ($page): HtmlString {
                                $d = $page->data;
                                $incomes = $d['income_sources'] ?? [];
                                $totalIncome = $page->calculateTotalIncome($incomes);
                                $householdSize = count($d['household_members'] ?? []) + 1;
                                $fplPercent = FederalPovertyLevel::fplPercent($totalIncome, $householdSize);

                                $html = '';
                                if (! empty($incomes)) {
                                    $html .= '<table class="w-full text-sm"><thead><tr class="border-b">'
                                        .'<th class="text-left py-1">Source</th><th class="text-right py-1">Amount</th>'
                                        .'<th class="text-left py-1 pl-3">Frequency</th><th class="text-right py-1">Annual</th>'
                                        .'</tr></thead><tbody>';

                                    foreach ($incomes as $inc) {
                                        $amount = (float) ($inc['amount'] ?? 0);
                                        $freq = $inc['frequency'] ?? null;
                                        $annual = $freq ? $amount * IncomeFrequency::from($freq)->annualMultiplier() : $amount;

                                        $html .= '<tr class="border-b border-gray-100">'
                                            .'<td class="py-1">'.e(Lookup::label('income_source', $inc['source'] ?? null) ?? ucfirst($inc['source'] ?? '')).'</td>'
                                            .'<td class="text-right py-1">$'.number_format($amount, 2).'</td>'
                                            .'<td class="py-1 pl-3">'.e($freq ? IncomeFrequency::from($freq)->label() : 'N/A').'</td>'
                                            .'<td class="text-right py-1">$'.number_format($annual, 2).'</td>'
                                            .'</tr>';
                                    }

                                    $html .= '<tr class="font-bold"><td class="py-1">Total</td><td></td><td></td>'
                                        .'<td class="text-right py-1">$'.number_format($totalIncome, 2).'</td></tr>';
                                    $html .= '</tbody></table>';
                                } else {
                                    $html .= '<p class="text-sm text-gray-500">No income reported</p>';
                                }

                                $fplLabel = $fplPercent !== null ? "{$fplPercent}% FPL" : 'FPL data unavailable';
                                $html .= '<div class="mt-3 font-medium">Eligibility: '.$fplLabel.'</div>';

                                return new HtmlString($html);
                            }),
                    ]),

                Section::make('Program Enrollments')
                    ->schema([
                        Placeholder::make('review_enrollments')
                            ->label('')
                            ->content(function () use ($page): HtmlString {
                                $enrollments = $page->data['program_enrollments'] ?? [];

                                if (empty($enrollments)) {
                                    return new HtmlString('<p class="text-sm text-gray-500">No programs selected</p>');
                                }

                                $html = '<ul class="space-y-1">';
                                foreach ($enrollments as $e) {
                                    $program = Program::find($e['program_id'] ?? 0);
                                    $caseworker = User::find($e['caseworker_id'] ?? 0);
                                    $html .= '<li class="text-sm">'
                                        .'<span class="font-medium">'.e($program?->name ?? 'Unknown').'</span>'
                                        .' — enrolled '.e(isset($e['enrolled_at']) ? date('m/d/Y', strtotime($e['enrolled_at'])) : 'N/A')
                                        .' — caseworker: '.e($caseworker?->name ?? 'Unassigned')
                                        .'</li>';
                                }
                                $html .= '</ul>';

                                return new HtmlString($html);
                            }),
                    ]),
            ]);
    }

    protected static function buildReviewTable(array $rows): string
    {
        $html = '<table class="w-full text-sm">';
        foreach ($rows as $label => $value) {
            $html .= '<tr class="border-b border-gray-100 dark:border-gray-800">'
                .'<td class="py-1.5 pr-4 font-medium text-gray-600 dark:text-gray-400 w-1/3">'.e($label).'</td>'
                .'<td class="py-1.5">'.$value.'</td>'
                .'</tr>';
        }
        $html .= '</table>';

        return $html;
    }
}

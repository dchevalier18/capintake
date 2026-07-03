<?php

declare(strict_types=1);

namespace App\Filament\Pages\IntakeWizard\Steps;

use App\Filament\Pages\IntakeWizard;
use App\Services\Lookup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ClientInfoStep
{
    public static function make(IntakeWizard $page): Step
    {
        return Step::make('Client Information')
            ->icon('heroicon-o-user')
            ->description('Personal details and contact info')
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $page->runDuplicateCheck()),

                        TextInput::make('middle_name')
                            ->maxLength(255),

                        DatePicker::make('date_of_birth')
                            ->required()
                            ->maxDate(now())
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $page->runDuplicateCheck()),

                        TextInput::make('ssn_encrypted')
                            ->label('Social Security Number')
                            ->password()
                            ->revealable()
                            ->maxLength(11)
                            ->placeholder('XXX-XX-XXXX'),

                        Select::make('preferred_language')
                            ->options([
                                'en' => 'English',
                                'es' => 'Spanish',
                                'zh' => 'Chinese',
                                'vi' => 'Vietnamese',
                                'ar' => 'Arabic',
                                'other' => 'Other',
                            ])
                            ->default('en'),
                    ])
                    ->columns(3),

                Section::make('Contact Information')
                    ->schema([
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Address')
                    ->schema([
                        TextInput::make('address_line_1')
                            ->label('Street Address')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('address_line_2')
                            ->label('Apt / Suite / Unit')
                            ->maxLength(255),

                        TextInput::make('city')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('state')
                            ->required()
                            ->maxLength(2)
                            ->default('PA'),

                        TextInput::make('zip')
                            ->label('ZIP Code')
                            ->required()
                            ->maxLength(10),

                        TextInput::make('county')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Section::make('Demographics')
                    ->description('All fields optional — "Unknown/not reported" is a valid answer for CSBG reporting, but complete data improves the agency\'s Annual Report.')
                    ->schema([
                        Select::make('gender')
                            ->options(fn () => Lookup::options('gender')),

                        Select::make('race')
                            ->label('Race (HUD Categories)')
                            ->options(fn () => Lookup::options('race')),

                        Select::make('ethnicity')
                            ->options(fn () => Lookup::options('ethnicity')),

                        Select::make('education_level')
                            ->label('Education Level')
                            ->options(fn () => Lookup::options('education_level')),

                        Select::make('employment_status')
                            ->label('Work Status')
                            ->helperText('Collected for individuals 18+')
                            ->options(fn () => Lookup::options('employment_status')),

                        Select::make('military_status')
                            ->label('Military Status')
                            ->options(fn () => Lookup::options('military_status'))
                            ->live(),

                        Select::make('health_insurance_status')
                            ->label('Health Insurance')
                            ->options(fn () => Lookup::options('health_insurance_status'))
                            ->live(),

                        Select::make('health_insurance_source')
                            ->label('Insurance Source')
                            ->options(fn () => Lookup::options('health_insurance_source'))
                            ->visible(fn (Get $get): bool => $get('health_insurance_status') === 'yes'),

                        Toggle::make('is_veteran')
                            ->label('Veteran')
                            ->default(false),

                        Toggle::make('is_disabled')
                            ->label('Disabling Condition')
                            ->default(false),

                        Toggle::make('is_disconnected_youth')
                            ->label('Disconnected Youth — not working or in school (ages 14-24)')
                            ->default(false)
                            ->visible(function (Get $get): bool {
                                $dob = $get('date_of_birth');
                                if (! $dob) {
                                    return false;
                                }

                                $age = Carbon::parse($dob)->age;

                                return $age >= 14 && $age <= 24;
                            }),
                    ])
                    ->columns(3),

                Section::make('Duplicate Check')
                    ->schema([
                        Placeholder::make('duplicate_warning_display')
                            ->label('')
                            ->content(fn (): HtmlString => new HtmlString(
                                $page->duplicateWarning
                                    ? '<div class="rounded-lg bg-warning-50 dark:bg-warning-900/20 border border-warning-300 dark:border-warning-700 p-4">'
                                        .'<div class="flex items-center gap-2 font-medium text-warning-800 dark:text-warning-200 mb-2">'
                                        .'<svg style="width:1.25rem;height:1.25rem;min-width:1.25rem;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>'
                                        .'Potential Duplicate Clients Found</div>'
                                        .'<div class="text-sm text-warning-700 dark:text-warning-300 whitespace-pre-line">'.e($page->duplicateWarning).'</div>'
                                        .'</div>'
                                    : ''
                            ))
                            ->visible(fn (): bool => $page->duplicateWarning !== null),

                        Checkbox::make('acknowledge_duplicates')
                            ->label('I have reviewed the potential duplicates above and confirm this is a new client')
                            ->visible(fn (): bool => $page->duplicateWarning !== null)
                            ->accepted(fn (): bool => $page->duplicateWarning !== null)
                            ->live()
                            ->afterStateUpdated(function (bool $state) use ($page): void {
                                if ($state) {
                                    $page->resetValidation('data.acknowledge_duplicates');
                                }
                            }),
                    ])
                    ->hidden(fn (): bool => $page->duplicateWarning === null),
            ])
            ->afterValidation(function () use ($page): void {
                $page->saveDraftStep1();
            });
    }
}

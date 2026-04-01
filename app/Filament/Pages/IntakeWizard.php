<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\IncomeFrequency;
use App\Enums\IntakeStatus;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\FederalPovertyLevel;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class IntakeWizard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'New Intake';

    protected static ?string $title = 'Client Intake Wizard';

    protected string $view = 'filament.pages.intake-wizard';

    protected static string|\UnitEnum|null $navigationGroup = 'Client Management';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public ?int $clientId = null;

    public ?string $duplicateWarning = null;

    public function mount(): void
    {
        $clientId = request()->query('client');

        if ($clientId) {
            $client = Client::with(['household', 'household.members', 'incomeRecords', 'enrollments'])
                ->draft()
                ->find((int) $clientId);

            if ($client) {
                $this->clientId = $client->id;
                $this->loadDraftData($client);

                return;
            }
        }

        $this->form->fill([
            'is_head_of_household' => true,
            'relationship_to_head' => 'self',
            'preferred_language' => 'en',
            'state' => 'PA',
            'housing_type' => 'rented',
            'household_mode' => 'new',
            'household_members' => [],
            'income_sources' => [],
            'program_enrollments' => [],
            'acknowledge_duplicates' => false,
        ]);
    }

    protected function loadDraftData(Client $client): void
    {
        $household = $client->household;

        $members = $household->members->map(fn (HouseholdMember $m) => [
            'first_name' => $m->first_name,
            'last_name' => $m->last_name,
            'date_of_birth' => $m->date_of_birth?->format('Y-m-d'),
            'relationship_to_client' => $m->relationship_to_client,
        ])->toArray();

        $incomes = $client->incomeRecords->map(fn (IncomeRecord $i) => [
            'source' => $i->source,
            'source_description' => $i->source_description,
            'amount' => (string) $i->amount,
            'frequency' => $i->frequency?->value,
        ])->toArray();

        $enrollments = $client->enrollments->map(fn (Enrollment $e) => [
            'program_id' => (string) $e->program_id,
            'enrolled_at' => $e->enrolled_at?->format('Y-m-d'),
            'caseworker_id' => (string) $e->caseworker_id,
        ])->toArray();

        $this->form->fill([
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'middle_name' => $client->middle_name,
            'date_of_birth' => $client->date_of_birth?->format('Y-m-d'),
            'ssn_encrypted' => '',
            'phone' => $client->phone,
            'email' => $client->email,
            'gender' => $client->gender,
            'race' => $client->race,
            'ethnicity' => $client->ethnicity,
            'is_veteran' => $client->is_veteran ?? false,
            'is_disabled' => $client->is_disabled ?? false,
            'preferred_language' => $client->preferred_language,
            'address_line_1' => $household->address_line_1,
            'address_line_2' => $household->address_line_2,
            'city' => $household->city,
            'state' => $household->state,
            'zip' => $household->zip,
            'county' => $household->county,
            'household_mode' => 'new',
            'existing_household_id' => null,
            'housing_type' => $household->housing_type,
            'is_head_of_household' => $client->is_head_of_household ?? true,
            'relationship_to_head' => $client->relationship_to_head ?? 'self',
            'household_members' => $members,
            'income_sources' => $incomes,
            'program_enrollments' => $enrollments,
            'acknowledge_duplicates' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    $this->clientInfoStep(),
                    $this->householdStep(),
                    $this->incomeStep(),
                    $this->enrollmentStep(),
                    $this->reviewStep(),
                ])
                    ->persistStepInQueryString('step')
                    ->submitAction(new HtmlString(
                        '<button type="submit" class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center gap-1.5 outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 dark:bg-custom-500 dark:hover:bg-custom-400 focus-visible:ring-custom-500/50 dark:focus-visible:ring-custom-400/50" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">Complete Intake</button>'
                    )),
            ]);
    }

    // -------------------------------------------------------------------------
    // Step 1: Client Information
    // -------------------------------------------------------------------------

    protected function clientInfoStep(): Step
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
                            ->afterStateUpdated(fn () => $this->runDuplicateCheck()),

                        TextInput::make('middle_name')
                            ->maxLength(255),

                        DatePicker::make('date_of_birth')
                            ->required()
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('m/d/Y')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->runDuplicateCheck()),

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
                    ->schema([
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'non_binary' => 'Non-Binary',
                                'other' => 'Other',
                                'prefer_not_to_say' => 'Prefer Not to Say',
                            ]),

                        Select::make('race')
                            ->label('Race (HUD Categories)')
                            ->options([
                                'white' => 'White',
                                'black' => 'Black or African American',
                                'asian' => 'Asian',
                                'native_american' => 'American Indian or Alaska Native',
                                'pacific_islander' => 'Native Hawaiian or Pacific Islander',
                                'multi_racial' => 'Two or More Races',
                                'other' => 'Other',
                            ]),

                        Select::make('ethnicity')
                            ->options([
                                'hispanic' => 'Hispanic or Latino',
                                'not_hispanic' => 'Not Hispanic or Latino',
                            ]),

                        Toggle::make('is_veteran')
                            ->label('Veteran')
                            ->default(false),

                        Toggle::make('is_disabled')
                            ->label('Disabled')
                            ->default(false),
                    ])
                    ->columns(3),

                Section::make('Duplicate Check')
                    ->schema([
                        Placeholder::make('duplicate_warning_display')
                            ->label('')
                            ->content(fn (): HtmlString => new HtmlString(
                                $this->duplicateWarning
                                    ? '<div class="rounded-lg bg-warning-50 dark:bg-warning-900/20 border border-warning-300 dark:border-warning-700 p-4">'
                                        . '<div class="flex items-center gap-2 font-medium text-warning-800 dark:text-warning-200 mb-2">'
                                        . '<svg style="width:1.25rem;height:1.25rem;min-width:1.25rem;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>'
                                        . 'Potential Duplicate Clients Found</div>'
                                        . '<div class="text-sm text-warning-700 dark:text-warning-300 whitespace-pre-line">' . e($this->duplicateWarning) . '</div>'
                                        . '</div>'
                                    : ''
                            ))
                            ->visible(fn (): bool => $this->duplicateWarning !== null),

                        Checkbox::make('acknowledge_duplicates')
                            ->label('I have reviewed the potential duplicates above and confirm this is a new client')
                            ->visible(fn (): bool => $this->duplicateWarning !== null)
                            ->accepted(fn (): bool => $this->duplicateWarning !== null)
                            ->live()
                            ->afterStateUpdated(function (bool $state): void {
                                if ($state) {
                                    $this->resetValidation('data.acknowledge_duplicates');
                                }
                            }),
                    ])
                    ->hidden(fn (): bool => $this->duplicateWarning === null),
            ])
            ->afterValidation(function (): void {
                $this->saveDraftStep1();
            });
    }

    // -------------------------------------------------------------------------
    // Step 2: Household
    // -------------------------------------------------------------------------

    protected function householdStep(): Step
    {
        return Step::make('Household')
            ->icon('heroicon-o-home')
            ->description('Household details and members')
            ->schema([
                Section::make('Household')
                    ->schema([
                        Select::make('household_mode')
                            ->label('Household Assignment')
                            ->options([
                                'new' => 'Create new household (using address from Step 1)',
                                'existing' => 'Link to an existing household',
                            ])
                            ->default('new')
                            ->required()
                            ->live(),

                        Select::make('existing_household_id')
                            ->label('Search Existing Households')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Household::query()
                                ->where('address_line_1', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhere('zip', 'like', "%{$search}%")
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (Household $h): array => [$h->id => $h->fullAddress()])
                                ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): string => Household::find($value)?->fullAddress() ?? '')
                            ->visible(fn (Get $get): bool => $get('household_mode') === 'existing')
                            ->required(fn (Get $get): bool => $get('household_mode') === 'existing'),

                        Placeholder::make('new_household_address')
                            ->label('Household Address')
                            ->content(function (): HtmlString {
                                $d = $this->data;
                                $addr = implode(', ', array_filter([
                                    $d['address_line_1'] ?? '',
                                    $d['address_line_2'] ?? '',
                                    $d['city'] ?? '',
                                    ($d['state'] ?? '') . ' ' . ($d['zip'] ?? ''),
                                ]));

                                return new HtmlString('<span class="text-sm">' . e($addr) . '</span>');
                            })
                            ->visible(fn (Get $get): bool => $get('household_mode') === 'new'),

                        Select::make('housing_type')
                            ->options([
                                'owned' => 'Owned',
                                'rented' => 'Rented',
                                'shelter' => 'Shelter',
                                'homeless' => 'Homeless',
                                'transitional' => 'Transitional',
                                'other' => 'Other',
                            ])
                            ->default('rented'),

                        Toggle::make('is_head_of_household')
                            ->label('This client is the head of household')
                            ->default(true)
                            ->live(),

                        TextInput::make('relationship_to_head')
                            ->label('Relationship to Head of Household')
                            ->maxLength(100)
                            ->visible(fn (Get $get): bool => ! $get('is_head_of_household')),
                    ])
                    ->columns(2),

                Section::make('Household Members')
                    ->description('Add other people living in the household')
                    ->schema([
                        Repeater::make('household_members')
                            ->label('')
                            ->schema([
                                TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('last_name')
                                    ->required()
                                    ->maxLength(255),

                                DatePicker::make('date_of_birth')
                                    ->maxDate(now())
                                    ->native(false)
                                    ->displayFormat('m/d/Y'),

                                Select::make('relationship_to_client')
                                    ->label('Relationship')
                                    ->options([
                                        'spouse' => 'Spouse/Partner',
                                        'child' => 'Child',
                                        'parent' => 'Parent',
                                        'sibling' => 'Sibling',
                                        'grandchild' => 'Grandchild',
                                        'grandparent' => 'Grandparent',
                                        'other' => 'Other',
                                    ])
                                    ->required(),
                            ])
                            ->columns(4)
                            ->addActionLabel('Add household member')
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->live(),

                        Placeholder::make('household_size_display')
                            ->label('Total Household Size')
                            ->content(function (Get $get): HtmlString {
                                $members = $get('household_members') ?? [];
                                $size = count($members) + 1;

                                return new HtmlString(
                                    '<span class="text-lg font-bold">' . $size . '</span>'
                                    . '<span class="text-sm text-gray-500 ml-2">(client + ' . count($members) . ' member' . (count($members) !== 1 ? 's' : '') . ')</span>'
                                );
                            }),
                    ]),
            ])
            ->afterValidation(function (): void {
                $this->saveDraftStep2();
            });
    }

    // -------------------------------------------------------------------------
    // Step 3: Income & Eligibility
    // -------------------------------------------------------------------------

    protected function incomeStep(): Step
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
                                    ->options([
                                        'employment' => 'Employment',
                                        'ssi' => 'SSI',
                                        'ssdi' => 'SSDI',
                                        'tanf' => 'TANF',
                                        'snap' => 'SNAP',
                                        'child_support' => 'Child Support',
                                        'pension' => 'Pension',
                                        'unemployment' => 'Unemployment',
                                        'self_employment' => 'Self-Employment',
                                        'other' => 'Other',
                                    ])
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

                                        return '$' . number_format($annual, 2);
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
                            ->content(function (Get $get): HtmlString {
                                $total = $this->calculateTotalIncome($get('income_sources') ?? []);

                                return new HtmlString(
                                    '<span class="text-lg font-bold">$' . number_format($total, 2) . '</span>'
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
                            ->content(function (Get $get): HtmlString {
                                $total = $this->calculateTotalIncome($get('income_sources') ?? []);
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
                                    . '<div class="font-medium mb-1">Documentation Notes:</div><ul class="list-disc list-inside">';
                                foreach ($flags as $flag) {
                                    $html .= '<li>' . e($flag) . '</li>';
                                }
                                $html .= '</ul></div>';

                                return new HtmlString($html);
                            }),
                    ])
                    ->columns(3),
            ])
            ->afterValidation(function (): void {
                $this->saveDraftStep3();
            });
    }

    // -------------------------------------------------------------------------
    // Step 4: Program Enrollment
    // -------------------------------------------------------------------------

    protected function enrollmentStep(): Step
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
                                    ->options(function (): array {
                                        $totalIncome = $this->calculateTotalIncome($this->data['income_sources'] ?? []);
                                        $householdSize = count($this->data['household_members'] ?? []) + 1;
                                        $fplPercent = FederalPovertyLevel::fplPercent($totalIncome, $householdSize);

                                        return Program::active()
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function (Program $program) use ($fplPercent): array {
                                                $label = $program->name . ' (' . $program->code . ')';

                                                if (! $program->requires_income_eligibility) {
                                                    $label .= ' — No income requirement';
                                                } elseif ($fplPercent === null) {
                                                    $label .= ' — FPL data unavailable';
                                                } elseif ($fplPercent <= $program->fpl_threshold_percent) {
                                                    $label .= ' — Eligible (' . $fplPercent . '% / ' . $program->fpl_threshold_percent . '% max)';
                                                } else {
                                                    $label .= ' — Over income (' . $fplPercent . '% / ' . $program->fpl_threshold_percent . '% max)';
                                                }

                                                return [$program->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable(),

                                DatePicker::make('enrolled_at')
                                    ->label('Enrollment Date')
                                    ->default(now()->format('Y-m-d'))
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('m/d/Y'),

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
            ->afterValidation(function (): void {
                $this->saveDraftStep4();
            });
    }

    // -------------------------------------------------------------------------
    // Step 5: Review & Submit
    // -------------------------------------------------------------------------

    protected function reviewStep(): Step
    {
        return Step::make('Review & Submit')
            ->icon('heroicon-o-check-circle')
            ->description('Review all information before completing intake')
            ->schema([
                Section::make('Client Information')
                    ->schema([
                        Placeholder::make('review_client')
                            ->label('')
                            ->content(function (): HtmlString {
                                $d = $this->data;
                                $name = trim(($d['first_name'] ?? '') . ' ' . ($d['middle_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                                $ssn = ! empty($d['ssn_encrypted'])
                                    ? '***-**-' . substr(preg_replace('/\D/', '', $d['ssn_encrypted']), -4)
                                    : 'Not provided';

                                $raceLabels = [
                                    'white' => 'White', 'black' => 'Black or African American',
                                    'asian' => 'Asian', 'native_american' => 'American Indian or Alaska Native',
                                    'pacific_islander' => 'Native Hawaiian or Pacific Islander',
                                    'multi_racial' => 'Two or More Races', 'other' => 'Other',
                                ];
                                $ethnicityLabels = [
                                    'hispanic' => 'Hispanic or Latino',
                                    'not_hispanic' => 'Not Hispanic or Latino',
                                ];
                                $dobFormatted = ! empty($d['date_of_birth'])
                                    ? date('m/d/Y', strtotime($d['date_of_birth']))
                                    : 'N/A';

                                $rows = [
                                    'Name' => e($name),
                                    'Date of Birth' => $dobFormatted,
                                    'SSN' => $ssn,
                                    'Phone' => e($d['phone'] ?? 'N/A'),
                                    'Email' => e($d['email'] ?? 'N/A'),
                                    'Gender' => ucfirst(str_replace('_', ' ', $d['gender'] ?? 'N/A')),
                                    'Race' => $raceLabels[$d['race'] ?? ''] ?? 'N/A',
                                    'Ethnicity' => $ethnicityLabels[$d['ethnicity'] ?? ''] ?? 'N/A',
                                    'Veteran' => ($d['is_veteran'] ?? false) ? 'Yes' : 'No',
                                    'Disabled' => ($d['is_disabled'] ?? false) ? 'Yes' : 'No',
                                ];

                                return new HtmlString($this->buildReviewTable($rows));
                            }),
                    ]),

                Section::make('Household')
                    ->schema([
                        Placeholder::make('review_household')
                            ->label('')
                            ->content(function (): HtmlString {
                                $d = $this->data;

                                if (($d['household_mode'] ?? 'new') === 'existing' && ! empty($d['existing_household_id'])) {
                                    $h = Household::find($d['existing_household_id']);
                                    $address = $h ? $h->fullAddress() : 'Unknown';
                                } else {
                                    $address = implode(', ', array_filter([
                                        $d['address_line_1'] ?? '',
                                        $d['address_line_2'] ?? '',
                                        $d['city'] ?? '',
                                        ($d['state'] ?? '') . ' ' . ($d['zip'] ?? ''),
                                    ]));
                                }

                                $members = $d['household_members'] ?? [];
                                $size = count($members) + 1;

                                $rows = [
                                    'Address' => e($address),
                                    'Housing Type' => ucfirst($d['housing_type'] ?? 'N/A'),
                                    'Household Size' => (string) $size,
                                    'Head of Household' => ($d['is_head_of_household'] ?? false) ? 'Yes' : 'No',
                                ];

                                $html = $this->buildReviewTable($rows);

                                if (! empty($members)) {
                                    $html .= '<div class="mt-3 text-sm font-medium">Members:</div><ul class="list-disc list-inside text-sm">';
                                    foreach ($members as $m) {
                                        $html .= '<li>' . e(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''))
                                            . ' — ' . e($m['relationship_to_client'] ?? '') . '</li>';
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
                            ->content(function (): HtmlString {
                                $d = $this->data;
                                $incomes = $d['income_sources'] ?? [];
                                $totalIncome = $this->calculateTotalIncome($incomes);
                                $householdSize = count($d['household_members'] ?? []) + 1;
                                $fplPercent = FederalPovertyLevel::fplPercent($totalIncome, $householdSize);

                                $html = '';
                                if (! empty($incomes)) {
                                    $html .= '<table class="w-full text-sm"><thead><tr class="border-b">'
                                        . '<th class="text-left py-1">Source</th><th class="text-right py-1">Amount</th>'
                                        . '<th class="text-left py-1 pl-3">Frequency</th><th class="text-right py-1">Annual</th>'
                                        . '</tr></thead><tbody>';

                                    foreach ($incomes as $inc) {
                                        $amount = (float) ($inc['amount'] ?? 0);
                                        $freq = $inc['frequency'] ?? null;
                                        $annual = $freq ? $amount * IncomeFrequency::from($freq)->annualMultiplier() : $amount;

                                        $sourceLabels = [
                                            'employment' => 'Employment', 'ssi' => 'SSI', 'ssdi' => 'SSDI',
                                            'tanf' => 'TANF', 'snap' => 'SNAP', 'child_support' => 'Child Support',
                                            'pension' => 'Pension', 'unemployment' => 'Unemployment',
                                            'self_employment' => 'Self-Employment', 'other' => 'Other',
                                        ];
                                        $html .= '<tr class="border-b border-gray-100">'
                                            . '<td class="py-1">' . e($sourceLabels[$inc['source'] ?? ''] ?? ucfirst($inc['source'] ?? '')) . '</td>'
                                            . '<td class="text-right py-1">$' . number_format($amount, 2) . '</td>'
                                            . '<td class="py-1 pl-3">' . e($freq ? IncomeFrequency::from($freq)->label() : 'N/A') . '</td>'
                                            . '<td class="text-right py-1">$' . number_format($annual, 2) . '</td>'
                                            . '</tr>';
                                    }

                                    $html .= '<tr class="font-bold"><td class="py-1">Total</td><td></td><td></td>'
                                        . '<td class="text-right py-1">$' . number_format($totalIncome, 2) . '</td></tr>';
                                    $html .= '</tbody></table>';
                                } else {
                                    $html .= '<p class="text-sm text-gray-500">No income reported</p>';
                                }

                                $fplLabel = $fplPercent !== null ? "{$fplPercent}% FPL" : 'FPL data unavailable';
                                $html .= '<div class="mt-3 font-medium">Eligibility: ' . $fplLabel . '</div>';

                                return new HtmlString($html);
                            }),
                    ]),

                Section::make('Program Enrollments')
                    ->schema([
                        Placeholder::make('review_enrollments')
                            ->label('')
                            ->content(function (): HtmlString {
                                $enrollments = $this->data['program_enrollments'] ?? [];

                                if (empty($enrollments)) {
                                    return new HtmlString('<p class="text-sm text-gray-500">No programs selected</p>');
                                }

                                $html = '<ul class="space-y-1">';
                                foreach ($enrollments as $e) {
                                    $program = Program::find($e['program_id'] ?? 0);
                                    $caseworker = User::find($e['caseworker_id'] ?? 0);
                                    $html .= '<li class="text-sm">'
                                        . '<span class="font-medium">' . e($program?->name ?? 'Unknown') . '</span>'
                                        . ' — enrolled ' . e(isset($e['enrolled_at']) ? date('m/d/Y', strtotime($e['enrolled_at'])) : 'N/A')
                                        . ' — caseworker: ' . e($caseworker?->name ?? 'Unassigned')
                                        . '</li>';
                                }
                                $html .= '</ul>';

                                return new HtmlString($html);
                            }),
                    ]),
            ]);
    }

    // -------------------------------------------------------------------------
    // Duplicate Detection
    // -------------------------------------------------------------------------

    public function runDuplicateCheck(): void
    {
        $data = $this->data;
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $dob = $data['date_of_birth'] ?? '';

        // Only check when we have enough data
        if (strlen($firstName) < 2 || strlen($lastName) < 2 || empty($dob)) {
            $this->duplicateWarning = null;

            return;
        }

        $query = Client::query()->complete();

        if ($this->clientId) {
            $query->where('id', '!=', $this->clientId);
        }

        $duplicates = $query->where(function ($q) use ($firstName, $lastName, $dob): void {
            $q->where(function ($sub) use ($firstName, $lastName, $dob): void {
                $sub->whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
                    ->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)])
                    ->whereDate('date_of_birth', $dob);
            });

            $ssn = $this->data['ssn_encrypted'] ?? '';
            $digits = preg_replace('/\D/', '', $ssn);
            if (strlen($digits) >= 4) {
                $lastFour = substr($digits, -4);
                $q->orWhere('ssn_last_four', $lastFour);
            }
        })->get(['id', 'first_name', 'last_name', 'middle_name', 'date_of_birth', 'ssn_last_four']);

        if ($duplicates->isEmpty()) {
            $this->duplicateWarning = null;

            return;
        }

        $this->duplicateWarning = $duplicates
            ->map(fn (Client $c): string => $c->fullName()
                . ' (DOB: ' . ($c->date_of_birth?->format('m/d/Y') ?? 'N/A')
                . ', SSN: ***-**-' . ($c->ssn_last_four ?? '????') . ')'
            )
            ->join("\n");
    }

    protected function checkDuplicates(): void
    {
        $data = $this->data;

        $query = Client::query()->complete();

        if ($this->clientId) {
            $query->where('id', '!=', $this->clientId);
        }

        $duplicates = $query->where(function ($q) use ($data): void {
            $q->where(function ($sub) use ($data): void {
                $sub->whereRaw('LOWER(first_name) = ?', [strtolower($data['first_name'] ?? '')])
                    ->whereRaw('LOWER(last_name) = ?', [strtolower($data['last_name'] ?? '')])
                    ->whereDate('date_of_birth', $data['date_of_birth'] ?? '');
            });

            $ssn = $data['ssn_encrypted'] ?? '';
            $digits = preg_replace('/\D/', '', $ssn);
            if (strlen($digits) >= 4) {
                $lastFour = substr($digits, -4);
                $q->orWhere('ssn_last_four', $lastFour);
            }
        })->get(['id', 'first_name', 'last_name', 'middle_name', 'date_of_birth', 'ssn_last_four']);

        if ($duplicates->isEmpty()) {
            $this->duplicateWarning = null;

            return;
        }

        if (! ($data['acknowledge_duplicates'] ?? false)) {
            $this->duplicateWarning = $duplicates
                ->map(fn (Client $c): string => $c->fullName()
                    . ' (DOB: ' . ($c->date_of_birth?->format('m/d/Y') ?? 'N/A')
                    . ', SSN: ***-**-' . ($c->ssn_last_four ?? '????') . ')'
                )
                ->join("\n");

            Notification::make()
                ->warning()
                ->title('Potential duplicates found')
                ->body('Review the matches and check the acknowledgment box to proceed.')
                ->persistent()
                ->send();

            throw new Halt();
        }
    }

    // -------------------------------------------------------------------------
    // Draft Save Methods
    // -------------------------------------------------------------------------

    protected function saveDraftStep1(): void
    {
        $data = $this->data;

        DB::transaction(function () use ($data): void {
            // If we don't have a clientId, check for an existing draft with the
            // same name+DOB to prevent duplicate drafts from page reloads.
            if (! $this->clientId) {
                $existingDraft = Client::draft()
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->whereDate('date_of_birth', $data['date_of_birth'] ?? '')
                    ->first();

                if ($existingDraft) {
                    $this->clientId = $existingDraft->id;
                }
            }

            if ($this->clientId) {
                $client = Client::find($this->clientId);
                $household = $client->household;

                $household->update([
                    'address_line_1' => $data['address_line_1'],
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'zip' => $data['zip'],
                    'county' => $data['county'] ?? null,
                ]);
            } else {
                $household = Household::create([
                    'address_line_1' => $data['address_line_1'],
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'zip' => $data['zip'],
                    'county' => $data['county'] ?? null,
                    'housing_type' => $data['housing_type'] ?? 'rented',
                    'household_size' => 1,
                ]);
            }

            $ssnRaw = $data['ssn_encrypted'] ?? '';
            $ssnDigits = preg_replace('/\D/', '', $ssnRaw);
            $ssnLastFour = strlen($ssnDigits) >= 4 ? substr($ssnDigits, -4) : null;

            $clientData = [
                'household_id' => $household->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'gender' => $data['gender'] ?? null,
                'race' => $data['race'] ?? null,
                'ethnicity' => $data['ethnicity'] ?? null,
                'is_veteran' => $data['is_veteran'] ?? false,
                'is_disabled' => $data['is_disabled'] ?? false,
                'is_head_of_household' => $data['is_head_of_household'] ?? true,
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'relationship_to_head' => $data['relationship_to_head'] ?? 'self',
                'intake_status' => IntakeStatus::Draft,
                'intake_step' => 2,
            ];

            if (! empty($ssnRaw)) {
                $clientData['ssn_encrypted'] = $ssnRaw;
                $clientData['ssn_last_four'] = $ssnLastFour;
            }

            if ($this->clientId) {
                $client->update($clientData);
            } else {
                $client = Client::create($clientData);
                $this->clientId = $client->id;
            }
        });

        // Update the browser URL so a page refresh preserves the draft context
        $this->js("
            const url = new URL(window.location);
            url.searchParams.set('client', {$this->clientId});
            window.history.replaceState({}, '', url);
        ");
    }

    protected function saveDraftStep2(): void
    {
        $data = $this->data;
        $client = Client::find($this->clientId);

        if (! $client) {
            $this->saveDraftStep1();
            $client = Client::find($this->clientId);
        }

        DB::transaction(function () use ($data, $client): void {
            // Handle household mode switch
            if (($data['household_mode'] ?? 'new') === 'existing' && ! empty($data['existing_household_id'])) {
                $oldHouseholdId = $client->household_id;
                $newHouseholdId = (int) $data['existing_household_id'];

                if ($oldHouseholdId !== $newHouseholdId) {
                    $client->update(['household_id' => $newHouseholdId]);

                    // Clean up the auto-created household if it has no other clients
                    $oldHousehold = Household::find($oldHouseholdId);
                    if ($oldHousehold && $oldHousehold->clients()->count() === 0 && $oldHousehold->members()->count() === 0) {
                        $oldHousehold->forceDelete();
                    }
                }
            }

            $household = $client->fresh()->household;

            // Update housing type and head-of-household
            $household->update([
                'housing_type' => $data['housing_type'] ?? $household->housing_type,
            ]);

            $client->update([
                'is_head_of_household' => $data['is_head_of_household'] ?? true,
                'relationship_to_head' => ($data['is_head_of_household'] ?? true) ? 'self' : ($data['relationship_to_head'] ?? null),
                'intake_step' => 3,
            ]);

            // Sync household members
            $household->members()->forceDelete();

            $members = $data['household_members'] ?? [];
            foreach ($members as $member) {
                if (empty($member['first_name']) || empty($member['last_name'])) {
                    continue;
                }

                $household->members()->create([
                    'first_name' => $member['first_name'],
                    'last_name' => $member['last_name'],
                    'date_of_birth' => $member['date_of_birth'] ?? null,
                    'relationship_to_client' => $member['relationship_to_client'],
                ]);
            }

            // Recalculate household size
            $household->recalculateSize();
        });
    }

    protected function saveDraftStep3(): void
    {
        $data = $this->data;
        $client = Client::find($this->clientId);

        DB::transaction(function () use ($data, $client): void {
            // Sync income records
            $client->incomeRecords()->forceDelete();

            $incomes = $data['income_sources'] ?? [];
            foreach ($incomes as $income) {
                if (empty($income['source']) || empty($income['amount'])) {
                    continue;
                }

                $client->incomeRecords()->create([
                    'source' => $income['source'],
                    'source_description' => $income['source_description'] ?? null,
                    'amount' => (float) $income['amount'],
                    'frequency' => $income['frequency'] ?? null,
                    'effective_date' => now(),
                    'is_verified' => false,
                ]);
            }

            $client->update(['intake_step' => 4]);
        });
    }

    protected function saveDraftStep4(): void
    {
        $data = $this->data;
        $client = Client::find($this->clientId);

        DB::transaction(function () use ($data, $client): void {
            // Sync enrollments
            $client->enrollments()->forceDelete();

            $enrollments = $data['program_enrollments'] ?? [];
            $totalIncome = $this->calculateTotalIncome($data['income_sources'] ?? []);
            $householdSize = count($data['household_members'] ?? []) + 1;
            $fplPercent = FederalPovertyLevel::fplPercent($totalIncome, $householdSize);

            foreach ($enrollments as $enrollment) {
                if (empty($enrollment['program_id'])) {
                    continue;
                }

                $program = Program::find($enrollment['program_id']);
                if (! $program) {
                    continue;
                }

                $incomeEligible = ! $program->requires_income_eligibility
                    || ($fplPercent !== null && $fplPercent <= $program->fpl_threshold_percent);

                $client->enrollments()->create([
                    'program_id' => $enrollment['program_id'],
                    'caseworker_id' => $enrollment['caseworker_id'],
                    'status' => EnrollmentStatus::Pending,
                    'enrolled_at' => $enrollment['enrolled_at'],
                    'household_income_at_enrollment' => $totalIncome,
                    'household_size_at_enrollment' => $householdSize,
                    'fpl_percent_at_enrollment' => $fplPercent,
                    'income_eligible' => $incomeEligible,
                ]);
            }

            $client->update(['intake_step' => 5]);
        });
    }

    // -------------------------------------------------------------------------
    // Final Submission
    // -------------------------------------------------------------------------

    public function submit(): void
    {
        $this->form->getState(); // validate all steps

        if (! $this->clientId) {
            Notification::make()
                ->danger()
                ->title('No client data found')
                ->body('Please complete all steps before submitting.')
                ->send();

            return;
        }

        $client = Client::find($this->clientId);
        $client->update([
            'intake_status' => IntakeStatus::Complete,
            'intake_step' => 5,
        ]);

        // Activate pending enrollments
        $client->enrollments()
            ->where('status', EnrollmentStatus::Pending)
            ->update(['status' => EnrollmentStatus::Active->value]);

        // Clean up any other orphaned draft clients (e.g., from abandoned duplicate tests)
        $orphanedDrafts = Client::draft()
            ->where('id', '!=', $client->id)
            ->where('first_name', $client->first_name)
            ->where('last_name', $client->last_name)
            ->get();

        foreach ($orphanedDrafts as $orphan) {
            $orphanHousehold = $orphan->household;
            $orphan->enrollments()->forceDelete();
            $orphan->incomeRecords()->forceDelete();
            $orphan->forceDelete();
            if ($orphanHousehold && $orphanHousehold->clients()->count() === 0) {
                $orphanHousehold->members()->forceDelete();
                $orphanHousehold->forceDelete();
            }
        }

        Notification::make()
            ->success()
            ->title('Intake completed')
            ->body('Client ' . $client->fullName() . ' has been successfully added.')
            ->send();

        $this->redirect(
            \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $client]),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getDraftClients(): Collection
    {
        return Client::draft()
            ->latest()
            ->limit(5)
            ->get();
    }

    protected function calculateTotalIncome(array $sources): float
    {
        $total = 0.0;

        foreach ($sources as $source) {
            $amount = (float) ($source['amount'] ?? 0);
            $freq = $source['frequency'] ?? null;

            if ($freq && $amount > 0) {
                $total += $amount * IncomeFrequency::from($freq)->annualMultiplier();
            } elseif ($amount > 0) {
                $total += $amount;
            }
        }

        return round($total, 2);
    }

    protected function buildReviewTable(array $rows): string
    {
        $html = '<table class="w-full text-sm">';
        foreach ($rows as $label => $value) {
            $html .= '<tr class="border-b border-gray-100 dark:border-gray-800">'
                . '<td class="py-1.5 pr-4 font-medium text-gray-600 dark:text-gray-400 w-1/3">' . e($label) . '</td>'
                . '<td class="py-1.5">' . $value . '</td>'
                . '</tr>';
        }
        $html .= '</table>';

        return $html;
    }
}

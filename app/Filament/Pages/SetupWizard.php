<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\AgencySetting;
use App\Models\Program;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
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
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

class SetupWizard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Setup';

    protected static ?string $title = 'Initial Setup';

    protected string $view = 'filament.pages.setup-wizard';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    protected static ?string $slug = 'setup';

    public static function canAccess(): bool
    {
        // Always accessible — mount() handles redirection if setup is already complete
        return true;
    }

    public function mount(): void
    {
        if (AgencySetting::isSetupComplete()) {
            $this->redirect('/admin');

            return;
        }

        $settings = AgencySetting::current();
        $programs = Program::all();

        $this->form->fill([
            'agency_name' => $settings?->agency_name ?? '',
            'agency_address_line_1' => $settings?->agency_address_line_1 ?? '',
            'agency_address_line_2' => $settings?->agency_address_line_2 ?? '',
            'agency_city' => $settings?->agency_city ?? '',
            'agency_state' => $settings?->agency_state ?? '',
            'agency_zip' => $settings?->agency_zip ?? '',
            'agency_county' => $settings?->agency_county ?? '',
            'agency_phone' => $settings?->agency_phone ?? '',
            'agency_ein' => $settings?->agency_ein ?? '',
            'agency_website' => $settings?->agency_website ?? '',
            'executive_director_name' => $settings?->executive_director_name ?? '',
            'primary_color' => $settings?->primary_color ?? '#3b82f6',
            'fiscal_year_start_month' => $settings?->fiscal_year_start_month ?? 10,
            'admin_name' => '',
            'admin_email' => '',
            'admin_password' => '',
            'admin_password_confirmation' => '',
            'mail_host' => $settings?->mail_host ?? '',
            'mail_port' => $settings?->mail_port ?? 587,
            'mail_username' => $settings?->mail_username ?? '',
            'mail_password' => '',
            'mail_encryption' => $settings?->mail_encryption ?? 'tls',
            'mail_from_address' => $settings?->mail_from_address ?? '',
            'mail_from_name' => $settings?->mail_from_name ?? '',
            'programs' => $programs->map(fn (Program $p) => [
                'program_id' => $p->id,
                'program_name' => $p->name,
                'program_code' => $p->code,
                'fpl_threshold_percent' => $p->fpl_threshold_percent,
                'requires_income_eligibility' => $p->requires_income_eligibility,
                'is_active' => $p->is_active,
            ])->toArray(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make($this->getSteps())
                    ->submitAction(new HtmlString(
                        '<button type="submit" class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50">Complete Setup</button>'
                    )),
            ])
            ->statePath('data');
    }

    protected function getSteps(): array
    {
        $steps = [
            Step::make('Agency Identity')
                ->icon('heroicon-o-building-office')
                ->description('Basic information about your agency')
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('agency_name')
                                ->label('Agency Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Community Action Agency of Example County'),

                            TextInput::make('agency_address_line_1')
                                ->label('Address Line 1')
                                ->maxLength(255),

                            TextInput::make('agency_address_line_2')
                                ->label('Address Line 2')
                                ->maxLength(255),

                            TextInput::make('agency_city')
                                ->label('City')
                                ->maxLength(255),

                            TextInput::make('agency_state')
                                ->label('State')
                                ->maxLength(2)
                                ->placeholder('PA'),

                            TextInput::make('agency_zip')
                                ->label('ZIP Code')
                                ->maxLength(10),

                            TextInput::make('agency_county')
                                ->label('County')
                                ->maxLength(255),

                            TextInput::make('agency_phone')
                                ->label('Phone')
                                ->tel()
                                ->maxLength(20),

                            TextInput::make('agency_ein')
                                ->label('EIN')
                                ->maxLength(20)
                                ->placeholder('XX-XXXXXXX'),

                            TextInput::make('agency_website')
                                ->label('Website')
                                ->url()
                                ->maxLength(255),

                            TextInput::make('executive_director_name')
                                ->label('Executive Director')
                                ->maxLength(255),
                        ])
                        ->columns(2),
                ]),

            Step::make('Branding')
                ->icon('heroicon-o-paint-brush')
                ->description('Logo and color scheme')
                ->schema([
                    Section::make()
                        ->schema([
                            FileUpload::make('logo')
                                ->label('Agency Logo')
                                ->image()
                                ->directory('logos')
                                ->disk('public')
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                                ->helperText('Upload a PNG, JPG, or SVG file (max 2MB).'),

                            ColorPicker::make('primary_color')
                                ->label('Primary Color')
                                ->default('#3b82f6')
                                ->helperText('This color will be used throughout the application for buttons, links, and highlights.'),
                        ]),
                ]),
        ];

        // Only show admin user step if no users exist
        if (User::count() === 0) {
            $steps[] = Step::make('Admin Account')
                ->icon('heroicon-o-user-plus')
                ->description('Create the first administrator account')
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('admin_name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('admin_email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('admin_password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(10)
                                ->rule('regex:/[a-z]/')
                                ->rule('regex:/[A-Z]/')
                                ->rule('regex:/[0-9]/')
                                ->validationMessages([
                                    'regex' => 'Password must contain uppercase, lowercase, and a number.',
                                    'min' => 'Password must be at least 10 characters.',
                                ])
                                ->helperText('Min 10 characters, mixed case, at least one number.')
                                ->confirmed(),

                            TextInput::make('admin_password_confirmation')
                                ->label('Confirm Password')
                                ->password()
                                ->revealable()
                                ->required(),
                        ])
                        ->columns(2),
                ]);
        }

        $steps[] = Step::make('Programs')
            ->icon('heroicon-o-rectangle-stack')
            ->description('Review and configure available programs')
            ->schema([
                Placeholder::make('programs_info')
                    ->content('These programs were pre-configured during installation. You can rename, deactivate, remove, or add programs as needed.')
                    ->columnSpanFull(),

                Repeater::make('programs')
                    ->schema([
                        TextInput::make('program_id')
                            ->hidden(),

                        TextInput::make('program_name')
                            ->label('Program Name')
                            ->required(),

                        TextInput::make('program_code')
                            ->label('Code')
                            ->maxLength(10)
                            ->placeholder('e.g., CSBG'),

                        TextInput::make('fpl_threshold_percent')
                            ->label('FPL Threshold %')
                            ->numeric()
                            ->default(200)
                            ->minValue(0)
                            ->maxValue(500)
                            ->suffix('%')
                            ->helperText('Max income as % of Federal Poverty Level (e.g., 150 = 150% FPL)'),

                        Toggle::make('requires_income_eligibility')
                            ->label('Requires Income Eligibility')
                            ->default(true),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(3)
                    ->addActionLabel('Add Program')
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ]);

        $steps[] = Step::make('Fiscal Year')
            ->icon('heroicon-o-calendar')
            ->description('Configure your fiscal year')
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('fiscal_year_start_month')
                            ->label('Fiscal Year Start Month')
                            ->options([
                                1 => 'January',
                                2 => 'February',
                                3 => 'March',
                                4 => 'April',
                                5 => 'May',
                                6 => 'June',
                                7 => 'July',
                                8 => 'August',
                                9 => 'September',
                                10 => 'October',
                                11 => 'November',
                                12 => 'December',
                            ])
                            ->default(10)
                            ->required()
                            ->helperText('Most Community Action Agencies use the federal fiscal year (October). This affects NPI report defaults.'),
                    ]),
            ]);

        $steps[] = Step::make('Email (Optional)')
            ->icon('heroicon-o-envelope')
            ->description('Configure outgoing email for notifications and password resets')
            ->schema([
                Section::make()
                    ->schema([
                        Placeholder::make('mail_info')
                            ->content('Configure SMTP settings to enable password reset emails and notifications. You can skip this step and configure it later in Settings.')
                            ->columnSpanFull(),

                        TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->maxLength(255)
                            ->placeholder('smtp.gmail.com'),

                        TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->default(587)
                            ->minValue(1)
                            ->maxValue(65535),

                        TextInput::make('mail_username')
                            ->label('SMTP Username')
                            ->maxLength(255),

                        TextInput::make('mail_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS (recommended)',
                                'ssl' => 'SSL',
                                '' => 'None',
                            ])
                            ->default('tls'),

                        TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('noreply@youragency.org'),

                        TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->maxLength(255)
                            ->placeholder('CAPIntake'),
                    ])
                    ->columns(2),
            ]);

        return $steps;
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Create or update agency settings
        $settings = AgencySetting::first() ?? new AgencySetting;
        $settings->fill([
            'agency_name' => $data['agency_name'],
            'agency_address_line_1' => $data['agency_address_line_1'] ?? null,
            'agency_address_line_2' => $data['agency_address_line_2'] ?? null,
            'agency_city' => $data['agency_city'] ?? null,
            'agency_state' => $data['agency_state'] ?? null,
            'agency_zip' => $data['agency_zip'] ?? null,
            'agency_county' => $data['agency_county'] ?? null,
            'agency_phone' => $data['agency_phone'] ?? null,
            'agency_ein' => $data['agency_ein'] ?? null,
            'agency_website' => $data['agency_website'] ?? null,
            'executive_director_name' => $data['executive_director_name'] ?? null,
            'primary_color' => $data['primary_color'] ?? '#3b82f6',
            'fiscal_year_start_month' => $data['fiscal_year_start_month'] ?? 10,
            'setup_completed' => true,
        ]);

        // Save mail settings if provided
        if (! empty($data['mail_host'])) {
            $settings->fill([
                'mail_mailer' => 'smtp',
                'mail_host' => $data['mail_host'],
                'mail_port' => (int) ($data['mail_port'] ?? 587),
                'mail_username' => $data['mail_username'] ?? null,
                'mail_password' => $data['mail_password'] ?? null,
                'mail_encryption' => $data['mail_encryption'] ?? 'tls',
                'mail_from_address' => $data['mail_from_address'] ?? null,
                'mail_from_name' => $data['mail_from_name'] ?? $data['agency_name'],
            ]);
        }

        // Handle logo upload
        if (! empty($data['logo'])) {
            $logoPath = is_array($data['logo']) ? reset($data['logo']) : $data['logo'];
            $settings->logo_path = $logoPath;
        }

        $settings->save();

        // Create admin user if provided
        if (! empty($data['admin_email']) && User::count() === 0) {
            $user = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role' => UserRole::Admin,
                'is_active' => true,
            ]);

            auth()->login($user);
        }

        // Sync programs: update existing, create new, deactivate removed
        if (! empty($data['programs'])) {
            $submittedIds = [];

            foreach ($data['programs'] as $programData) {
                if (! empty($programData['program_id'])) {
                    // Update existing program
                    Program::where('id', $programData['program_id'])->update([
                        'name' => $programData['program_name'],
                        'fpl_threshold_percent' => (int) ($programData['fpl_threshold_percent'] ?? 200),
                        'requires_income_eligibility' => $programData['requires_income_eligibility'] ?? true,
                        'is_active' => $programData['is_active'] ?? true,
                    ]);
                    $submittedIds[] = $programData['program_id'];
                } else {
                    // Create new program
                    $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $programData['program_code'] ?? $programData['program_name']), 0, 10));
                    $code = $code ?: 'PROG'.random_int(100, 999);

                    // Ensure unique code
                    $baseCode = $code;
                    $suffix = 2;
                    while (Program::where('code', $code)->exists()) {
                        $code = substr($baseCode, 0, 7).$suffix;
                        $suffix++;
                    }

                    $program = Program::create([
                        'name' => $programData['program_name'],
                        'code' => $code,
                        'fpl_threshold_percent' => (int) ($programData['fpl_threshold_percent'] ?? 200),
                        'requires_income_eligibility' => $programData['requires_income_eligibility'] ?? true,
                        'is_active' => $programData['is_active'] ?? true,
                    ]);
                    $submittedIds[] = $program->id;
                }
            }

            // Deactivate seeded programs that were removed from the list
            Program::whereNotIn('id', $submittedIds)->update(['is_active' => false]);
        }

        Notification::make()
            ->title('Setup complete!')
            ->body("Welcome to {$settings->agency_name}. Your system is ready to use.")
            ->success()
            ->send();

        $this->redirect(url('/admin'));
    }
}

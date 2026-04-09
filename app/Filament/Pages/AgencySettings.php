<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\AgencySetting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AgencySettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Agency Settings';

    protected static ?string $title = 'Agency Settings';

    protected string $view = 'filament.pages.agency-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->role === UserRole::Admin;
    }

    public function mount(): void
    {
        $settings = AgencySetting::current() ?? new AgencySetting;

        $this->form->fill([
            'agency_name' => $settings->agency_name ?? '',
            'agency_address_line_1' => $settings->agency_address_line_1 ?? '',
            'agency_address_line_2' => $settings->agency_address_line_2 ?? '',
            'agency_city' => $settings->agency_city ?? '',
            'agency_state' => $settings->agency_state ?? '',
            'agency_zip' => $settings->agency_zip ?? '',
            'agency_county' => $settings->agency_county ?? '',
            'agency_phone' => $settings->agency_phone ?? '',
            'agency_ein' => $settings->agency_ein ?? '',
            'agency_website' => $settings->agency_website ?? '',
            'executive_director_name' => $settings->executive_director_name ?? '',
            'primary_color' => $settings->primary_color ?? '#3b82f6',
            'logo' => $settings->logo_path ? [$settings->logo_path] : [],
            'fiscal_year_start_month' => $settings->fiscal_year_start_month ?? 10,
            'mail_host' => $settings->mail_host ?? '',
            'mail_port' => $settings->mail_port ?? 587,
            'mail_username' => $settings->mail_username ?? '',
            'mail_password' => '',
            'mail_encryption' => $settings->mail_encryption ?? 'tls',
            'mail_from_address' => $settings->mail_from_address ?? '',
            'mail_from_name' => $settings->mail_from_name ?? '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Agency Identity')
                    ->schema([
                        TextInput::make('agency_name')
                            ->label('Agency Name')
                            ->required()
                            ->maxLength(255),

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

                Section::make('Branding')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->default('#3b82f6')
                            ->lazy(),

                        FileUpload::make('logo')
                            ->label('Agency Logo')
                            ->image()
                            ->directory('logos')
                            ->disk('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                            ->helperText('Upload a PNG, JPG, or SVG file (max 2MB).'),
                    ])
                    ->columns(2),

                Section::make('Fiscal Year')
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
                            ->required(),
                    ]),

                Section::make('Email (SMTP)')
                    ->description('Configure outgoing email for password resets and notifications.')
                    ->schema([
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
                            ->maxLength(255)
                            ->helperText('Leave blank to keep the existing password.'),

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
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

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
        ]);

        // Handle logo upload
        if (! empty($data['logo'])) {
            $logoPath = is_array($data['logo']) ? reset($data['logo']) : $data['logo'];
            $settings->logo_path = $logoPath;
        }

        // Handle mail settings
        if (! empty($data['mail_host'])) {
            $settings->fill([
                'mail_mailer' => 'smtp',
                'mail_host' => $data['mail_host'],
                'mail_port' => (int) ($data['mail_port'] ?? 587),
                'mail_username' => $data['mail_username'] ?? null,
                'mail_encryption' => $data['mail_encryption'] ?? 'tls',
                'mail_from_address' => $data['mail_from_address'] ?? null,
                'mail_from_name' => $data['mail_from_name'] ?? $data['agency_name'],
            ]);

            // Only update password if a new one was provided
            if (! empty($data['mail_password'])) {
                $settings->mail_password = $data['mail_password'];
            }
        }

        $settings->save();

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('Agency settings have been updated.')
            ->send();
    }
}

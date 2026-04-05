<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\CsbgReportSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CsbgReportSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'CSBG Report Settings';

    protected static ?string $title = 'CSBG Report Settings';

    protected string $view = 'filament.pages.csbg-report-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'System Configuration';

    protected static ?int $navigationSort = 22;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->role === UserRole::Admin;
    }

    public function mount(): void
    {
        $settings = CsbgReportSetting::current();

        $this->form->fill([
            'entity_name' => $settings->entity_name,
            'state' => $settings->state,
            'uei' => $settings->uei,
            'reporting_period' => $settings->reporting_period,
            'current_fiscal_year' => $settings->current_fiscal_year,
            'total_csbg_allocation' => $settings->total_csbg_allocation,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Agency Information')
                    ->schema([
                        TextInput::make('entity_name')
                            ->label('Entity Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The legal name of your agency as it appears on the CSBG Annual Report.'),

                        TextInput::make('state')
                            ->label('State')
                            ->required()
                            ->maxLength(2)
                            ->placeholder('PA'),

                        TextInput::make('uei')
                            ->label('Unique Entity Identifier (UEI)')
                            ->maxLength(12)
                            ->helperText('12-character alphanumeric ID from SAM.gov.'),
                    ])
                    ->columns(3),

                Section::make('Reporting Configuration')
                    ->schema([
                        Select::make('reporting_period')
                            ->label('Reporting Period')
                            ->options([
                                'jul_jun' => 'July - June',
                                'oct_sep' => 'October - September',
                                'jan_dec' => 'January - December',
                            ])
                            ->required()
                            ->helperText('The fiscal year your agency uses for CSBG reporting.'),

                        TextInput::make('current_fiscal_year')
                            ->label('Current Fiscal Year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2040)
                            ->helperText('The year associated with the current reporting period.'),
                    ])
                    ->columns(2),

                Section::make('Financial')
                    ->schema([
                        TextInput::make('total_csbg_allocation')
                            ->label('Total CSBG Allocation')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->helperText('Total CSBG funding allocation for the current fiscal year.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = CsbgReportSetting::current();
        $settings->update($data);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('CSBG Report Settings have been updated.')
            ->send();
    }
}

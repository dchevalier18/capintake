<?php

declare(strict_types=1);

namespace App\Filament\Resources\FederalPovertyLevelResource\Pages;

use App\Filament\Resources\FederalPovertyLevelResource;
use App\Models\FederalPovertyLevel;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CreateFederalPovertyLevel extends Page
{
    protected static string $resource = FederalPovertyLevelResource::class;

    protected static ?string $title = 'Add FPL Guidelines for a New Year';

    protected string $view = 'filament.pages.create-fpl';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'year' => now()->year,
            'region' => 'continental',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Year & Region')
                    ->schema([
                        TextInput::make('year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2040)
                            ->default(now()->year),

                        Select::make('region')
                            ->options([
                                'continental' => 'Continental US (48 States + DC)',
                                'alaska' => 'Alaska',
                                'hawaii' => 'Hawaii',
                            ])
                            ->required()
                            ->default('continental'),
                    ])
                    ->columns(2),

                Section::make('Poverty Guidelines by Household Size')
                    ->description('Enter the annual poverty guideline amount for each household size.')
                    ->schema([
                        ...collect(range(1, 8))->map(fn (int $size) => TextInput::make("size_{$size}")
                            ->label("Household Size {$size}")
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(1)
                            ->live(onBlur: true)
                        )->toArray(),

                        Placeholder::make('per_person_increment')
                            ->label('Per-Person Increment (Size 8 - Size 7)')
                            ->content(function (Get $get): HtmlString {
                                $size7 = (int) ($get('size_7') ?? 0);
                                $size8 = (int) ($get('size_8') ?? 0);
                                if ($size7 > 0 && $size8 > $size7) {
                                    $increment = $size8 - $size7;
                                    return new HtmlString('<span class="text-lg font-bold text-success-600">$' . number_format($increment) . '</span> <span class="text-sm text-gray-500">(used for households larger than 8)</span>');
                                }
                                return new HtmlString('<span class="text-gray-400">Enter sizes 7 and 8 to calculate</span>');
                            }),
                    ])
                    ->columns(4),
            ]);
    }

    public function copyFromPreviousYear(): void
    {
        $region = $this->data['region'] ?? 'continental';
        $latestYear = FederalPovertyLevel::where('region', $region)
            ->max('year');

        if (! $latestYear) {
            Notification::make()
                ->warning()
                ->title('No previous data')
                ->body("No FPL data found for {$region} region to copy from.")
                ->send();
            return;
        }

        $existing = FederalPovertyLevel::where('year', $latestYear)
            ->where('region', $region)
            ->orderBy('household_size')
            ->get();

        $fill = [];
        foreach ($existing as $fpl) {
            $fill["size_{$fpl->household_size}"] = $fpl->poverty_guideline;
        }

        $this->form->fill(array_merge($this->data, $fill));

        Notification::make()
            ->success()
            ->title("Copied from {$latestYear}")
            ->body('Update the amounts for the new year, then save.')
            ->send();
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $year = (int) $data['year'];
        $region = $data['region'];

        // Validate that sizes increase
        for ($i = 2; $i <= 8; $i++) {
            if ((int) $data["size_{$i}"] <= (int) $data['size_' . ($i - 1)]) {
                Notification::make()
                    ->danger()
                    ->title('Invalid amounts')
                    ->body("Household Size {$i} must be greater than Size " . ($i - 1) . ".")
                    ->send();
                return;
            }
        }

        // Check for existing data
        $exists = FederalPovertyLevel::where('year', $year)
            ->where('region', $region)
            ->exists();

        if ($exists) {
            // Update existing
            for ($size = 1; $size <= 8; $size++) {
                FederalPovertyLevel::updateOrCreate(
                    ['year' => $year, 'region' => $region, 'household_size' => $size],
                    ['poverty_guideline' => (int) $data["size_{$size}"]]
                );
            }
            $verb = 'updated';
        } else {
            // Create new
            for ($size = 1; $size <= 8; $size++) {
                FederalPovertyLevel::create([
                    'year' => $year,
                    'region' => $region,
                    'household_size' => $size,
                    'poverty_guideline' => (int) $data["size_{$size}"],
                ]);
            }
            $verb = 'created';
        }

        Notification::make()
            ->success()
            ->title("FPL Guidelines {$verb}")
            ->body("{$year} {$region} guidelines have been {$verb} for all 8 household sizes.")
            ->send();

        $this->redirect(FederalPovertyLevelResource::getUrl('index'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_previous')
                ->label('Copy from Previous Year')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->action('copyFromPreviousYear'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\FnpiTarget;
use App\Models\NpiGoal;
use App\Models\NpiIndicator;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class FnpiTargets extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'FNPI Targets';

    protected static ?string $title = 'FNPI Indicator Targets';

    protected string $view = 'filament.pages.fnpi-targets';

    protected static string|\UnitEnum|null $navigationGroup = 'CSBG Reports';

    protected static ?int $navigationSort = 3;

    public int $fiscalYear;

    public array $targets = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function mount(): void
    {
        $this->fiscalYear = now()->month >= 10 ? now()->year + 1 : now()->year;
        $this->loadTargets();
    }

    public function updatedFiscalYear(): void
    {
        $this->loadTargets();
    }

    protected function loadTargets(): void
    {
        $indicators = NpiIndicator::whereNull('parent_indicator_id')
            ->with('goal')
            ->orderBy('npi_goal_id')
            ->orderBy('indicator_code')
            ->get();

        $existingTargets = FnpiTarget::where('fiscal_year', $this->fiscalYear)
            ->pluck('target_count', 'npi_indicator_id')
            ->toArray();

        $this->targets = $indicators->map(fn (NpiIndicator $ind) => [
            'indicator_id' => $ind->id,
            'code' => $ind->indicator_code,
            'name' => $ind->name,
            'goal_number' => $ind->goal->goal_number,
            'goal_name' => $ind->goal->name,
            'target' => $existingTargets[$ind->id] ?? 0,
        ])->toArray();
    }

    public function saveTargets(): void
    {
        foreach ($this->targets as $target) {
            if ((int) $target['target'] > 0) {
                FnpiTarget::updateOrCreate(
                    ['npi_indicator_id' => $target['indicator_id'], 'fiscal_year' => $this->fiscalYear],
                    ['target_count' => (int) $target['target']]
                );
            } else {
                FnpiTarget::where('npi_indicator_id', $target['indicator_id'])
                    ->where('fiscal_year', $this->fiscalYear)
                    ->delete();
            }
        }

        Notification::make()
            ->success()
            ->title('Targets saved')
            ->body("FNPI targets for FFY {$this->fiscalYear} have been saved.")
            ->send();
    }

    public function copyFromPreviousYear(): void
    {
        $previousYear = $this->fiscalYear - 1;
        $previous = FnpiTarget::where('fiscal_year', $previousYear)
            ->pluck('target_count', 'npi_indicator_id')
            ->toArray();

        if (empty($previous)) {
            Notification::make()
                ->warning()
                ->title('No previous targets')
                ->body("No targets found for FFY {$previousYear}.")
                ->send();

            return;
        }

        foreach ($this->targets as &$target) {
            if (isset($previous[$target['indicator_id']])) {
                $target['target'] = $previous[$target['indicator_id']];
            }
        }
        unset($target);

        Notification::make()
            ->success()
            ->title("Copied from FFY {$previousYear}")
            ->body('Targets loaded. Click "Save All" to persist.')
            ->send();
    }
}

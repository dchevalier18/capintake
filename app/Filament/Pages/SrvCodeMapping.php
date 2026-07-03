<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\CsbgSrvCategory;
use App\Models\Service;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SrvCodeMapping extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'SRV Code Mapping';

    protected static ?string $title = 'Map Services to SRV Codes';

    protected string $view = 'filament.pages.srv-code-mapping';

    protected static string|\UnitEnum|null $navigationGroup = 'CSBG Reports';

    protected static ?int $navigationSort = 6;

    public array $mappings = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, [UserRole::Admin, UserRole::Supervisor]);
    }

    public function mount(): void
    {
        $this->loadMappings();
    }

    protected function loadMappings(): void
    {
        $categories = CsbgSrvCategory::forVersion()->orderBy('sort_order')
            ->get()
            ->map(fn (CsbgSrvCategory $cat) => [
                'id' => $cat->id,
                'code' => $cat->code,
                'domain' => $cat->domain,
                'group_name' => $cat->group_name,
                'name' => $cat->name,
                'service_ids' => $cat->services()->pluck('services.id')->toArray(),
            ])
            ->toArray();

        $this->mappings = $categories;
    }

    public function getServiceOptions(): array
    {
        return Service::with('program')
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Service $s) => [
                $s->id => $s->program->code.' — '.$s->name.' ('.$s->code.')',
            ])
            ->toArray();
    }

    public function saveMappings(): void
    {
        foreach ($this->mappings as $mapping) {
            $category = CsbgSrvCategory::find($mapping['id']);
            if ($category) {
                $category->services()->sync($mapping['service_ids'] ?? []);
            }
        }

        Notification::make()
            ->success()
            ->title('Mappings saved')
            ->body('Service-to-SRV code mappings have been updated.')
            ->send();
    }

    /**
     * Copy service mappings from version 2.1 categories to same-coded
     * categories in the active version. Used after loading the 3.0 taxonomy
     * so agencies don't re-map every service by hand.
     */
    public function copyMappingsFromV21(): void
    {
        $activeVersion = CsbgSrvCategory::activeReportVersion();

        if ($activeVersion === '2.1') {
            Notification::make()
                ->warning()
                ->title('Nothing to copy')
                ->body('Version 2.1 is already the active report version.')
                ->send();

            return;
        }

        $copied = 0;
        $sourceByCode = CsbgSrvCategory::forVersion('2.1')->with('services')->get()->keyBy('code');

        CsbgSrvCategory::forVersion($activeVersion)->get()->each(function (CsbgSrvCategory $target) use ($sourceByCode, &$copied): void {
            $source = $sourceByCode->get($target->code);
            if ($source && $source->services->isNotEmpty()) {
                $target->services()->syncWithoutDetaching($source->services->pluck('id')->all());
                $copied++;
            }
        });

        $this->loadMappings();

        Notification::make()
            ->success()
            ->title('Mappings copied')
            ->body("Copied 2.1 service mappings to {$copied} matching {$activeVersion} categories.")
            ->send();
    }
}

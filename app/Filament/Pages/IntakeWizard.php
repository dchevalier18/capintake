<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\IntakeStatus;
use App\Filament\Pages\IntakeWizard\Steps\ClientInfoStep;
use App\Filament\Pages\IntakeWizard\Steps\EnrollmentStep;
use App\Filament\Pages\IntakeWizard\Steps\HouseholdStep;
use App\Filament\Pages\IntakeWizard\Steps\IncomeStep;
use App\Filament\Pages\IntakeWizard\Steps\ReviewStep;
use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\Enrollment;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Services\Intake\IntakeDraftPersister;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Collection;
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
            'housing_type' => 'rent',
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
            'gender' => $m->gender,
            'employment_status' => $m->employment_status,
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
                    ClientInfoStep::make($this),
                    HouseholdStep::make($this),
                    IncomeStep::make($this),
                    EnrollmentStep::make($this),
                    ReviewStep::make($this),
                ])
                    ->persistStepInQueryString('step')
                    ->submitAction(new HtmlString(
                        '<button type="submit" class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center gap-1.5 outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 dark:bg-custom-500 dark:hover:bg-custom-400 focus-visible:ring-custom-500/50 dark:focus-visible:ring-custom-400/50" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">Complete Intake</button>'
                    )),
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

        // Only check when we have enough data
        if (strlen($firstName) < 2 || strlen($lastName) < 2) {
            $this->duplicateWarning = null;

            return;
        }

        $query = Client::query()->complete();

        if ($this->clientId) {
            $query->where('id', '!=', $this->clientId);
        }

        $duplicates = $query->where(function ($q) use ($firstName, $lastName): void {
            // Name match (DOB is encrypted and cannot be queried directly)
            $q->where(function ($sub) use ($firstName, $lastName): void {
                $sub->whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
                    ->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)]);
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
                .' (DOB: '.($c->date_of_birth?->format('m/d/Y') ?? 'N/A')
                .', SSN: ***-**-'.($c->ssn_last_four ?? '????').')'
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
            // Name match (DOB is encrypted and cannot be queried directly)
            $q->where(function ($sub) use ($data): void {
                $sub->whereRaw('LOWER(first_name) = ?', [strtolower($data['first_name'] ?? '')])
                    ->whereRaw('LOWER(last_name) = ?', [strtolower($data['last_name'] ?? '')]);
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
                    .' (DOB: '.($c->date_of_birth?->format('m/d/Y') ?? 'N/A')
                    .', SSN: ***-**-'.($c->ssn_last_four ?? '????').')'
                )
                ->join("\n");

            Notification::make()
                ->warning()
                ->title('Potential duplicates found')
                ->body('Review the matches and check the acknowledgment box to proceed.')
                ->persistent()
                ->send();

            throw new Halt;
        }
    }

    // -------------------------------------------------------------------------
    // Draft Save Methods (delegating to IntakeDraftPersister)
    // -------------------------------------------------------------------------

    public function saveDraftStep1(): void
    {
        $this->clientId = $this->persister()->saveStep1($this->data, $this->clientId);

        // Update the browser URL so a page refresh preserves the draft context
        $this->js("
            const url = new URL(window.location);
            url.searchParams.set('client', {$this->clientId});
            window.history.replaceState({}, '', url);
        ");
    }

    public function saveDraftStep2(): void
    {
        $this->clientId = $this->persister()->saveStep2($this->data, $this->clientId);
    }

    public function saveDraftStep3(): void
    {
        $this->persister()->saveStep3($this->data, $this->clientId);
    }

    public function saveDraftStep4(): void
    {
        $this->persister()->saveStep4($this->data, $this->clientId);
    }

    protected function persister(): IntakeDraftPersister
    {
        return app(IntakeDraftPersister::class);
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
            ->body('Client '.$client->fullName().' has been successfully added.')
            ->send();

        $this->redirect(
            ClientResource::getUrl('edit', ['record' => $client]),
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

    public function calculateTotalIncome(array $sources): float
    {
        return IntakeDraftPersister::totalAnnualIncome($sources);
    }
}

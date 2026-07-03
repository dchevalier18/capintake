<?php

declare(strict_types=1);

namespace App\Services\Intake;

use App\Enums\EnrollmentStatus;
use App\Enums\IncomeFrequency;
use App\Enums\IntakeStatus;
use App\Models\Client;
use App\Models\FederalPovertyLevel;
use App\Models\Household;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

/**
 * Persists intake wizard drafts after each step. Extracted from IntakeWizard
 * so the page stays focused on form definition and flow control.
 */
class IntakeDraftPersister
{
    /**
     * Save step 1 (client info) and return the draft client id.
     */
    public function saveStep1(array $data, ?int $clientId): int
    {
        DB::transaction(function () use ($data, &$clientId): void {
            // If we don't have a clientId, check for an existing draft with the
            // same name+DOB to prevent duplicate drafts from page reloads.
            if (! $clientId) {
                // Match on name only — DOB is encrypted and cannot be queried
                $existingDraft = Client::draft()
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->first();

                if ($existingDraft) {
                    $clientId = $existingDraft->id;
                }
            }

            if ($clientId) {
                $client = Client::find($clientId);
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

            if ($clientId) {
                $client->update($clientData);
            } else {
                $client = Client::create($clientData);
                $clientId = $client->id;
            }
        });

        return $clientId;
    }

    /**
     * Save step 2 (household + members) and return the client id.
     */
    public function saveStep2(array $data, ?int $clientId): int
    {
        $client = Client::find($clientId);

        if (! $client) {
            $clientId = $this->saveStep1($data, $clientId);
            $client = Client::find($clientId);
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
                    'gender' => $member['gender'] ?? null,
                    'employment_status' => $member['employment_status'] ?? null,
                ]);
            }

            // Recalculate household size
            $household->recalculateSize();
        });

        return $clientId;
    }

    /**
     * Save step 3 (income sources).
     */
    public function saveStep3(array $data, int $clientId): void
    {
        $client = Client::find($clientId);

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

    /**
     * Save step 4 (program enrollments) with the eligibility snapshot.
     */
    public function saveStep4(array $data, int $clientId): void
    {
        $client = Client::find($clientId);

        DB::transaction(function () use ($data, $client): void {
            // Sync enrollments
            $client->enrollments()->forceDelete();

            $enrollments = $data['program_enrollments'] ?? [];
            $totalIncome = self::totalAnnualIncome($data['income_sources'] ?? []);
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

    /**
     * Annualize a set of income source rows from the wizard form state.
     */
    public static function totalAnnualIncome(array $sources): float
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
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Enums\IntakeStatus;
use App\Enums\OutcomeStatus;
use App\Models\AgencyCapacityMetric;
use App\Models\Client;
use App\Models\CnpiIndicator;
use App\Models\CnpiResult;
use App\Models\CommunityInitiative;
use App\Models\CsbgExpenditure;
use App\Models\CsbgReportSetting;
use App\Models\Enrollment;
use App\Models\FnpiTarget;
use App\Models\FundingSource;
use App\Models\Household;
use App\Models\NpiIndicator;
use App\Models\Outcome;
use App\Models\Program;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\Lookup;
use Illuminate\Database\Seeder;

/**
 * Seeds a realistic demo agency year so a fresh install shows a fully
 * populated CSBG Annual Report (every Module 4 Section C row non-zero
 * where the data model can produce it).
 *
 * NOT part of DatabaseSeeder — run explicitly:
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * Deterministic (fixed faker seed, attribute cycling) and guarded: refuses
 * to run in production or when clients already exist.
 */
class DemoDataSeeder extends Seeder
{
    protected const HOUSEHOLDS = 120;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoDataSeeder is disabled in production.');

            return;
        }

        if (Client::count() > 0) {
            $this->command?->error('DemoDataSeeder requires an empty client table.');

            return;
        }

        fake()->seed(20260703);

        $settings = CsbgReportSetting::current();
        $fiscalYear = $settings->current_fiscal_year;
        [$periodStart, $periodEnd] = match ($settings->reporting_period) {
            'jul_jun' => [($fiscalYear - 1).'-07-01', $fiscalYear.'-06-30'],
            'jan_dec' => [$fiscalYear.'-01-01', $fiscalYear.'-12-31'],
            default => [($fiscalYear - 1).'-10-01', $fiscalYear.'-09-30'],
        };
        $reportYear = (int) substr($periodEnd, 0, 4);

        $caseworkers = collect(range(1, 3))->map(fn (int $i) => User::firstOrCreate(
            ['email' => "demo-caseworker{$i}@example.org"],
            [
                'name' => "Demo Caseworker {$i}",
                'password' => bcrypt('demo-password-'.$i),
                'role' => 'caseworker',
                'is_active' => true,
            ],
        ));

        $programs = Program::active()->orderBy('id')->get();
        $servicesByProgram = $programs->mapWithKeys(
            fn (Program $p) => [$p->id => Service::where('program_id', $p->id)->orderBy('id')->get()]
        );

        // Cycled value pools (from the seeded CSBG lookups)
        $genders = array_keys(Lookup::options('gender'));
        $races = array_keys(Lookup::options('race'));
        $ethnicities = array_keys(Lookup::options('ethnicity'));
        $educations = array_keys(Lookup::options('education_level'));
        $employments = array_keys(Lookup::options('employment_status'));
        $insuranceSources = array_keys(Lookup::options('health_insurance_source'));
        $militaries = array_keys(Lookup::options('military_status'));
        $householdTypes = array_keys(Lookup::options('household_type'));
        $housingTypes = array_keys(Lookup::options('housing_type'));
        $otherIncomeSources = array_values(array_diff(
            array_keys(Lookup::options('income_source')),
            ['employment', 'self_employment', 'unknown'],
        ));
        $benefits = array_keys(Lookup::options('non_cash_benefit'));

        // Ages covering every Section C age band, relative to the report year
        $ages = [3, 10, 15, 20, 30, 50, 57, 62, 70, 80];
        // FPL percentages covering all nine bands + unknown
        $fplBands = [30, 60, 85, 110, 140, 160, 190, 225, 300, null];

        for ($i = 0; $i < self::HOUSEHOLDS; $i++) {
            $memberCount = $i % 6; // household sizes 1 through 6+

            $household = Household::create([
                'address_line_1' => fake()->buildingNumber().' '.fake()->streetName(),
                'city' => fake()->randomElement(['Allentown', 'Bethlehem', 'Easton', 'Emmaus', 'Whitehall']),
                'state' => 'PA',
                'zip' => fake()->numerify('181##'),
                'county' => fake()->randomElement(['Lehigh', 'Northampton']),
                'housing_type' => $housingTypes[$i % count($housingTypes)],
                'household_type' => $householdTypes[$i % count($householdTypes)],
                'household_size' => $memberCount + 1,
            ]);

            $age = $ages[$i % count($ages)];
            $insuranceStatus = ['yes', 'no', 'unknown'][$i % 3];

            $client = Client::create([
                'household_id' => $household->id,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'date_of_birth' => sprintf('%d-06-15', $reportYear - $age),
                'gender' => $genders[$i % count($genders)],
                'race' => $races[$i % count($races)],
                'ethnicity' => $ethnicities[$i % count($ethnicities)],
                'education_level' => $educations[$i % count($educations)],
                'employment_status' => $age >= 18 ? $employments[$i % count($employments)] : null,
                'health_insurance_status' => $insuranceStatus,
                'health_insurance_source' => $insuranceStatus === 'yes' ? $insuranceSources[$i % count($insuranceSources)] : null,
                'military_status' => $age >= 18 ? $militaries[$i % count($militaries)] : 'never_served',
                'is_veteran' => $age >= 18 && $militaries[$i % count($militaries)] === 'veteran',
                'is_disabled' => $i % 5 === 0,
                'is_disconnected_youth' => $age === 20 && $i % 2 === 0,
                'is_head_of_household' => true,
                'preferred_language' => $i % 7 === 0 ? 'es' : 'en',
                'relationship_to_head' => 'self',
                'intake_status' => IntakeStatus::Complete,
                'intake_step' => 5,
            ]);

            for ($m = 0; $m < $memberCount; $m++) {
                $memberAge = $ages[($i + $m + 1) % count($ages)];
                $household->members()->create([
                    'first_name' => fake()->firstName(),
                    'last_name' => $client->last_name,
                    'date_of_birth' => sprintf('%d-03-10', $reportYear - $memberAge),
                    'relationship_to_client' => $memberAge < 18 ? 'child' : 'spouse',
                    'gender' => $genders[($i + $m) % count($genders)],
                    'race' => $races[($i + $m) % count($races)],
                    'ethnicity' => $ethnicities[($i + $m) % count($ethnicities)],
                    'education_level' => $educations[($i + $m) % count($educations)],
                    'employment_status' => $memberAge >= 18 ? $employments[($i + $m) % count($employments)] : null,
                    'is_disabled' => ($i + $m) % 7 === 0,
                ]);
            }

            // Income + benefits engineered to hit every item 13 composite
            $composite = $i % 9;
            $hasEmployment = in_array($composite, [0, 1, 2, 3], true);
            $hasOther = in_array($composite, [1, 2, 4, 5, 8], true);
            $hasBenefits = in_array($composite, [2, 3, 5, 6], true);

            if ($hasEmployment) {
                $client->incomeRecords()->create([
                    'source' => $i % 2 === 0 ? 'employment' : 'self_employment',
                    'amount' => 900 + ($i % 10) * 150,
                    'frequency' => 'monthly',
                    'effective_date' => $periodStart,
                    'is_verified' => true,
                    'verification_method' => 'Pay stubs',
                ]);
            }

            if ($hasOther) {
                $client->incomeRecords()->create([
                    'source' => $otherIncomeSources[$i % count($otherIncomeSources)],
                    'amount' => 200 + ($i % 6) * 90,
                    'frequency' => 'monthly',
                    'effective_date' => $periodStart,
                    'is_verified' => false,
                ]);
            }

            if ($hasBenefits) {
                $client->nonCashBenefits()->create([
                    'benefit_type' => $benefits[$i % count($benefits)],
                    'effective_date' => $periodStart,
                    'is_active' => true,
                ]);
            }

            // Enrollment with an FPL snapshot spread across all nine bands
            $program = $programs[$i % $programs->count()];
            $enrolledAt = date('Y-m-d', strtotime($periodStart.' +'.($i % 300).' days'));

            $enrollment = Enrollment::create([
                'client_id' => $client->id,
                'program_id' => $program->id,
                'caseworker_id' => $caseworkers[$i % $caseworkers->count()]->id,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => $enrolledAt,
                'household_income_at_enrollment' => 12000 + ($i % 20) * 1500,
                'household_size_at_enrollment' => $memberCount + 1,
                'fpl_percent_at_enrollment' => $fplBands[$i % count($fplBands)],
                'income_eligible' => true,
            ]);

            // One to three services within the reporting period
            $services = $servicesByProgram[$program->id];
            for ($s = 0; $s <= $i % 3; $s++) {
                $service = $services[($i + $s) % $services->count()];
                $client->serviceRecords()->create([
                    'service_id' => $service->id,
                    'enrollment_id' => $enrollment->id,
                    'provided_by' => $caseworkers[$i % $caseworkers->count()]->id,
                    'service_date' => date('Y-m-d', strtotime($enrolledAt.' +'.($s * 14).' days')),
                    'quantity' => 1,
                    'value' => 50 + ($i % 8) * 25,
                ]);
            }

            // Achieved outcomes for a third of clients, spread across indicators
            if ($i % 3 === 0) {
                $indicator = NpiIndicator::forVersion('2.1')
                    ->whereNull('parent_indicator_id')
                    ->orderBy('id')
                    ->skip(($i / 3) % 20)
                    ->first();

                if ($indicator) {
                    Outcome::create([
                        'client_id' => $client->id,
                        'npi_indicator_id' => $indicator->id,
                        'enrollment_id' => $enrollment->id,
                        'status' => OutcomeStatus::Achieved,
                        'achieved_date' => date('Y-m-d', strtotime($enrolledAt.' +30 days')),
                    ]);
                }
            }
        }

        // FNPI targets for every top-level indicator
        NpiIndicator::forVersion('2.1')->whereNull('parent_indicator_id')->get()
            ->each(fn (NpiIndicator $indicator, int $idx) => FnpiTarget::updateOrCreate(
                ['npi_indicator_id' => $indicator->id, 'fiscal_year' => $fiscalYear],
                ['target_count' => 10 + ($idx % 5) * 5],
            ));

        // Module 2A expenditures per domain
        foreach (['employment', 'education', 'income', 'housing', 'health', 'civic_engagement', 'multi_domain'] as $idx => $domain) {
            CsbgExpenditure::updateOrCreate(
                ['fiscal_year' => $fiscalYear, 'domain' => $domain],
                [
                    'reporting_period' => $settings->reporting_period,
                    'csbg_funds' => 45000 + $idx * 12500,
                ],
            );
        }

        // Module 2B capacity metrics
        foreach (array_keys(AgencyCapacityMetric::TYPES) as $idx => $type) {
            AgencyCapacityMetric::updateOrCreate(
                ['fiscal_year' => $fiscalYear, 'metric_type' => $type, 'metric_key' => $type],
                ['metric_value' => 120 + $idx * 40],
            );
        }

        // Module 2C funding sources
        foreach ([
            ['CSBG', 'federal', 350000],
            ['LIHEAP', 'federal', 210000],
            ['State Human Services', 'state', 95000],
            ['United Way', 'private', 40000],
        ] as [$name, $type, $amount]) {
            FundingSource::updateOrCreate(
                ['fiscal_year' => $fiscalYear, 'source_name' => $name],
                ['source_type' => $type, 'amount' => $amount],
            );
        }

        // Module 3: two initiatives with CNPI results
        $initiative = CommunityInitiative::factory()->create([
            'fiscal_year' => $fiscalYear,
            'name' => 'Lehigh Valley Affordable Housing Coalition',
        ]);
        CommunityInitiative::factory()->create([
            'fiscal_year' => $fiscalYear,
            'name' => 'Countywide Financial Empowerment Initiative',
        ]);

        CnpiIndicator::forVersion('2.1')->orderBy('sort_order')->limit(4)->get()
            ->each(fn (CnpiIndicator $indicator, int $idx) => CnpiResult::updateOrCreate(
                ['cnpi_indicator_id' => $indicator->id, 'fiscal_year' => $fiscalYear],
                [
                    'community_initiative_id' => $initiative->id,
                    'identified_community' => 'Lehigh Valley',
                    'target' => 100 + $idx * 25,
                    'actual_result' => 80 + $idx * 30,
                ],
            ));

        $this->command?->info(sprintf(
            'Demo data seeded: %d households, %d clients, %d service records for FFY %d.',
            Household::count(),
            Client::count(),
            ServiceRecord::count(),
            $fiscalYear,
        ));
    }
}

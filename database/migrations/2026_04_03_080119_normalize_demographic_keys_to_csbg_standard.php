<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalize existing demographic field values to match the CSBG-standard
 * lookup keys. The old forms used shortened keys (e.g., 'black') while
 * the new lookup system uses CSBG-standard keys ('black_african_american').
 */
return new class extends Migration
{
    public function up(): void
    {
        // Race key mappings (old form values → new lookup keys)
        $raceMap = [
            'black' => 'black_african_american',
            'native_american' => 'american_indian_alaska_native',
            'pacific_islander' => 'native_hawaiian_pacific_islander',
            'multi_racial' => 'multi_race',
        ];

        foreach ($raceMap as $old => $new) {
            DB::table('clients')->where('race', $old)->update(['race' => $new]);
            DB::table('household_members')->where('race', $old)->update(['race' => $new]);
        }

        // Ethnicity key mappings
        $ethnicityMap = [
            'hispanic' => 'hispanic_latino',
            'not_hispanic' => 'not_hispanic_latino',
        ];

        foreach ($ethnicityMap as $old => $new) {
            DB::table('clients')->where('ethnicity', $old)->update(['ethnicity' => $new]);
            DB::table('household_members')->where('ethnicity', $old)->update(['ethnicity' => $new]);
        }

        // Housing type key mappings
        $housingMap = [
            'owned' => 'own',
            'rented' => 'rent',
            'shelter' => 'other',
            'transitional' => 'other_permanent',
        ];

        foreach ($housingMap as $old => $new) {
            DB::table('households')->where('housing_type', $old)->update(['housing_type' => $new]);
        }

        // Employment status key mappings (old enum values → new lookup keys)
        $employmentMap = [
            'employed' => 'employed_full',
            'employed_part_time' => 'employed_part',
            'disabled' => 'unemployed_not_in_labor',
            'student' => 'unemployed_not_in_labor',
            'homemaker' => 'unemployed_not_in_labor',
            'self_employed' => 'employed_full',
        ];

        foreach ($employmentMap as $old => $new) {
            DB::table('household_members')->where('employment_status', $old)->update(['employment_status' => $new]);
        }

        // Income source key mappings
        $incomeMap = [
            'snap' => 'other', // SNAP is non-cash benefit, not income
            'child_support' => 'child_support',
            'self_employment' => 'self_employment',
        ];

        foreach ($incomeMap as $old => $new) {
            DB::table('income_records')->where('source', $old)->update(['source' => $new]);
        }
    }

    public function down(): void
    {
        // Data migration — not reversible
    }
};

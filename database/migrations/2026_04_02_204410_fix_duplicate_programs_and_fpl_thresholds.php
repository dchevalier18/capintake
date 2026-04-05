<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix duplicate programs created by the setup wizard bug.
 *
 * The setup wizard previously didn't preserve FPL thresholds or funding sources
 * when creating new programs, and could create duplicates (CSBG2, EMRG2, WAP2)
 * while deactivating the correctly-configured originals.
 *
 * This migration:
 * 1. Reactivates original programs (CSBG, EMRG, WAP) with correct data
 * 2. Migrates any enrollments from duplicate programs to originals
 * 3. Removes the duplicate programs
 */
return new class extends Migration
{
    public function up(): void
    {
        // Map of original code => correct configuration
        $correctPrograms = [
            'CSBG' => [
                'name' => 'Community Services Block Grant',
                'fpl_threshold_percent' => 200,
                'funding_source' => 'CSBG',
                'requires_income_eligibility' => true,
                'is_active' => true,
            ],
            'EMRG' => [
                'name' => 'Emergency Services',
                'fpl_threshold_percent' => 150,
                'funding_source' => 'CSBG',
                'requires_income_eligibility' => true,
                'is_active' => true,
            ],
            'WAP' => [
                'name' => 'Weatherization Assistance',
                'fpl_threshold_percent' => 200,
                'funding_source' => 'federal',
                'requires_income_eligibility' => true,
                'is_active' => true,
            ],
        ];

        foreach ($correctPrograms as $code => $data) {
            $original = DB::table('programs')->where('code', $code)->first();

            if (! $original) {
                continue;
            }

            // Reactivate and fix the original program
            DB::table('programs')->where('id', $original->id)->update($data);

            // Find duplicates (codes like CSBG2, CSBG3, EMRG2, etc.)
            $duplicates = DB::table('programs')
                ->where('code', 'like', $code . '%')
                ->where('code', '!=', $code)
                ->get();

            foreach ($duplicates as $duplicate) {
                // Migrate enrollments from duplicate to original
                DB::table('enrollments')
                    ->where('program_id', $duplicate->id)
                    ->update(['program_id' => $original->id]);

                // Migrate services from duplicate to original (skip if code already exists)
                $existingServiceCodes = DB::table('services')
                    ->where('program_id', $original->id)
                    ->pluck('code')
                    ->toArray();

                DB::table('services')
                    ->where('program_id', $duplicate->id)
                    ->whereNotIn('code', $existingServiceCodes)
                    ->update(['program_id' => $original->id]);

                // Delete remaining duplicate services, then the duplicate program
                DB::table('services')->where('program_id', $duplicate->id)->delete();
                DB::table('programs')->where('id', $duplicate->id)->delete();
            }
        }
    }

    public function down(): void
    {
        // Data migration — not reversible
    }
};

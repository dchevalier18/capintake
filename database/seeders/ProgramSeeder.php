<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'name' => 'Community Services Block Grant',
                'code' => 'CSBG',
                'description' => 'Federally funded program to reduce poverty and revitalize low-income communities through locally designed and delivered programs.',
                'funding_source' => 'CSBG',
                'fpl_threshold_percent' => 200,
                'services' => [
                    ['name' => 'Case Management', 'code' => 'CSBG-CM', 'unit_of_measure' => 'hour'],
                    ['name' => 'Information and Referral', 'code' => 'CSBG-IR', 'unit_of_measure' => 'instance'],
                    ['name' => 'Financial Literacy Workshop', 'code' => 'CSBG-FLW', 'unit_of_measure' => 'instance'],
                    ['name' => 'Employment Readiness Training', 'code' => 'CSBG-ERT', 'unit_of_measure' => 'hour'],
                    ['name' => 'Tax Preparation (VITA)', 'code' => 'CSBG-VITA', 'unit_of_measure' => 'instance'],
                ],
            ],
            [
                'name' => 'Emergency Services',
                'code' => 'EMRG',
                'description' => 'Emergency assistance for immediate needs including food, shelter, utilities, and prescription medications.',
                'funding_source' => 'CSBG',
                'fpl_threshold_percent' => 150,
                'services' => [
                    ['name' => 'Emergency Food Box', 'code' => 'EMRG-FOOD', 'unit_of_measure' => 'item'],
                    ['name' => 'Emergency Rent Assistance', 'code' => 'EMRG-RENT', 'unit_of_measure' => 'dollar'],
                    ['name' => 'Emergency Utility Payment', 'code' => 'EMRG-UTIL', 'unit_of_measure' => 'dollar'],
                    ['name' => 'Emergency Prescription Assistance', 'code' => 'EMRG-RX', 'unit_of_measure' => 'dollar'],
                    ['name' => 'Emergency Clothing Voucher', 'code' => 'EMRG-CLO', 'unit_of_measure' => 'dollar'],
                ],
            ],
            [
                'name' => 'Weatherization Assistance',
                'code' => 'WAP',
                'description' => 'Department of Energy Weatherization Assistance Program to reduce energy costs for low-income households by increasing energy efficiency.',
                'funding_source' => 'federal',
                'fpl_threshold_percent' => 200,
                'services' => [
                    ['name' => 'Energy Audit', 'code' => 'WAP-AUDIT', 'unit_of_measure' => 'instance'],
                    ['name' => 'Insulation Installation', 'code' => 'WAP-INS', 'unit_of_measure' => 'instance'],
                    ['name' => 'Furnace Repair/Replacement', 'code' => 'WAP-FURN', 'unit_of_measure' => 'instance'],
                    ['name' => 'Air Sealing', 'code' => 'WAP-SEAL', 'unit_of_measure' => 'instance'],
                    ['name' => 'Window/Door Replacement', 'code' => 'WAP-WIN', 'unit_of_measure' => 'instance'],
                ],
            ],
        ];

        foreach ($programs as $programData) {
            $services = $programData['services'];
            unset($programData['services']);

            $programData['requires_income_eligibility'] = true;
            $programData['is_active'] = true;
            // Federal fiscal year: Oct 1 - Sep 30
            $programData['fiscal_year_start'] = now()->month >= 10
                ? now()->startOfYear()->addMonths(9)->startOfMonth()
                : now()->subYear()->startOfYear()->addMonths(9)->startOfMonth();
            $programData['fiscal_year_end'] = now()->month >= 10
                ? now()->addYear()->startOfYear()->addMonths(8)->endOfMonth()
                : now()->startOfYear()->addMonths(8)->endOfMonth();

            $program = Program::create($programData);

            foreach ($services as $serviceData) {
                $serviceData['is_active'] = true;
                $serviceData['description'] = "Service provided under the {$program->name} program.";
                $program->services()->create($serviceData);
            }
        }
    }
}

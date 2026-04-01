<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Reference data first (no dependencies)
            FederalPovertyLevelSeeder::class,
            NpiSeeder::class,

            // Programs and services
            ProgramSeeder::class,

            // NPI-to-Service mapping (depends on NPI + Programs)
            NpiServiceMappingSeeder::class,

            // Default admin user
            AdminUserSeeder::class,
        ]);
    }
}

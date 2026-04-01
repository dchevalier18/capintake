<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', UserRole::Admin)->exists()) {
            $this->command->info('Admin user already exists, skipping.');

            return;
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@capintake.test',
            'password' => Hash::make('Password1!'),
            'role' => UserRole::Admin,
            'is_active' => true,
            'title' => 'System Administrator',
        ]);

        $this->command->info('Default admin user created: admin@capintake.test / Password1!');
    }
}

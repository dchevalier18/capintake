<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientDocumentFactory extends Factory
{
    protected $model = ClientDocument::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'uploaded_by' => User::factory(),
            'category' => fake()->randomElement(['photo_id', 'income_verification', 'utility_bill', 'lease', 'other']),
            'disk' => 'local',
            'path' => 'client-documents/'.fake()->uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
        ];
    }
}

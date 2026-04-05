<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $ssn = fake()->numerify('#########');
        $dob = fake()->dateTimeBetween('-80 years', '-18 years');

        return [
            'household_id' => Household::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->optional(0.3)->firstName(),
            'date_of_birth' => $dob,
            'birth_year' => (int) $dob->format('Y'),
            'ssn_encrypted' => $ssn,
            'ssn_last_four' => substr($ssn, -4),
            'phone' => fake()->numerify('(###) ###-####'),
            'email' => fake()->optional(0.6)->safeEmail(),
            'gender' => fake()->randomElement(['male', 'female', 'non_binary', 'prefer_not_to_say']),
            'race' => fake()->randomElement([
                'white', 'black_african_american', 'asian',
                'american_indian_alaska_native', 'native_hawaiian_pacific_islander',
                'multi_race', 'other',
            ]),
            'ethnicity' => fake()->randomElement(['hispanic_latino', 'not_hispanic_latino']),
            'is_veteran' => fake()->boolean(10),
            'is_disabled' => fake()->boolean(15),
            'is_disconnected_youth' => false,
            'military_status' => fake()->randomElement(['never_served', 'veteran', null]),
            'employment_status' => fake()->randomElement(['employed_full', 'employed_part', 'unemployed_short', 'retired', null]),
            'education_level' => fake()->randomElement(['hs_graduate', 'ged', 'some_post_secondary', 'college_2_4_yr', null]),
            'health_insurance_status' => fake()->randomElement(['yes', 'no', null]),
            'health_insurance_source' => fake()->randomElement(['medicaid', 'employer', 'direct_purchase', null]),
            'is_head_of_household' => true,
            'preferred_language' => fake()->randomElement(['en', 'es', 'zh', 'vi', 'ar']),
            'relationship_to_head' => 'self',
        ];
    }

    public function minor(): static
    {
        return $this->state(function () {
            $dob = fake()->dateTimeBetween('-17 years', '-1 year');

            return [
                'date_of_birth' => $dob,
                'birth_year' => (int) $dob->format('Y'),
                'is_head_of_household' => false,
                'relationship_to_head' => 'child',
            ];
        });
    }

    public function veteran(): static
    {
        return $this->state(function () {
            $dob = fake()->dateTimeBetween('-80 years', '-21 years');

            return [
                'is_veteran' => true,
                'date_of_birth' => $dob,
                'birth_year' => (int) $dob->format('Y'),
            ];
        });
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\Household;
use App\Models\HouseholdMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class HouseholdMemberFactory extends Factory
{
    protected $model = HouseholdMember::class;

    public function definition(): array
    {
        $dob = fake()->dateTimeBetween('-80 years', '-1 year');

        return [
            'household_id' => Household::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => $dob,
            'birth_year' => (int) $dob->format('Y'),
            'gender' => fake()->randomElement(['male', 'female', 'non_binary']),
            'race' => fake()->randomElement([
                'white', 'black_african_american', 'asian',
                'american_indian_alaska_native', 'multi_racial', 'other',
            ]),
            'ethnicity' => fake()->randomElement(['hispanic_latino', 'not_hispanic_latino']),
            'relationship_to_client' => fake()->randomElement(['spouse', 'child', 'parent', 'sibling', 'grandchild', 'other']),
            'employment_status' => fake()->randomElement(EmploymentStatus::cases()),
            'is_veteran' => fake()->boolean(5),
            'is_disabled' => fake()->boolean(10),
            'is_student' => fake()->boolean(20),
            'education_level' => fake()->randomElement(['less_than_hs', 'hs_ged', 'some_college', 'associates', 'bachelors', 'graduate']),
            'health_insurance' => fake()->randomElement(['medicaid', 'medicare', 'employer', 'marketplace', 'none']),
        ];
    }

    public function child(): static
    {
        return $this->state(function () {
            $dob = fake()->dateTimeBetween('-17 years', '-1 year');

            return [
                'date_of_birth' => $dob,
                'birth_year' => (int) $dob->format('Y'),
                'relationship_to_client' => 'child',
                'employment_status' => null,
                'is_student' => true,
            ];
        });
    }

    public function spouse(): static
    {
        return $this->state(function () {
            $dob = fake()->dateTimeBetween('-70 years', '-18 years');

            return [
                'date_of_birth' => $dob,
                'birth_year' => (int) $dob->format('Y'),
                'relationship_to_client' => 'spouse',
            ];
        });
    }
}

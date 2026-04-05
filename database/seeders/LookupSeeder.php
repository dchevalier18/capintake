<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LookupCategory;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->getCategories();

        foreach ($categories as $sortOrder => $categoryData) {
            $values = $categoryData['values'];
            unset($categoryData['values']);

            $categoryData['sort_order'] = $sortOrder;
            $categoryData['is_system'] = true;

            $category = LookupCategory::updateOrCreate(
                ['key' => $categoryData['key']],
                $categoryData
            );

            foreach ($values as $valueSortOrder => $valueData) {
                $category->values()->updateOrCreate(
                    ['key' => $valueData['key']],
                    array_merge($valueData, [
                        'is_system' => true,
                        'is_active' => true,
                        'sort_order' => $valueSortOrder,
                    ])
                );
            }
        }
    }

    private function getCategories(): array
    {
        return [
            [
                'key' => 'gender',
                'name' => 'Gender',
                'description' => 'Gender identity options for client and household member demographics.',
                'allow_custom' => true,
                'values' => [
                    ['key' => 'male', 'label' => 'Male'],
                    ['key' => 'female', 'label' => 'Female'],
                    ['key' => 'non_binary', 'label' => 'Non-Binary'],
                    ['key' => 'other', 'label' => 'Other'],
                    ['key' => 'prefer_not_to_say', 'label' => 'Prefer Not to Say', 'csbg_report_code' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'race',
                'name' => 'Race',
                'description' => 'Race categories per HUD/CSBG reporting requirements.',
                'allow_custom' => false,
                'values' => [
                    ['key' => 'american_indian_alaska_native', 'label' => 'American Indian or Alaska Native'],
                    ['key' => 'asian', 'label' => 'Asian'],
                    ['key' => 'black_african_american', 'label' => 'Black or African American'],
                    ['key' => 'native_hawaiian_pacific_islander', 'label' => 'Native Hawaiian and Other Pacific Islander'],
                    ['key' => 'white', 'label' => 'White'],
                    ['key' => 'other', 'label' => 'Other'],
                    ['key' => 'multi_race', 'label' => 'Multi-race (two or more of the above)'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'ethnicity',
                'name' => 'Ethnicity',
                'description' => 'Hispanic/Latino ethnicity categories per CSBG reporting.',
                'allow_custom' => false,
                'values' => [
                    ['key' => 'hispanic_latino', 'label' => 'Hispanic, Latino or Spanish Origins'],
                    ['key' => 'not_hispanic_latino', 'label' => 'Not Hispanic, Latino or Spanish Origins'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'education_level',
                'name' => 'Education Level',
                'description' => 'Highest education level completed. CSBG report splits by age: 14-24 and 25+.',
                'allow_custom' => false,
                'values' => [
                    ['key' => 'grades_0_8', 'label' => 'Grades 0-8'],
                    ['key' => 'grades_9_12_non_grad', 'label' => 'Grades 9-12/Non-Graduate'],
                    ['key' => 'hs_graduate', 'label' => 'High School Graduate'],
                    ['key' => 'ged', 'label' => 'GED/Equivalency Diploma'],
                    ['key' => 'some_post_secondary', 'label' => '12 grade + Some Post-Secondary'],
                    ['key' => 'college_2_4_yr', 'label' => '2 or 4 years College Graduate'],
                    ['key' => 'post_secondary_other', 'label' => 'Graduate of other post-secondary school'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'employment_status',
                'name' => 'Employment Status',
                'description' => 'Employment status for individuals 18 and older.',
                'allow_custom' => false,
                'values' => [
                    ['key' => 'employed_full', 'label' => 'Employed Full-Time'],
                    ['key' => 'employed_part', 'label' => 'Employed Part-Time'],
                    ['key' => 'migrant_seasonal', 'label' => 'Migrant or Seasonal Farm Worker'],
                    ['key' => 'unemployed_short', 'label' => 'Unemployed (Short-Term, 6 months or less)'],
                    ['key' => 'unemployed_long', 'label' => 'Unemployed (Long-Term, more than 6 months)'],
                    ['key' => 'unemployed_not_in_labor', 'label' => 'Unemployed (Not in Labor Force)'],
                    ['key' => 'retired', 'label' => 'Retired'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'military_status',
                'name' => 'Military Status',
                'description' => 'Military service status.',
                'allow_custom' => false,
                'values' => [
                    ['key' => 'veteran', 'label' => 'Veteran'],
                    ['key' => 'active', 'label' => 'Active Military'],
                    ['key' => 'never_served', 'label' => 'Never Served in the Military'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'health_insurance_status',
                'name' => 'Health Insurance Status',
                'description' => 'Whether the individual has health insurance coverage.',
                'allow_custom' => false,
                'values' => [
                    ['key' => 'yes', 'label' => 'Yes'],
                    ['key' => 'no', 'label' => 'No'],
                    ['key' => 'unknown', 'label' => 'Unknown'],
                ],
            ],
            [
                'key' => 'health_insurance_source',
                'name' => 'Health Insurance Source',
                'description' => 'Source of health insurance coverage.',
                'allow_custom' => true,
                'values' => [
                    ['key' => 'medicaid', 'label' => 'Medicaid'],
                    ['key' => 'medicare', 'label' => 'Medicare'],
                    ['key' => 'schip', 'label' => 'State Children\'s Health Insurance Program'],
                    ['key' => 'state_adult', 'label' => 'State Health Insurance for Adults'],
                    ['key' => 'military', 'label' => 'Military Health Care'],
                    ['key' => 'direct_purchase', 'label' => 'Direct-Purchase'],
                    ['key' => 'employer', 'label' => 'Employment Based'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'housing_type',
                'name' => 'Housing Type',
                'description' => 'Housing tenure/type at time of intake.',
                'allow_custom' => true,
                'values' => [
                    ['key' => 'own', 'label' => 'Own'],
                    ['key' => 'rent', 'label' => 'Rent'],
                    ['key' => 'other_permanent', 'label' => 'Other permanent housing'],
                    ['key' => 'homeless', 'label' => 'Homeless'],
                    ['key' => 'other', 'label' => 'Other'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'household_type',
                'name' => 'Household Type',
                'description' => 'Composition of the household.',
                'allow_custom' => true,
                'values' => [
                    ['key' => 'single_person', 'label' => 'Single Person'],
                    ['key' => 'two_adults_no_children', 'label' => 'Two Adults NO Children'],
                    ['key' => 'single_parent_female', 'label' => 'Single Parent Female'],
                    ['key' => 'single_parent_male', 'label' => 'Single Parent Male'],
                    ['key' => 'two_parent', 'label' => 'Two Parent Household'],
                    ['key' => 'non_related_adults_children', 'label' => 'Non-related Adults with Children'],
                    ['key' => 'multigenerational', 'label' => 'Multigenerational Household'],
                    ['key' => 'other', 'label' => 'Other'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'income_source',
                'name' => 'Income Source',
                'description' => 'Sources of income for CSBG reporting.',
                'allow_custom' => true,
                'values' => [
                    ['key' => 'employment', 'label' => 'Income from Employment'],
                    ['key' => 'tanf', 'label' => 'TANF'],
                    ['key' => 'ssi', 'label' => 'Supplemental Security Income (SSI)'],
                    ['key' => 'ssdi', 'label' => 'Social Security Disability Income (SSDI)'],
                    ['key' => 'va_disability', 'label' => 'VA Service-Connected Disability Compensation'],
                    ['key' => 'va_pension', 'label' => 'VA Non-Service Connected Disability Pension'],
                    ['key' => 'private_disability', 'label' => 'Private Disability Insurance'],
                    ['key' => 'workers_comp', 'label' => 'Worker\'s Compensation'],
                    ['key' => 'social_security_retirement', 'label' => 'Retirement Income from Social Security'],
                    ['key' => 'pension', 'label' => 'Pension'],
                    ['key' => 'child_support', 'label' => 'Child Support'],
                    ['key' => 'alimony', 'label' => 'Alimony or other Spousal Support'],
                    ['key' => 'unemployment', 'label' => 'Unemployment Insurance'],
                    ['key' => 'eitc', 'label' => 'EITC'],
                    ['key' => 'self_employment', 'label' => 'Self-Employment'],
                    ['key' => 'other', 'label' => 'Other'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'non_cash_benefit',
                'name' => 'Non-Cash Benefit',
                'description' => 'Non-cash assistance programs for CSBG reporting.',
                'allow_custom' => true,
                'values' => [
                    ['key' => 'snap', 'label' => 'SNAP'],
                    ['key' => 'wic', 'label' => 'WIC'],
                    ['key' => 'liheap', 'label' => 'LIHEAP'],
                    ['key' => 'housing_choice_voucher', 'label' => 'Housing Choice Voucher'],
                    ['key' => 'public_housing', 'label' => 'Public Housing'],
                    ['key' => 'permanent_supportive_housing', 'label' => 'Permanent Supportive Housing'],
                    ['key' => 'hud_vash', 'label' => 'HUD-VASH'],
                    ['key' => 'childcare_voucher', 'label' => 'Childcare Voucher'],
                    ['key' => 'aca_subsidy', 'label' => 'Affordable Care Act Subsidy'],
                    ['key' => 'other', 'label' => 'Other'],
                    ['key' => 'unknown', 'label' => 'Unknown/not reported'],
                ],
            ],
            [
                'key' => 'relationship_to_head',
                'name' => 'Relationship to Head of Household',
                'description' => 'Relationship of household member to the head of household.',
                'allow_custom' => true,
                'values' => [
                    ['key' => 'self', 'label' => 'Self (Head of Household)'],
                    ['key' => 'spouse', 'label' => 'Spouse/Partner'],
                    ['key' => 'child', 'label' => 'Child'],
                    ['key' => 'parent', 'label' => 'Parent'],
                    ['key' => 'sibling', 'label' => 'Sibling'],
                    ['key' => 'grandchild', 'label' => 'Grandchild'],
                    ['key' => 'grandparent', 'label' => 'Grandparent'],
                    ['key' => 'other_relative', 'label' => 'Other Relative'],
                    ['key' => 'non_relative', 'label' => 'Non-Relative'],
                ],
            ],
            [
                'key' => 'community_domain',
                'name' => 'Community Domain',
                'description' => 'CSBG Module 3 community initiative domains.',
                'allow_custom' => false,
                'values' => [
                    ['key' => 'employment', 'label' => 'Employment'],
                    ['key' => 'education', 'label' => 'Education and Cognitive Development'],
                    ['key' => 'income_asset', 'label' => 'Income, Infrastructure, and Asset Building'],
                    ['key' => 'housing', 'label' => 'Housing'],
                    ['key' => 'health_social', 'label' => 'Health and Social/Behavioral Development'],
                    ['key' => 'civic_engagement', 'label' => 'Civic Engagement and Community Involvement'],
                ],
            ],
        ];
    }
}

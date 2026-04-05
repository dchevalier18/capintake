<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CsbgStrCategory;
use Illuminate\Database\Seeder;

class CsbgStrCategorySeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'STR 1' => ['Employment Strategies', [
                ['STR 1a', 'Minimum/Living Wage Campaign'],
                ['STR 1b', 'Job Creation/Employment Generation'],
                ['STR 1c', 'Job Fairs'],
                ['STR 1d', 'Earned Income Tax Credit (EITC) Promotion'],
                ['STR 1e', 'Commercial Space Development'],
                ['STR 1f', 'Employer Education'],
                ['STR 1g', 'Employment Policy Changes'],
                ['STR 1h', 'Employment Legislative Changes'],
                ['STR 1i', 'Other Employment Strategy'],
            ]],
            'STR 2' => ['Education and Cognitive Development Strategies', [
                ['STR 2a', 'Preschool for All Campaign'],
                ['STR 2b', 'Charter School Development'],
                ['STR 2c', 'After School Enrichment Activities Promotion'],
                ['STR 2d', 'Pre K-College/Community College Support'],
                ['STR 2e', 'Children\'s Trust Fund Creation'],
                ['STR 2f', 'Scholarship Creation'],
                ['STR 2g', 'Child Tax Credit (CTC) Promotion'],
                ['STR 2h', 'Adoption Child Care Quality Rating'],
                ['STR 2i', 'Adult Education Establishment'],
                ['STR 2j', 'Education Policy Changes'],
                ['STR 2k', 'Education Legislative Changes'],
                ['STR 2l', 'Other Education Strategy'],
            ]],
            'STR 3' => ['Infrastructure and Asset Building Strategies', [
                ['STR 3a', 'Cultural Asset Creation'],
                ['STR 3b', 'Police/Community Relations Campaign'],
                ['STR 3c', 'Neighborhood Safety Watch Programs'],
                ['STR 3d', 'Anti-Predatory Lending Campaign'],
                ['STR 3e', 'Asset Building and Savings Promotion'],
                ['STR 3f', 'Develop/Build/Rehab Spaces'],
                ['STR 3g', 'Maintain or Host Income Tax Preparation Sites'],
                ['STR 3h', 'Community-Wide Data Collection Systems Development'],
                ['STR 3i', 'Local 211 or Resource/Referral System Development'],
                ['STR 3j', 'Water/Sewer System Development'],
                ['STR 3k', 'Community Financial Institution Creation'],
                ['STR 3l', 'Infrastructure Planning Coalition'],
                ['STR 3m', 'Park or Recreation Creation and Maintenance'],
                ['STR 3n', 'Rehabilitation/Weatherization of Housing Stock'],
                ['STR 3o', 'Community Center/Community Facility Establishment'],
                ['STR 3p', 'Asset Limit Barriers for Benefits Policy Changes'],
                ['STR 3q', 'Infrastructure and Asset Building Policy Changes'],
                ['STR 3r', 'Infrastructure and Asset Building Legislative Changes'],
                ['STR 3s', 'Other Infrastructure and Asset Building Strategy'],
            ]],
            'STR 4' => ['Housing Strategies', [
                ['STR 4a', 'End Chronic Homelessness Campaign'],
                ['STR 4b', 'New Affordable Single Unit Housing Creation'],
                ['STR 4c', 'New Affordable Multi-Unit Housing Creation'],
                ['STR 4d', 'Tenants\' Rights Campaign'],
                ['STR 4e', 'New Shelters Creation'],
                ['STR 4f', 'Housing or Land Trust Creation'],
                ['STR 4g', 'Building Codes Campaign'],
                ['STR 4h', 'Housing Policy Changes'],
                ['STR 4i', 'Housing Legislative Changes'],
                ['STR 4j', 'Other Housing Strategy'],
            ]],
            'STR 5' => ['Health and Social/Behavioral Development Strategies', [
                ['STR 5a', 'Health Specific Campaign'],
                ['STR 5b', 'Farmers Market or Community Garden Development'],
                ['STR 5c', 'Grocery Store Development'],
                ['STR 5d', 'Gun Safety/Control Campaign'],
                ['STR 5e', 'Healthy Food Campaign'],
                ['STR 5f', 'Nutrition Education Collaborative'],
                ['STR 5g', 'Food Bank Development'],
                ['STR 5h', 'Domestic Violence Court Development'],
                ['STR 5i', 'Drug Court Development'],
                ['STR 5j', 'Alternative Energy Source Development'],
                ['STR 5k', 'Develop or Maintain a Health Clinic'],
                ['STR 5l', 'Health Policy Changes'],
                ['STR 5m', 'Health Legislative Changes'],
                ['STR 5n', 'Other Health Strategy'],
            ]],
            'STR 6 G2' => ['Civic Engagement - Goal 2 Strategies', [
                ['STR 6 G2a', 'Development of Health and Social Service Provider Partnerships'],
                ['STR 6 G2b', 'Recruiting and Coordinating Community Volunteers'],
                ['STR 6 G2c', 'Poverty Simulations'],
                ['STR 6 G2d', 'Attract Capital Investments'],
                ['STR 6 G2e', 'Coordinated Community-wide Needs Assessment'],
                ['STR 6 G2f', 'Civic Engagement and Community Involvement in Advocacy Efforts'],
                ['STR 6 G2g', 'Civic Engagement Policy Changes'],
                ['STR 6 G2h', 'Civic Engagement Legislative Changes'],
                ['STR 6 G2i', 'Other Civic Engagement Strategy - Goal 2'],
            ]],
            'STR 6 G3' => ['Civic Engagement - Goal 3 Strategies', [
                ['STR 6 G3a', 'Empowerment of Individuals/Families with Low-Incomes'],
                ['STR 6 G3b', 'Campaign to Ensure Low-Income Representation on Governing Bodies'],
                ['STR 6 G3c', 'Social Capital Building Campaign'],
                ['STR 6 G3d', 'Campaign for Volunteer Placement and Coordination'],
                ['STR 6 G3e', 'Civic Engagement Policy Changes'],
                ['STR 6 G3f', 'Civic Engagement Legislative Changes'],
                ['STR 6 G3g', 'Other Civic Engagement Strategy - Goal 3'],
            ]],
            'STR 7' => ['Community Support Strategies', [
                ['STR 7a', 'Off-Hours (Non-Traditional Hours) Child Care Development'],
                ['STR 7b', 'Transportation System Development'],
                ['STR 7c', 'Transportation Services Coordination and Support'],
                ['STR 7d', 'Community Support Policy Changes'],
                ['STR 7e', 'Community Support Legislative Changes'],
                ['STR 7f', 'Other Community Support Strategy'],
            ]],
            'STR 8' => ['Emergency Management Strategies', [
                ['STR 8a', 'State or Local Emergency Management Board Enhancement'],
                ['STR 8b', 'Community wide Emergency Disaster Relief Service Creation'],
                ['STR 8c', 'Disaster Preparation Planning'],
                ['STR 8d', 'Emergency Management Policy Changes'],
                ['STR 8e', 'Emergency Management Legislative Changes'],
                ['STR 8f', 'Other Emergency Management Strategy'],
            ]],
        ];

        $sortOrder = 0;
        foreach ($groups as $groupCode => [$groupName, $codes]) {
            foreach ($codes as [$code, $name]) {
                $sortOrder++;
                CsbgStrCategory::updateOrCreate(
                    ['code' => $code],
                    [
                        'group_code' => $groupCode,
                        'group_name' => $groupName,
                        'name' => $name,
                        'sort_order' => $sortOrder,
                    ],
                );
            }
        }
    }
}

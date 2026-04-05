<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CsbgSrvCategory;
use Illuminate\Database\Seeder;

class CsbgSrvCategorySeeder extends Seeder
{
    public function run(): void
    {
        $sortOrder = 0;

        foreach ($this->getCategories() as $category) {
            CsbgSrvCategory::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['sort_order' => $sortOrder++])
            );
        }
    }

    private function getCategories(): array
    {
        return [
            // SRV 1 — Employment Services
            ...self::group('employment', 'Skills Training and Opportunities', [
                ['1a', 'Vocational Training'], ['1b', 'On-the-Job/Work Experience'], ['1c', 'Youth Summer Work Placements'],
                ['1d', 'Apprenticeship/Internship'], ['1e', 'Self-Employment Skills Training'], ['1f', 'Job Readiness Training'],
            ]),
            ...self::group('employment', 'Career Counseling', [
                ['1g', 'Workshops'], ['1h', 'Coaching'],
            ]),
            ...self::group('employment', 'Job Search', [
                ['1i', 'Coaching'], ['1j', 'Resume Development'], ['1k', 'Interview Skills Training'],
                ['1l', 'Job Referrals'], ['1m', 'Job Placements'], ['1n', 'Pre-employment physicals/background checks'],
            ]),
            ...self::group('employment', 'Post Employment Supports', [
                ['1o', 'Coaching'], ['1p', 'Interactions with employers'],
            ]),
            ...self::group('employment', 'Employment Supplies', [
                ['1q', 'Employment Supplies'],
            ]),

            // SRV 2 — Education and Cognitive Development Services
            ...self::group('education', 'Child/Young Adult Education Programs', [
                ['2a', 'Early Head Start'], ['2b', 'Head Start'], ['2c', 'Other Early-Childhood (0-5) Education'],
                ['2d', 'K-12 Education'], ['2e', 'K-12 Support Services'], ['2f', 'Financial Literacy Education'],
                ['2g', 'Literacy/English Language Education'], ['2h', 'College-Readiness Preparation/Support'],
                ['2i', 'Other Post Secondary Preparation'], ['2j', 'Other Post Secondary Support'],
            ]),
            ...self::group('education', 'School Supplies', [
                ['2k', 'School Supplies'],
            ]),
            ...self::group('education', 'Extra-curricular Programs', [
                ['2l', 'Before and After School Activities'], ['2m', 'Summer Youth Recreational Activities'],
                ['2n', 'Summer Education Programs'], ['2o', 'Behavior Improvement Programs'],
                ['2p', 'Mentoring'], ['2q', 'Leadership Training'],
            ]),
            ...self::group('education', 'Adult Education Programs', [
                ['2r', 'Adult Literacy Classes'], ['2s', 'English Language Classes'], ['2t', 'Basic Education Classes'],
                ['2u', 'High School Equivalency Classes'], ['2v', 'Leadership Training'], ['2w', 'Parenting Supports'],
                ['2x', 'Applied Technology Classes'], ['2y', 'Post-Secondary Education Preparation'],
                ['2z', 'Financial Literacy Education'],
            ]),
            ...self::group('education', 'Post-Secondary Education Supports', [
                ['2aa', 'College applications/textbooks/computers'],
            ]),
            ...self::group('education', 'Financial Aid Assistance', [
                ['2bb', 'Scholarships'],
            ]),
            ...self::group('education', 'Home Visits', [
                ['2cc', 'Home Visits'],
            ]),

            // SRV 3 — Income and Asset Building Services
            ...self::group('income_asset', 'Training and Counseling', [
                ['3a', 'Financial Capability Skills Training'], ['3b', 'Financial Coaching/Counseling'],
                ['3c', 'Financial Management Programs'], ['3d', 'First-time Homebuyer Counseling'],
                ['3e', 'Foreclosure Prevention Counseling'], ['3f', 'Small Business Start-Up and Development Counseling'],
            ]),
            ...self::group('income_asset', 'Benefit Coordination and Advocacy', [
                ['3g', 'Child Support Payments'], ['3h', 'Health Insurance'], ['3i', 'Social Security/SSI Payments'],
                ['3j', 'Veteran\'s Benefits'], ['3k', 'TANF Benefits'], ['3l', 'SNAP Benefits'],
            ]),
            ...self::group('income_asset', 'Asset Building', [
                ['3m', 'Saving Accounts/IDAs'], ['3n', 'Other financial products (IRA/MyRA/retirement)'],
                ['3o', 'VITA/EITC/Tax Preparation programs'],
            ]),
            ...self::group('income_asset', 'Loans and Grants', [
                ['3p', 'Micro-loans'], ['3q', 'Business incubator/development loans'],
            ]),

            // SRV 4 — Housing Services
            ...self::group('housing', 'Housing Payment Assistance', [
                ['4a', 'Financial Capability Skill Training'], ['4b', 'Financial Coaching/Counseling'],
                ['4c', 'Rent Payments (incl emergency)'], ['4d', 'Deposit Payments'], ['4e', 'Mortgage Payments (incl emergency)'],
            ]),
            ...self::group('housing', 'Eviction Prevention', [
                ['4f', 'Eviction Counseling'], ['4g', 'Landlord/Tenant Mediations'], ['4h', 'Landlord/Tenant Rights Education'],
            ]),
            ...self::group('housing', 'Utility Payment Assistance', [
                ['4i', 'Utility Payments (LIHEAP incl emergency)'], ['4j', 'Utility Deposits'],
                ['4k', 'Utility Arrears Payments'], ['4l', 'Level Billing Assistance'],
            ]),
            ...self::group('housing', 'Housing Placement/Rapid Re-housing', [
                ['4m', 'Temporary Housing Placement (incl Emergency Shelters)'], ['4n', 'Transitional Housing Placements'],
                ['4o', 'Permanent Housing Placements'], ['4p', 'Rental Counseling'],
            ]),
            ...self::group('housing', 'Housing Maintenance & Improvements', [
                ['4q', 'Home Repairs (incl emergency)'],
            ]),
            ...self::group('housing', 'Weatherization Services', [
                ['4r', 'Independent-living Home Improvements'], ['4s', 'Healthy Homes Services'],
                ['4t', 'Energy Efficiency Improvements'],
            ]),

            // SRV 5 — Health and Social/Behavioral Development Services
            ...self::group('health_social', 'Health Services, Screening and Assessments', [
                ['5a', 'Immunizations'], ['5b', 'Physicals'], ['5c', 'Developmental Delay Screening'],
                ['5d', 'Vision Screening'], ['5e', 'Prescription Payments'], ['5f', 'Doctor Visit Payments'],
                ['5g', 'Maternal/Child Health'], ['5h', 'Nursing Care Sessions'],
                ['5i', 'In-Home Seniors/Disabled Care'], ['5j', 'Health Insurance Options Counseling'],
            ]),
            ...self::group('health_social', 'Reproductive Health Services', [
                ['5k', 'Coaching Sessions'], ['5l', 'Family Planning Classes'], ['5m', 'Contraceptives'],
                ['5n', 'STI/HIV Prevention Counseling'], ['5o', 'STI/HIV Screenings'],
            ]),
            ...self::group('health_social', 'Wellness Education', [
                ['5p', 'Wellness Classes'], ['5q', 'Exercise/Fitness'],
            ]),
            ...self::group('health_social', 'Mental/Behavioral Health', [
                ['5r', 'Detoxification Sessions'], ['5s', 'Substance Abuse Screenings'],
                ['5t', 'Substance Abuse Counseling'], ['5u', 'Mental Health Assessments'],
                ['5v', 'Mental Health Counseling'], ['5w', 'Crisis Response/Call-In Responses'],
                ['5x', 'Domestic Violence Programs'],
            ]),
            ...self::group('health_social', 'Support Groups', [
                ['5y', 'Substance Abuse Support Group Meetings'], ['5z', 'Domestic Violence Support Group Meetings'],
                ['5aa', 'Mental Health Support Group Meetings'],
            ]),
            ...self::group('health_social', 'Dental Services', [
                ['5bb', 'Adult Dental Screening/Exams'], ['5cc', 'Adult Dental Services'],
                ['5dd', 'Child Dental Screenings/Exams'], ['5ee', 'Child Dental Services'],
            ]),
            ...self::group('health_social', 'Nutrition and Food/Meals', [
                ['5ff', 'Skills Classes (Gardening/Cooking/Nutrition)'], ['5gg', 'Community Gardening Activities'],
                ['5hh', 'Incentives'], ['5ii', 'Prepared Meals'], ['5jj', 'Food Distribution'],
            ]),
            ...self::group('health_social', 'Family Skills Development', [
                ['5kk', 'Family Mentoring Sessions'], ['5ll', 'Life Skills Coaching Sessions'], ['5mm', 'Parenting Classes'],
            ]),
            ...self::group('health_social', 'Emergency Hygiene Assistance', [
                ['5nn', 'Kits/boxes'], ['5oo', 'Hygiene Facility Utilizations'],
            ]),

            // SRV 6 — Civic Engagement and Community Involvement
            ...self::group('civic_engagement', 'Civic Engagement', [
                ['6a', 'Voter Education and Access'], ['6b', 'Leadership Training'],
                ['6c', 'Tri-partite Board Membership'], ['6d', 'Citizenship Classes'],
                ['6e', 'Getting Ahead Classes'], ['6f', 'Volunteer Training'],
            ]),

            // SRV 7 — Services Supporting Multiple Domains
            ...self::group('multi_domain', 'Case Management', [['7a', 'Case Management']]),
            ...self::group('multi_domain', 'Eligibility Determinations', [['7b', 'Eligibility Determinations']]),
            ...self::group('multi_domain', 'Referrals', [['7c', 'Referrals']]),
            ...self::group('multi_domain', 'Transportation', [['7d', 'Transportation Services']]),
            ...self::group('multi_domain', 'Childcare', [['7e', 'Child Care subsidies'], ['7f', 'Child Care payments']]),
            ...self::group('multi_domain', 'Eldercare', [['7g', 'Day Centers']]),
            ...self::group('multi_domain', 'Identification Documents', [
                ['7h', 'Birth Certificate'], ['7i', 'Social Security Card'], ['7j', 'Driver\'s License'],
            ]),
            ...self::group('multi_domain', 'Re-Entry Services', [['7k', 'Criminal Record Expungements']]),
            ...self::group('multi_domain', 'Legal Assistance', [['7m', 'Legal Assistance']]),
            ...self::group('multi_domain', 'Emergency Clothing', [['7n', 'Emergency Clothing Assistance']]),
            ...self::group('multi_domain', 'Mediation/Advocacy', [['7o', 'Mediation/Customer Advocacy Intervention']]),
        ];
    }

    /**
     * Build category entries for a domain/group combination.
     */
    private static function group(string $domain, string $groupName, array $items): array
    {
        return array_map(fn (array $item) => [
            'code' => 'SRV ' . $item[0],
            'domain' => $domain,
            'group_name' => $groupName,
            'name' => $item[1],
        ], $items);
    }
}

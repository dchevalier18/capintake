<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NpiGoal;
use App\Models\NpiIndicator;
use Illuminate\Database\Seeder;

class NpiSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data for idempotent re-runs
        NpiIndicator::query()->delete();
        NpiGoal::query()->delete();

        foreach ($this->getGoals() as $goalData) {
            $indicators = $goalData['indicators'];
            unset($goalData['indicators']);

            $goal = NpiGoal::create($goalData);

            foreach ($indicators as $indicatorData) {
                $children = $indicatorData['children'] ?? [];
                unset($indicatorData['children']);

                $indicator = $goal->indicators()->create($indicatorData);

                foreach ($children as $childData) {
                    $childData['npi_goal_id'] = $goal->id;
                    $childData['parent_indicator_id'] = $indicator->id;
                    NpiIndicator::create($childData);
                }
            }
        }
    }

    private function getGoals(): array
    {
        return [
            // ----------------------------------------------------------------
            // Goal 1 — Employment
            // ----------------------------------------------------------------
            [
                'goal_number' => 1,
                'name' => 'Employment',
                'description' => 'Individuals and families with low incomes are stable and achieve economic security.',
                'indicators' => [
                    ['indicator_code' => 'FNPI-1a', 'name' => 'Youth obtained employment', 'description' => 'The number of unemployed youth who obtained employment to gain skills or income.'],
                    ['indicator_code' => 'FNPI-1b', 'name' => 'Adults obtained employment (up to living wage)', 'description' => 'The number of unemployed adults who obtained employment (up to a living wage).'],
                    ['indicator_code' => 'FNPI-1c', 'name' => 'Adults maintained employment 90 days (up to living wage)', 'description' => 'The number of unemployed adults who obtained and maintained employment for at least 90 days (up to a living wage).'],
                    ['indicator_code' => 'FNPI-1d', 'name' => 'Adults maintained employment 180 days (up to living wage)', 'description' => 'The number of unemployed adults who obtained and maintained employment for at least 180 days (up to a living wage).'],
                    ['indicator_code' => 'FNPI-1e', 'name' => 'Adults obtained employment (living wage+)', 'description' => 'The number of unemployed adults who obtained employment (with a living wage or higher).'],
                    ['indicator_code' => 'FNPI-1f', 'name' => 'Adults maintained employment 90 days (living wage+)', 'description' => 'The number of unemployed adults who obtained and maintained employment for at least 90 days (with a living wage or higher).'],
                    ['indicator_code' => 'FNPI-1g', 'name' => 'Adults maintained employment 180 days (living wage+)', 'description' => 'The number of unemployed adults who obtained and maintained employment for at least 180 days (with a living wage or higher).'],
                    [
                        'indicator_code' => 'FNPI-1h', 'name' => 'Employed participants increased income/benefits', 'is_aggregate' => true,
                        'description' => 'The number of employed participants in a career-advancement related program who entered or transitioned into a position that provided increased income and/or benefits.',
                        'children' => [
                            ['indicator_code' => 'FNPI-1h.1', 'name' => 'Increased income through wage/salary increase', 'description' => 'Of the above, the number of employed participants who increased income from employment through wage or salary amount increase.'],
                            ['indicator_code' => 'FNPI-1h.2', 'name' => 'Increased income through hours worked increase', 'description' => 'Of the above, the number of employed participants who increased income from employment through hours worked increase.'],
                            ['indicator_code' => 'FNPI-1h.3', 'name' => 'Increased benefits related to employment', 'description' => 'Of the above, the number of employed participants who increased benefits related to employment.'],
                        ],
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // Goal 2 — Education and Cognitive Development
            // ----------------------------------------------------------------
            [
                'goal_number' => 2,
                'name' => 'Education and Cognitive Development',
                'description' => 'Individuals and families with low incomes are stable and achieve economic security.',
                'indicators' => [
                    ['indicator_code' => 'FNPI-2a', 'name' => 'Children (0-5) improved emergent literacy', 'description' => 'The number of children (0 to 5) who demonstrated improved emergent literacy skills.'],
                    ['indicator_code' => 'FNPI-2b', 'name' => 'Children (0-5) demonstrated school readiness', 'description' => 'The number of children (0 to 5) who demonstrated skills for school readiness.'],
                    [
                        'indicator_code' => 'FNPI-2c', 'name' => 'Children/youth improved approaches toward learning', 'is_aggregate' => true,
                        'description' => 'The number of children and youth who demonstrated improved positive approaches toward learning, including improved attention skills.',
                        'children' => [
                            ['indicator_code' => 'FNPI-2c.1', 'name' => 'Early Childhood Education (ages 0-5)', 'description' => 'Early Childhood Education (ages 0-5)'],
                            ['indicator_code' => 'FNPI-2c.2', 'name' => '1st grade-8th grade', 'description' => '1st grade-8th grade'],
                            ['indicator_code' => 'FNPI-2c.3', 'name' => '9th grade-12th grade', 'description' => '9th grade-12th grade'],
                        ],
                    ],
                    [
                        'indicator_code' => 'FNPI-2d', 'name' => 'Children/youth achieving at basic grade level', 'is_aggregate' => true,
                        'description' => 'The number of children and youth who are achieving at basic grade level (academic, social, and other school success skills).',
                        'children' => [
                            ['indicator_code' => 'FNPI-2d.1', 'name' => 'Early Childhood Education (ages 0-5)', 'description' => 'Early Childhood Education (ages 0-5)'],
                            ['indicator_code' => 'FNPI-2d.2', 'name' => '1st grade-8th grade', 'description' => '1st grade-8th grade'],
                            ['indicator_code' => 'FNPI-2d.3', 'name' => '9th grade-12th grade', 'description' => '9th grade-12th grade'],
                        ],
                    ],
                    ['indicator_code' => 'FNPI-2e', 'name' => 'Parents/caregivers improved home environments', 'description' => 'The number of parents/caregivers who improved their home environments.'],
                    ['indicator_code' => 'FNPI-2f', 'name' => 'Adults improved basic education', 'description' => 'The number of adults who demonstrated improved basic education.'],
                    ['indicator_code' => 'FNPI-2g', 'name' => 'Obtained HS diploma or equivalency', 'description' => 'The number of individuals who obtained a high school diploma and/or obtained an equivalency certificate or diploma.'],
                    ['indicator_code' => 'FNPI-2h', 'name' => 'Obtained credential/certificate/degree', 'description' => 'The number of individuals who obtained a recognized credential, certificate, or degree relating to the achievement of educational or vocational skills.'],
                    ['indicator_code' => 'FNPI-2i', 'name' => 'Obtained Associate\'s degree', 'description' => 'The number of individuals who obtained an Associate\'s degree.'],
                    ['indicator_code' => 'FNPI-2j', 'name' => 'Obtained Bachelor\'s degree', 'description' => 'The number of individuals who obtained a Bachelor\'s degree.'],
                ],
            ],

            // ----------------------------------------------------------------
            // Goal 3 — Income and Asset Building
            // ----------------------------------------------------------------
            [
                'goal_number' => 3,
                'name' => 'Income and Asset Building',
                'description' => 'Individuals and families with low incomes are stable and achieve economic security.',
                'indicators' => [
                    ['indicator_code' => 'FNPI-3a', 'name' => 'Achieved capacity to meet basic needs 90 days', 'description' => 'The number of individuals who achieved and maintained capacity to meet basic needs for 90 days.'],
                    ['indicator_code' => 'FNPI-3b', 'name' => 'Achieved capacity to meet basic needs 180 days', 'description' => 'The number of individuals who achieved and maintained capacity to meet basic needs for 180 days.'],
                    ['indicator_code' => 'FNPI-3c', 'name' => 'Opened savings account or IDA', 'description' => 'The number of individuals who opened a savings account or IDA.'],
                    ['indicator_code' => 'FNPI-3d', 'name' => 'Increased savings', 'description' => 'The number of individuals who increased their savings.'],
                    ['indicator_code' => 'FNPI-3e', 'name' => 'Used savings to purchase asset', 'description' => 'The number of individuals who used their savings to purchase an asset.'],
                    ['indicator_code' => 'FNPI-3f', 'name' => 'Purchased a home', 'description' => 'The number of individuals who purchased a home.'],
                    ['indicator_code' => 'FNPI-3g', 'name' => 'Improved credit scores', 'description' => 'The number of individuals who improved their credit scores.'],
                    ['indicator_code' => 'FNPI-3h', 'name' => 'Increased net worth', 'description' => 'The number of individuals who increased their net worth.'],
                    ['indicator_code' => 'FNPI-3i', 'name' => 'Improved financial well-being', 'description' => 'The number of individuals engaged with the Community Action Agency who report improved financial well-being.'],
                ],
            ],

            // ----------------------------------------------------------------
            // Goal 4 — Housing
            // ----------------------------------------------------------------
            [
                'goal_number' => 4,
                'name' => 'Housing',
                'description' => 'Individuals and families with low incomes are stable and achieve economic security.',
                'indicators' => [
                    ['indicator_code' => 'FNPI-4a', 'name' => 'Homeless obtained temporary shelter', 'description' => 'The number of individuals experiencing homelessness who obtained safe temporary shelter.'],
                    ['indicator_code' => 'FNPI-4b', 'name' => 'Obtained safe affordable housing', 'description' => 'The number of individuals who obtained safe and affordable housing.'],
                    ['indicator_code' => 'FNPI-4c', 'name' => 'Maintained housing 90 days', 'description' => 'The number of individuals who maintained safe and affordable housing for 90 days.'],
                    ['indicator_code' => 'FNPI-4d', 'name' => 'Maintained housing 180 days', 'description' => 'The number of individuals who maintained safe and affordable housing for 180 days.'],
                    ['indicator_code' => 'FNPI-4e', 'name' => 'Avoided eviction', 'description' => 'The number of individuals who avoided eviction.'],
                    ['indicator_code' => 'FNPI-4f', 'name' => 'Avoided foreclosure', 'description' => 'The number of individuals who avoided foreclosure.'],
                    ['indicator_code' => 'FNPI-4g', 'name' => 'Improved health/safety from home improvements', 'description' => 'The number of individuals who experienced improved health and safety due to improvements within their home (e.g. reduction or elimination of lead, radon, carbon monoxide and/or fire hazards or electrical issues, etc).'],
                    ['indicator_code' => 'FNPI-4h', 'name' => 'Improved energy efficiency/burden reduction', 'description' => 'The number of individuals with improved energy efficiency and/or energy burden reduction in their homes.'],
                ],
            ],

            // ----------------------------------------------------------------
            // Goal 5 — Health and Social/Behavioral Development
            // ----------------------------------------------------------------
            [
                'goal_number' => 5,
                'name' => 'Health and Social/Behavioral Development',
                'description' => 'Individuals and families with low incomes are stable and achieve economic security.',
                'indicators' => [
                    ['indicator_code' => 'FNPI-5a', 'name' => 'Increased nutrition skills', 'description' => 'The number of individuals who demonstrated increased nutrition skills (e.g. cooking, shopping, and growing food).'],
                    ['indicator_code' => 'FNPI-5b', 'name' => 'Improved physical health', 'description' => 'The number of individuals who demonstrated improved physical health and well-being.'],
                    ['indicator_code' => 'FNPI-5c', 'name' => 'Improved mental/behavioral health', 'description' => 'The number of individuals who demonstrated improved mental and behavioral health and well-being.'],
                    ['indicator_code' => 'FNPI-5d', 'name' => 'Improved parenting/caregiving skills', 'description' => 'The number of individuals who improved skills related to the adult role of parents/caregivers.'],
                    ['indicator_code' => 'FNPI-5e', 'name' => 'Parents increased sensitivity/responsiveness', 'description' => 'The number of parents/caregivers who demonstrated increased sensitivity and responsiveness in their interactions with their children.'],
                    ['indicator_code' => 'FNPI-5f', 'name' => 'Seniors maintained independent living', 'description' => 'The number of seniors (65+) who maintained an independent living situation.'],
                    ['indicator_code' => 'FNPI-5g', 'name' => 'Disabled maintained independent living', 'description' => 'The number of individuals with disabilities who maintained an independent living situation.'],
                    ['indicator_code' => 'FNPI-5h', 'name' => 'Chronic illness maintained independent living', 'description' => 'The number of individuals with a chronic illness who maintained an independent living situation.'],
                    [
                        'indicator_code' => 'FNPI-5i', 'name' => 'No recidivating event for six months', 'is_aggregate' => true,
                        'description' => 'The number of individuals with no recidivating event for six months.',
                        'children' => [
                            ['indicator_code' => 'FNPI-5i.1', 'name' => 'Youth (ages 14-17)', 'description' => 'Youth (ages 14-17)'],
                            ['indicator_code' => 'FNPI-5i.2', 'name' => 'Adults (ages 18+)', 'description' => 'Adults (ages 18+)'],
                        ],
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // Goal 6 — Civic Engagement and Community Involvement
            // ----------------------------------------------------------------
            [
                'goal_number' => 6,
                'name' => 'Civic Engagement and Community Involvement',
                'description' => 'Individuals and families with low incomes are stable and achieve economic security.',
                'indicators' => [
                    [
                        'indicator_code' => 'FNPI-6a', 'name' => 'Increased skills to improve community conditions', 'is_aggregate' => true,
                        'description' => 'The number of individuals who increased skills, knowledge, and abilities to enable them to work with Community Action to improve conditions in the community.',
                        'children' => [
                            ['indicator_code' => 'FNPI-6a.1', 'name' => 'Improved leadership skills', 'description' => 'Of the above, the number of Community Action program participants who improved their leadership skills.'],
                            ['indicator_code' => 'FNPI-6a.2', 'name' => 'Improved social networks', 'description' => 'Of the above, the number of Community Action program participants who improved their social networks.'],
                            ['indicator_code' => 'FNPI-6a.3', 'name' => 'Gained other skills/knowledge/abilities', 'description' => 'Of the above, the number of Community Action program participants who gained other skills, knowledge and abilities to enhance their ability to engage.'],
                        ],
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // Goal 7 — Outcomes Across Multiple Domains
            // ----------------------------------------------------------------
            [
                'goal_number' => 7,
                'name' => 'Outcomes Across Multiple Domains',
                'description' => 'Individuals and families with low incomes are stable and achieve economic security.',
                'indicators' => [
                    ['indicator_code' => 'FNPI-7a', 'name' => 'Achieved outcomes in one or more domains', 'description' => 'The number of individuals who achieved one or more outcomes in the identified National Performance Indicators in one or more domains.'],
                ],
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\GoalCategory;
use App\Models\GoalMeasure;
use App\Models\GoalSheetCategory;
use Illuminate\Database\Seeder;

class GoalSheetsSeeder extends Seeder
{
    public function run(): void
    {
        // The six worksheets of the 2026 GOAL SHEETS workbook. Names are stored
        // exactly as they appear in the workbook so imports match cleanly.
        $sheetCategories = [
            ['name' => 'CBE E.Y.E', 'code' => 'CBE_EYE', 'sort_order' => 1],
            ['name' => 'ICDC  E.Y.E', 'code' => 'ICDC_EYE', 'sort_order' => 2],
            ['name' => 'CBE UPPER PRIMARY', 'code' => 'CBE_UP', 'sort_order' => 3],
            ['name' => 'ICDC  UPPER PRIMARY', 'code' => 'ICDC_UP', 'sort_order' => 4],
            ['name' => 'C.B.EJUNIOR SCHOOL', 'code' => 'CBE_JS', 'sort_order' => 5],
            ['name' => 'I.C.D.C JUNIOR SCHOOL', 'code' => 'ICDC_JS', 'sort_order' => 6],
        ];

        foreach ($sheetCategories as $category) {
            GoalSheetCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['is_active' => true])
            );
        }

        // Goal categories from the workbook. Not hardcoded: super admins can
        // add, edit or retire categories and their default measures.
        $goalCategories = [
            ['name' => 'Academic Performance', 'code' => 'ACADEMIC', 'sort_order' => 1],
            ['name' => 'Reading Skills', 'code' => 'READING', 'sort_order' => 2],
            ['name' => 'Writing Skills', 'code' => 'WRITING', 'sort_order' => 3],
            ['name' => 'Learner Discipline', 'code' => 'DISCIPLINE', 'sort_order' => 4],
        ];

        foreach ($goalCategories as $category) {
            GoalCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['is_active' => true])
            );
        }

        $measures = [
            'Academic Performance' => [
                'name' => 'Academic Performance (subject/class attainment)',
                'goal_statement' => '80% of learners to score 80%(EE) in all subjects assigned to the teacher by the end of the year.',
                'uom' => '%',
                'calculation_method' => 'No of students with 80% score divided by Total No of Students in the class',
                'source_of_data' => 'Class Mark Sheet',
                'achievement_type' => 'HB',
                'performance_weight' => 0.5,
                'goal_for_year' => 80,
            ],
            'Reading Skills' => [
                'name' => 'Reading Skills',
                'goal_statement' => '80% of the learners to score 80% (EE) in reading skills by the end of the year',
                'uom' => '%',
                'calculation_method' => 'No of students with 80% score divided by Total No of Students in the class',
                'source_of_data' => 'Class Mark Sheet',
                'achievement_type' => 'HB',
                'performance_weight' => 0.2,
                'goal_for_year' => 80,
            ],
            'Writing Skills' => [
                'name' => 'Writing Skills',
                'goal_statement' => '80% of the learners to score 80% (EE) in writing skills by the end of the year',
                'uom' => '%',
                'calculation_method' => 'No of students with 80% score divided by Total No of Students in the class',
                'source_of_data' => 'Class Mark Sheet',
                'achievement_type' => 'HB',
                'performance_weight' => 0.2,
                'goal_for_year' => 80,
            ],
            'Learner Discipline' => [
                'name' => 'Learner Discipline',
                'goal_statement' => '90% of learners to demonstrate discipline and moral character every month, term and year.',
                'uom' => '%',
                'calculation_method' => 'No of Students with no discipline cases divided by the total number of students in the class',
                'source_of_data' => 'Discipline Record Book',
                'achievement_type' => 'HB',
                'performance_weight' => 0.1,
                'goal_for_year' => 90,
            ],
        ];

        foreach ($measures as $categoryName => $measure) {
            $category = GoalCategory::query()->where('name', $categoryName)->first();

            GoalMeasure::query()->updateOrCreate(
                ['goal_category_id' => $category->id, 'name' => $measure['name']],
                array_merge($measure, [
                    'goal_category_id' => $category->id,
                    'sort_order' => 1,
                    'is_active' => true,
                ])
            );
        }
    }
}

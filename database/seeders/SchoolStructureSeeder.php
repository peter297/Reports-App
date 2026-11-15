<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Stream;
use Illuminate\Database\Seeder;

class SchoolStructureSeeder extends Seeder
{
    /**
     * Initial academic structure. All entities are created idempotently,
     * so this seeder is safe to re-run after admin changes.
     */
    public function run(): void
    {
        $structure = [
            'Juja Road' => [
                'Early Years Education' => ['Grade 1', 'Grade 2', 'Grade 3'],
                'Upper Primary' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'Junior School' => ['Grade 7', 'Grade 8', 'Grade 9'],
            ],
            'South C' => [
                'Early Years Education' => ['K1', 'PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3'],
                'Upper Primary' => ['Grade 4', 'Grade 5', 'Grade 6'],
                'Junior School' => ['Grade 7', 'Grade 8'],
            ],
            'Kitisuru' => [
                'Early Years Education' => ['K1', 'PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3'],
                'Upper Primary' => ['Grade 4', 'Grade 5', 'Grade 6'],
            ],
        ];

        // Seed an active school session and term so fresh installs are usable.
        $yearSession = \App\Models\YearSession::firstOrCreate(
            ['name' => '2026 Academic Year'],
            ['start_date' => '2026-01-05', 'end_date' => '2026-12-04', 'status' => 'active'],
        );

        \App\Models\Term::firstOrCreate(
            ['year_session_id' => $yearSession->id, 'name' => 'Term 1'],
            ['start_date' => '2026-01-05', 'end_date' => '2026-03-27', 'status' => 'active'],
        );

        $streamNames = ['Blue', 'Green', 'Red'];

        foreach ($structure as $branchName => $levels) {
            $branch = Branch::firstOrCreate(
                ['name' => $branchName],
                ['code' => strtoupper(substr(preg_replace('/\s+/', '', $branchName), 0, 4)), 'is_active' => true],
            );

            foreach ($levels as $levelName => $classNames) {
                $level = AcademicLevel::firstOrCreate(
                    ['name' => $levelName],
                    ['sort_order' => match ($levelName) {
                        'Early Years Education' => 1,
                        'Upper Primary' => 2,
                        'Junior School' => 3,
                        default => 99,
                    }, 'is_active' => true],
                );

                foreach ($classNames as $className) {
                    $class = Classes::firstOrCreate(
                        ['branch_id' => $branch->id, 'academic_level_id' => $level->id, 'name' => $className],
                        ['is_active' => true],
                    );

                    foreach ($streamNames as $streamName) {
                        Stream::firstOrCreate(
                            ['class_id' => $class->id, 'name' => $streamName],
                            ['is_active' => true],
                        );
                    }
                }
            }
        }
    }
}

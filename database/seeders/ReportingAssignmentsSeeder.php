<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ReportingAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $role = fn (string $name): int => Role::findByName($name, 'web')->id;

        // [submitter role, branch (name|null), level (name|null), recipient role, sort]
        $rules = [
            // Administrators submit branch administrative reports to the Principal.
            ['Administrator', null, null, 'Principal', 1],
            // Coordinators (all branches) submit academic reports to the Head Teacher.
            ['Coordinator', null, null, 'Head Teacher', 1],
            // Deputy Head Teachers and Deans of Islamic Studies report to the Head Teacher.
            ['Deputy Head Teacher', null, null, 'Head Teacher', 2],
            ['Dean of Islamic Studies', null, null, 'Head Teacher', 3],
            // Deputy Principal's designated recipient (configurable).
            ['Deputy Principal', null, null, 'Principal', 1],
            // Fleet Manager reports directly to the CEO.
            ['Fleet Manager', null, null, 'CEO', 1],
            // ICT Officer reports to the Principal (CEO has view_all access).
            ['ICT Officer', null, null, 'Principal', 1],
            // HR Officer reports to both the Principal and the CEO.
            ['HR Officer', null, null, 'Principal', 1],
            ['HR Officer', null, null, 'CEO', 2],
            // Consolidation chain.
            ['Head Teacher', null, null, 'Principal', 1],
            ['Principal', null, null, 'CEO', 1],
        ];

        foreach ($rules as [$submitter, $branchName, $levelName, $recipient, $sort]) {
            $branchId = $branchName ? Branch::where('name', $branchName)->value('id') : null;
            $levelId = $levelName ? \App\Models\AcademicLevel::where('name', $levelName)->value('id') : null;

            $exists = \App\Models\ReportingAssignment::query()
                ->where('submitter_role_id', $role($submitter))
                ->where('branch_id', $branchId)
                ->where('academic_level_id', $levelId)
                ->where('recipient_role_id', $role($recipient))
                ->exists();

            if (! $exists) {
                \App\Models\ReportingAssignment::create([
                    'submitter_role_id' => $role($submitter),
                    'branch_id' => $branchId,
                    'academic_level_id' => $levelId,
                    'recipient_role_id' => $role($recipient),
                    'sort_order' => $sort,
                    'is_active' => true,
                ]);
            }
        }
    }
}

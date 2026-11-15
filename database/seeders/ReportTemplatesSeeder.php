<?php

namespace Database\Seeders;

use App\Enums\ReportFrequency;
use App\Models\ReportTemplate;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ReportTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $definition) {
            $this->seedTemplate($definition);
        }
    }

    /** @return array<int, array{name: string, role: string, frequency: string, description: string, sections: array<int, array{title: string, is_auto?: bool, data_source?: ?string, is_mandatory?: bool, help_text?: ?string}>}> */
    private function templates(): array
    {
        return [
            [
                'name' => 'Principal Consolidated Report',
                'role' => 'Principal',
                'frequency' => ReportFrequency::Termly->value,
                'description' => 'Consolidated report compiled by the Principal from the three branch administrative reports and the Head Teacher consolidated academic report.',
                'sections' => [
                    ['title' => 'School Population (All Branches)', 'is_auto' => true, 'data_source' => 'enrollment.org_breakdown', 'help_text' => 'Learner population by branch — pulled from the Enrollment module. Edit before submitting if figures have changed.'],
                    ['title' => 'Current Academic Position', 'is_auto' => true, 'data_source' => 'syllabus.coverage', 'help_text' => 'Aggregated syllabus coverage across the school, plus observations from the Head Teacher\'s consolidated report.'],
                    ['title' => 'Learner Achievements', 'is_auto' => true, 'data_source' => 'assessment.formative'],
                    ['title' => 'Staff Development'],
                    ['title' => 'Branch Priorities', 'help_text' => 'Priorities per branch, preserved with branch attribution (Juja Road / South C / Kitisuru).'],
                    ['title' => 'Resources'],
                    ['title' => 'Events of the Following Term'],
                    ['title' => 'Conclusion'],
                ],
            ],
            [
                'name' => 'Head Teacher Consolidated Report',
                'role' => 'Head Teacher',
                'frequency' => ReportFrequency::Termly->value,
                'description' => 'Consolidated academic report compiled by the Head Teacher from Coordinator, Deputy Head Teacher and Dean reports across all branches.',
                'sections' => [
                    ['title' => 'Syllabus Mapping', 'is_auto' => true, 'data_source' => 'syllabus.coverage'],
                    ['title' => 'Attendance', 'is_auto' => true, 'data_source' => 'attendance.org_summary'],
                    ['title' => 'Formative Assessments', 'is_auto' => true, 'data_source' => 'assessment.formative'],
                    ['title' => 'Discipline', 'is_auto' => true, 'data_source' => 'discipline.org_summary'],
                    ['title' => 'Lesson Attendance', 'is_auto' => true, 'data_source' => 'lesson_attendance.note', 'is_mandatory' => false],
                    ['title' => 'Professional Documents', 'is_auto' => true, 'data_source' => 'documents.status', 'is_mandatory' => false],
                ],
            ],
            [
                'name' => 'Administrator Report',
                'role' => 'Administrator',
                'frequency' => ReportFrequency::Monthly->value,
                'description' => 'Branch administrative report submitted by each branch Administrator to the Principal. Learner numbers are pulled from the Enrollment module.',
                'sections' => [
                    ['title' => 'Learner Numbers', 'is_auto' => true, 'data_source' => 'enrollment.branch_learners', 'help_text' => 'Current learner population for your branch — do not type manually. You may add observations below.'],
                    ['title' => 'Enrollment Observations'],
                    ['title' => 'Recommendations', 'help_text' => 'Recommendations following the learner number review.'],
                    ['title' => 'School Activities'],
                    ['title' => 'Activity Observations'],
                    ['title' => 'Recommendations (activities)'],
                    ['title' => 'Professional Development'],
                    ['title' => 'Follow-up / Action Plan'],
                    ['title' => 'Pending Issues / Action Tracker'],
                ],
            ],
            [
                'name' => 'Coordinator Report',
                'role' => 'Coordinator',
                'frequency' => ReportFrequency::Monthly->value,
                'description' => 'Academic progress report for the teachers, classes and subjects under a Coordinator. Populated from system data where available.',
                'sections' => [
                    ['title' => 'Syllabus Mapping', 'is_auto' => true, 'data_source' => 'syllabus.coverage', 'help_text' => 'Coverage for mappings in your scope. Add overall observations here.'],
                    ['title' => 'Attendance', 'is_auto' => true, 'data_source' => 'attendance.branch_summary'],
                    ['title' => 'Formative Assessments', 'is_auto' => true, 'data_source' => 'assessment.formative'],
                    ['title' => 'Discipline', 'is_auto' => true, 'data_source' => 'discipline.branch_summary'],
                    ['title' => 'Lesson Attendance', 'is_auto' => true, 'data_source' => 'lesson_attendance.note', 'is_mandatory' => false],
                    ['title' => 'Professional Documents', 'is_auto' => true, 'data_source' => 'documents.status', 'is_mandatory' => false],
                ],
            ],
            [
                'name' => 'Teacher Report',
                'role' => 'Teacher',
                'frequency' => ReportFrequency::Termly->value,
                'description' => 'Termly report for teaching staff, derived from their own assignments. Teachers do not re-enter data the system already holds.',
                'sections' => [
                    ['title' => 'Attendance', 'is_auto' => true, 'data_source' => 'attendance.teacher_summary'],
                    ['title' => 'Formative Assessments', 'is_auto' => true, 'data_source' => 'assessment.formative'],
                    ['title' => 'Discipline', 'is_auto' => true, 'data_source' => 'discipline.teacher_summary'],
                    ['title' => 'Syllabus Mapping', 'is_auto' => true, 'data_source' => 'syllabus.coverage'],
                ],
            ],
            [
                'name' => 'Deputy Head Teacher Report',
                'role' => 'Deputy Head Teacher',
                'frequency' => ReportFrequency::Monthly->value,
                'description' => 'Branch-wide operational report for the Deputy Head Teacher.',
                'sections' => [
                    ['title' => 'Attendance (Entire Branch)', 'is_auto' => true, 'data_source' => 'attendance.branch_summary'],
                    ['title' => 'Syllabus Mapping (Entire Branch)', 'is_auto' => true, 'data_source' => 'syllabus.coverage'],
                    ['title' => 'Discipline (Entire Branch)', 'is_auto' => true, 'data_source' => 'discipline.branch_summary'],
                    ['title' => 'Lesson Attendance', 'is_auto' => true, 'data_source' => 'lesson_attendance.note', 'is_mandatory' => false],
                    ['title' => 'Professional Documents', 'is_auto' => true, 'data_source' => 'documents.status', 'is_mandatory' => false],
                ],
            ],
            [
                'name' => 'Dean of Islamic Studies Report',
                'role' => 'Dean of Islamic Studies',
                'frequency' => ReportFrequency::Monthly->value,
                'description' => 'Islamic Studies report for the Dean, scoped to Islamic Studies subjects in the assigned branch.',
                'sections' => [
                    ['title' => 'Attendance (Branch)', 'is_auto' => true, 'data_source' => 'attendance.branch_summary'],
                    ['title' => 'Syllabus Mapping (Islamic Studies)', 'is_auto' => true, 'data_source' => 'syllabus.coverage', 'help_text' => 'Coverage for Islamic Studies mappings in your branch.'],
                    ['title' => 'Discipline (Branch)', 'is_auto' => true, 'data_source' => 'discipline.branch_summary'],
                    ['title' => 'Lesson Attendance', 'is_auto' => true, 'data_source' => 'lesson_attendance.note', 'is_mandatory' => false],
                    ['title' => 'Professional Documents (Islamic Studies)', 'is_auto' => true, 'data_source' => 'documents.status', 'is_mandatory' => false],
                ],
            ],
            [
                'name' => 'HR Officer Report',
                'role' => 'HR Officer',
                'frequency' => ReportFrequency::Monthly->value,
                'description' => 'HR report submitted by the HR Officer to the Principal and the CEO.',
                'sections' => [
                    ['title' => 'Recruitment & Staffing'],
                    ['title' => 'Staff Welfare & Wellbeing'],
                    ['title' => 'Professional Development'],
                    ['title' => 'Policy & Compliance'],
                    ['title' => 'Pending HR Issues'],
                ],
            ],
            [
                'name' => 'Fleet Manager Report',
                'role' => 'Fleet Manager',
                'frequency' => ReportFrequency::Monthly->value,
                'description' => 'Fleet and transport report submitted by the Fleet Manager to the CEO.',
                'sections' => [
                    ['title' => 'Fleet Operations'],
                    ['title' => 'Maintenance & Repairs'],
                    ['title' => 'Fuel & Operating Costs'],
                    ['title' => 'Incidents & Accidents'],
                    ['title' => 'Upcoming Service Schedule'],
                ],
            ],
            [
                'name' => 'Deputy Principal Report',
                'role' => 'Deputy Principal',
                'frequency' => ReportFrequency::Termly->value,
                'description' => 'Oversight report for the Deputy Principal across the organization.',
                'sections' => [
                    ['title' => 'School Population (All Branches)', 'is_auto' => true, 'data_source' => 'enrollment.org_breakdown'],
                    ['title' => 'Academic Overview', 'is_auto' => true, 'data_source' => 'syllabus.coverage'],
                    ['title' => 'Attendance Overview', 'is_auto' => true, 'data_source' => 'attendance.org_summary'],
                    ['title' => 'Discipline Overview', 'is_auto' => true, 'data_source' => 'discipline.org_summary'],
                    ['title' => 'Priorities & Oversight'],
                ],
            ],
        ];
    }

    /** @param array{name: string, role: string, frequency: string, description: string, sections: array<int, array{title: string, is_auto?: bool, data_source?: ?string, is_mandatory?: bool, help_text?: ?string}>} $definition */
    private function seedTemplate(array $definition): void
    {
        $role = Role::where('name', $definition['role'])->first();

        if (! $role) {
            $this->command?->warn("Role [{$definition['role']}] not found; skipping template [{$definition['name']}].");

            return;
        }

        $template = ReportTemplate::query()->firstOrCreate(
            ['name' => $definition['name'], 'role_id' => $role->id],
            [
                'description' => $definition['description'],
                'frequency' => $definition['frequency'],
                'is_active' => true,
                'created_by' => null,
            ],
        );

        // Skip if a version already exists (idempotent re-seeds keep history).
        if ($template->currentVersion()->exists()) {
            return;
        }

        $template->publishVersion(
            array_map(fn (array $section, int $index): array => [
                'title' => $section['title'],
                'sort_order' => $index + 1,
                'is_mandatory' => $section['is_mandatory'] ?? true,
                'is_auto' => $section['is_auto'] ?? false,
                'data_source' => $section['data_source'] ?? null,
                'help_text' => $section['help_text'] ?? null,
            ], $definition['sections'], array_keys($definition['sections'])),
            'Initial template',
            null
        );
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Normalize legacy roles to the specified organizational structure.
     * Renames are done in place so user assignments are preserved.
     */
    public function run(): void
    {
        $this->normalizeRoles();
        $this->seedPermissions();
        $this->seedRoleMatrix();
    }

    private function normalizeRoles(): void
    {
        // Legacy plural/inconsistent names -> specification names (in place).
        $renames = [
            'HeadTeacher' => 'Head Teacher',
            'Coordinators' => 'Coordinator',
            'Teachers' => 'Teacher',
            'Deputy HeadTeacher' => 'Deputy Head Teacher',
            'School Administrator' => 'Administrator',
            'ICT Department' => 'ICT Officer',
        ];

        foreach ($renames as $from => $to) {
            $this->renameRole($from, $to);
        }

        // Unused legacy roles outside the specified structure (no users hold them).
        foreach (['Admin', 'Head of Admissions'] as $legacy) {
            $role = Role::where('name', $legacy)->first();
            if ($role && ! $role->users()->exists()) {
                $role->delete();
                $this->command?->warn("Removed unused role [{$legacy}].");
            }
        }

        // Ensure every specified role exists exactly once.
        foreach ($this->specRoles() as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function renameRole(string $from, string $to): void
    {
        $legacy = Role::where('name', $from)->first();
        if (! $legacy) {
            return;
        }

        $target = Role::where('name', $to)->first();
        if ($target) {
            // Merge: re-point assignments then drop the legacy row.
            foreach ($legacy->users as $user) {
                $user->assignRole($target);
                $user->removeRole($legacy);
            }
            $legacy->delete();
            return;
        }

        DB::table('roles')->where('id', $legacy->id)->update(['name' => $to]);
    }

    /** @return list<string> */
    private function specRoles(): array
    {
        return [
            'super_admin',
            'CEO',
            'Principal',
            'Deputy Principal',
            'Head Teacher',
            'Deputy Head Teacher',
            'Coordinator',
            'Dean of Islamic Studies',
            'Administrator',
            'ICT Officer',
            'HR Officer',
            'Fleet Manager',
            'Teacher',
        ];
    }

    /** @return array<string, string> name => description */
    private function permissions(): array
    {
        return [
            // General
            'dashboard.view' => 'View the Dashboard',
            // Goal sheets module
            'goalsheets.view' => 'View goal sheets within scope',
            'goalsheets.create' => 'Create goal sheets',
            'goalsheets.edit' => 'Edit goal sheets (draft only)',
            'goalsheets.delete' => 'Delete goal sheets',
            'goalsheets.acknowledge' => 'Acknowledge/sign goal sheets (owner or line managers)',
            'goalsheets.reports' => 'View consolidated goal sheet dashboards and summaries',
            'goalsheets.admin' => 'Import the GOAL SHEETS workbook and configure categories/measures',
            // Syllabus module
            'syllabus.dashboard.view' => 'View the syllabus mapping dashboard',
            'syllabus.mappings.view' => 'View syllabus mappings within scope',
            'syllabus.mappings.progress' => 'Record lesson completion progress',
            'syllabus.mappings.manage' => 'Manage syllabus mappings and structure',
            'syllabus.reviews.manage' => 'Add coordinator review comments',
            'syllabus.reports.view' => 'Download syllabus mapping reports',
            // Organizational reports module
            'reports.module.view' => 'See the Reports module in navigation',
            'reports.view' => 'View organizational reports (scoped)',
            'reports.view_all' => 'View all branch and department reports',
            'reports.create' => 'Create reports from templates',
            'reports.submit' => 'Submit reports to line managers',
            'reports.review' => 'Mark reports received / under review, comment',
            'reports.return' => 'Return reports for revision',
            'reports.approve' => 'Approve reports',
            'reports.compile' => 'Compile consolidated reports',
            'reports.templates.manage' => 'Create and manage report templates',
            'reports.routing.manage' => 'Manage reporting hierarchy and routing',
            // School structure & sessions
            'sessions.view' => 'View school sessions, terms and weeks',
            'sessions.manage' => 'Manage school sessions, terms and weeks',
            'branches.view' => 'View branches',
            'branches.manage' => 'Manage branches',
            'academic_structure.view' => 'View levels, classes, streams and subjects',
            'academic_structure.manage' => 'Manage levels, classes, streams and subjects',
            'enrollments.view' => 'View learner numbers and enrollment records',
            'enrollments.manage' => 'Manage learner numbers and enrollment records',
            'teacher_assignments.view' => 'View teacher assignments',
            'teacher_assignments.manage' => 'Manage teacher assignments',
            // Attendance & class sizes
            'attendance.view' => 'View attendance data',
            'attendances.manage' => 'Manage attendance records',
            // Discipline
            'discipline.view' => 'View discipline records',
            'discipline.manage' => 'Manage discipline records',
            // Assessments & professional documents
            'assessments.view' => 'View formative assessments',
            'documents.view' => 'View professional documents',
            // Events & item requests
            'events.view' => 'View events and the school calendar',
            'events.manage' => 'Manage events and calendar quotes',
            'item_requests.view' => 'View item requests',
            'item_requests.manage' => 'Manage item requests (approve / fulfil)',
            // Operations
            'billings.view' => 'View billing and fee records',
            'billings.manage' => 'Manage billing and fee records',
            'maintenance.view' => 'View maintenance work',
            'maintenance.manage' => 'Manage maintenance work',
            // Activities
            'co_curriculars.view' => 'View co-curricular activities',
            'co_curriculars.manage' => 'Manage co-curricular activities',
            'extra_curriculars.view' => 'View extra-curricular activities',
            'extra_curriculars.manage' => 'Manage extra-curricular activities',
            // Administration
            'users.view' => 'View users and assignments',
            'users.manage' => 'Manage users and assignments',
            'roles.manage' => 'Manage roles and permissions',
            'coordinator_assignments.view' => 'View coordinator assignments',
            'coordinator_assignments.manage' => 'Manage coordinator assignments',
            'settings.manage' => 'Manage system settings',
        ];
    }

    private function seedPermissions(): void
    {
        foreach ($this->permissions() as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }

        // Super Admin sees every catalog permission in the Shield UI.
        Role::findOrCreate('super_admin', 'web')->syncPermissions(array_keys($this->permissions()));
    }

    private function seedRoleMatrix(): void
    {
        $matrix = [
            'CEO' => [
                'goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.view_all', 'reports.create', 'reports.submit', 'reports.review',
                'syllabus.dashboard.view', 'syllabus.mappings.view', 'syllabus.reports.view',
                'attendance.view', 'discipline.view', 'assessments.view', 'documents.view',
                'sessions.view', 'branches.view', 'academic_structure.view', 'teacher_assignments.view', 'enrollments.view',
                'events.view', 'item_requests.view', 'billings.view', 'maintenance.view',
                'co_curriculars.view', 'extra_curriculars.view',
                'users.view', 'coordinator_assignments.view',
            ],
            'Principal' => [
                'goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.create', 'reports.submit', 'reports.review', 'reports.return', 'reports.approve', 'reports.compile',
                'syllabus.dashboard.view', 'syllabus.mappings.view', 'syllabus.reports.view',
                'attendance.view', 'discipline.view', 'assessments.view', 'documents.view',
                'sessions.view', 'branches.view', 'academic_structure.view', 'teacher_assignments.view', 'enrollments.view', 'enrollments.manage',
                'events.view', 'events.manage', 'item_requests.view', 'item_requests.manage',
                'billings.view', 'billings.manage', 'maintenance.view',
                'co_curriculars.view', 'extra_curriculars.view',
                'users.view', 'coordinator_assignments.view',
            ],
            'Deputy Principal' => [
                'goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.view_all', 'reports.review',
                'syllabus.dashboard.view', 'syllabus.mappings.view', 'syllabus.reports.view',
                'attendance.view', 'discipline.view', 'assessments.view', 'documents.view',
                'sessions.view', 'branches.view', 'academic_structure.view', 'teacher_assignments.view', 'enrollments.view',
                'events.view', 'item_requests.view',
                'co_curriculars.view', 'extra_curriculars.view',
                'users.view',
            ],
            'Head Teacher' => [
                'goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.create', 'reports.submit', 'reports.review', 'reports.return', 'reports.approve', 'reports.compile',
                'syllabus.dashboard.view', 'syllabus.mappings.view', 'syllabus.reviews.manage', 'syllabus.reports.view',
                'attendance.view', 'attendances.manage', 'discipline.view', 'assessments.view', 'documents.view',
                'sessions.view', 'branches.view', 'academic_structure.view', 'teacher_assignments.view', 'enrollments.view',
                'events.view', 'item_requests.view',
                'co_curriculars.view', 'extra_curriculars.view',
                'users.view',
            ],
            'Deputy Head Teacher' => [
                'goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.create', 'reports.submit',
                'syllabus.dashboard.view', 'syllabus.mappings.view', 'syllabus.reports.view',
                'attendance.view', 'attendances.manage', 'discipline.view', 'discipline.manage', 'documents.view',
                'sessions.view', 'branches.view', 'academic_structure.view', 'teacher_assignments.view', 'enrollments.view', 'enrollments.manage',
                'events.view', 'item_requests.view',
                'co_curriculars.view', 'extra_curriculars.view',
            ],
            'Coordinator' => [
                'goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.create', 'reports.submit',
                'syllabus.dashboard.view', 'syllabus.mappings.view', 'syllabus.reviews.manage', 'syllabus.reports.view',
                'attendance.view', 'attendances.manage', 'discipline.view', 'assessments.view', 'documents.view',
                'sessions.view', 'branches.view', 'academic_structure.view', 'teacher_assignments.view', 'enrollments.view',
                'events.view', 'item_requests.view',
                'co_curriculars.view', 'extra_curriculars.view',
            ],
            'Dean of Islamic Studies' => [
                'goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.create', 'reports.submit',
                'syllabus.dashboard.view', 'syllabus.mappings.view', 'syllabus.reviews.manage', 'syllabus.reports.view',
                'attendance.view', 'attendances.manage', 'discipline.view', 'documents.view',
                'sessions.view', 'branches.view', 'academic_structure.view', 'teacher_assignments.view', 'enrollments.view',
                'events.view', 'item_requests.view',
                'co_curriculars.view', 'extra_curriculars.view',
            ],
            'Administrator' => [
                'goalsheets.view', 'goalsheets.reports', 'goalsheets.admin',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.create', 'reports.submit',
                'attendance.view', 'attendances.manage', 'discipline.view', 'discipline.manage', 'documents.view',
                'sessions.view', 'branches.view', 'academic_structure.view', 'teacher_assignments.view',
                'enrollments.view', 'enrollments.manage',
                'events.view', 'events.manage', 'item_requests.view', 'item_requests.manage',
                'co_curriculars.view', 'extra_curriculars.view',
            ],
            'ICT Officer' => [
                'goalsheets.view',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.view_all', 'reports.create', 'reports.submit',
                'sessions.view', 'branches.view', 'academic_structure.view',
                'events.view', 'item_requests.view',
            ],
            'HR Officer' => [
                'goalsheets.view',
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.create', 'reports.submit',
                'sessions.view', 'events.view', 'users.view',
            ],
            'Fleet Manager' => [
                'dashboard.view',
                'reports.module.view', 'reports.view', 'reports.create', 'reports.submit',
                'events.view',
            ],
            'Teacher' => [
                'goalsheets.view', 'goalsheets.create', 'goalsheets.edit',
                'syllabus.dashboard.view', 'syllabus.mappings.view', 'syllabus.mappings.progress', 'syllabus.reports.view',
                'attendance.view', 'assessments.view',
                'events.view', 'item_requests.view',
            ],
        ];

        foreach ($matrix as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }
}

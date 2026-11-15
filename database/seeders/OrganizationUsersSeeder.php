<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\Branch;
use App\Models\CoordinatorAssignment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Idempotent organizational chart seed: one account per organizational
 * position so every acceptance path can be exercised. All accounts use
 * the development password "password123!" (rotate for production).
 */
class OrganizationUsersSeeder extends Seeder
{
    private const PASSWORD = 'password123!';

    public function run(): void
    {
        foreach ($this->users() as $definition) {
            $this->seedUser($definition);
        }

        $this->seedIslamicStudiesSubjects();
        $this->seedRelationships();
    }

    /** @return array<int, array{name: string, email: string, role: string, branch?: string|null, assignments?: array<int, array{branch: string, level: string}>}> */
    private function users(): array
    {
        return [
            ['name' => 'Chief Executive Officer', 'email' => 'ceo@alameenacademy.com', 'role' => 'CEO', 'branch' => null],
            ['name' => 'Principal', 'email' => 'principal@alameenacademy.com', 'role' => 'Principal', 'branch' => null],
            ['name' => 'Deputy Principal', 'email' => 'deputy.principal@alameenacademy.com', 'role' => 'Deputy Principal', 'branch' => null],
            ['name' => 'Head Teacher', 'email' => 'head.teacher@alameenacademy.com', 'role' => 'Head Teacher', 'branch' => null],

            ['name' => 'Juja Road Deputy Head Teacher', 'email' => 'dht.juja@alameenacademy.com', 'role' => 'Deputy Head Teacher', 'branch' => 'Juja Road'],
            ['name' => 'South C Deputy Head Teacher', 'email' => 'dht.southc@alameenacademy.com', 'role' => 'Deputy Head Teacher', 'branch' => 'South C'],

            ['name' => 'Juja Road Early Years Coordinator', 'email' => 'coord.eye.juja@alameenacademy.com', 'role' => 'Coordinator', 'branch' => 'Juja Road', 'assignments' => [['branch' => 'Juja Road', 'level' => 'Early Years Education']]],
            ['name' => 'Juja Road Upper Primary Coordinator', 'email' => 'coord.up.juja@alameenacademy.com', 'role' => 'Coordinator', 'branch' => 'Juja Road', 'assignments' => [['branch' => 'Juja Road', 'level' => 'Upper Primary']]],
            ['name' => 'Juja Road Junior School Coordinator', 'email' => 'coord.js.juja@alameenacademy.com', 'role' => 'Coordinator', 'branch' => 'Juja Road', 'assignments' => [['branch' => 'Juja Road', 'level' => 'Junior School']]],
            ['name' => 'South C Early Years Coordinator', 'email' => 'coord.eye.southc@alameenacademy.com', 'role' => 'Coordinator', 'branch' => 'South C', 'assignments' => [['branch' => 'South C', 'level' => 'Early Years Education']]],
            ['name' => 'Kitisuru Upper Primary Coordinator', 'email' => 'coord.up.kitisuru@alameenacademy.com', 'role' => 'Coordinator', 'branch' => 'Kitisuru', 'assignments' => [['branch' => 'Kitisuru', 'level' => 'Upper Primary']]],

            ['name' => 'Juja Road Dean of Islamic Studies', 'email' => 'dean.juja@alameenacademy.com', 'role' => 'Dean of Islamic Studies', 'branch' => 'Juja Road', 'assignments' => $this->allLevelAssignments('Juja Road')],
            ['name' => 'South C Dean of Islamic Studies', 'email' => 'dean.southc@alameenacademy.com', 'role' => 'Dean of Islamic Studies', 'branch' => 'South C', 'assignments' => $this->allLevelAssignments('South C')],
            ['name' => 'Kitisuru Dean of Islamic Studies', 'email' => 'dean.kitisuru@alameenacademy.com', 'role' => 'Dean of Islamic Studies', 'branch' => 'Kitisuru', 'assignments' => $this->allLevelAssignments('Kitisuru')],

            ['name' => 'Juja Road Administrator', 'email' => 'admin.juja@alameenacademy.com', 'role' => 'Administrator', 'branch' => 'Juja Road'],
            ['name' => 'South C Administrator', 'email' => 'admin.southc@alameenacademy.com', 'role' => 'Administrator', 'branch' => 'South C'],
            ['name' => 'Kitisuru Administrator', 'email' => 'admin.kitisuru@alameenacademy.com', 'role' => 'Administrator', 'branch' => 'Kitisuru'],

            ['name' => 'ICT Officer', 'email' => 'ict@alameenacademy.com', 'role' => 'ICT Officer', 'branch' => null],
            ['name' => 'HR Officer', 'email' => 'hr@alameenacademy.com', 'role' => 'HR Officer', 'branch' => null],
            ['name' => 'Fleet Manager', 'email' => 'fleet@alameenacademy.com', 'role' => 'Fleet Manager', 'branch' => null],

            ['name' => 'Amina Mwangi', 'email' => 'amina@alameenacademy.com', 'role' => 'Teacher', 'branch' => 'Juja Road', 'teacher_assignments' => [
                ['class' => 'Grade 1', 'stream' => 'Blue', 'subject' => 'ENGLISH'],
                ['class' => 'Grade 1', 'stream' => 'Blue', 'subject' => 'CREATIVE ACTIVITIES'],
            ]],
            ['name' => 'Brian Otieno', 'email' => 'brian@alameenacademy.com', 'role' => 'Teacher', 'branch' => 'Juja Road', 'teacher_assignments' => [
                ['class' => 'Grade 1', 'stream' => 'Green', 'subject' => 'ENGLISH'],
                ['class' => 'Grade 1', 'stream' => 'Green', 'subject' => 'CREATIVE ACTIVITIES'],
                ['class' => 'Grade 1', 'stream' => 'Green', 'subject' => 'ENVIRONMENTAL'],
            ]],
        ];
    }

    /** @return array<int, array{branch: string, level: string}> */
    private function allLevelAssignments(string $branch): array
    {
        return AcademicLevel::query()
            ->orderBy('sort_order')
            ->pluck('name')
            ->map(fn ($name) => ['branch' => $branch, 'level' => $name])
            ->all();
    }

    /** @param array{name: string, email: string, role: string, branch?: string|null, assignments?: array<int, array{branch: string, level: string}>} $definition */
    private function seedUser(array $definition): void
    {
        $branchId = isset($definition['branch'])
            ? Branch::where('name', $definition['branch'])->value('id')
            : null;

        $user = User::updateOrCreate(
            ['email' => $definition['email']],
            [
                'name' => $definition['name'],
                'password' => self::PASSWORD,
                'branch_id' => $branchId,
                'is_active' => true,
            ],
        );

        $user->syncRoles([$definition['role']]);

        foreach ($definition['teacher_assignments'] ?? [] as $assignment) {
            $this->seedTeacherAssignment($user, $assignment);
        }

        foreach ($definition['assignments'] ?? [] as $assignment) {
            $branchModel = Branch::where('name', $assignment['branch'])->first();
            $levelModel = AcademicLevel::where('name', $assignment['level'])->first();

            if (! $branchModel || ! $levelModel) {
                continue;
            }

            CoordinatorAssignment::firstOrCreate([
                'user_id' => $user->id,
                'branch_id' => $branchModel->id,
                'academic_level_id' => $levelModel->id,
            ], ['is_active' => true]);
        }

        // Keep the seeded test coordinator account (Saum) aligned with her role.
        if ($definition['email'] === 'coord.eye.juja@alameenacademy.com') {
            $saum = User::where('email', 'saum@alameenacademy.com')->first();

            if ($saum) {
                $saum->syncRoles(['Coordinator']);
                CoordinatorAssignment::firstOrCreate([
                    'user_id' => $saum->id,
                    'branch_id' => Branch::where('name', 'Juja Road')->value('id'),
                    'academic_level_id' => AcademicLevel::where('name', 'Early Years Education')->value('id'),
                ], ['is_active' => true]);
            }
        }
    }

    /**
     * Assign a teacher to a (class, stream, subject) combination in the
     * active (or latest) session and term.
     *
     * @param  array{class: string, stream: string, subject: string}  $assignment
     */
    private function seedTeacherAssignment(User $user, array $assignment): void
    {
        $session = \App\Models\YearSession::orderByDesc('id')->first();
        $term = $session ? \App\Models\Term::where('year_session_id', $session->id)->orderByDesc('id')->first() : null;

        if (! $session || ! $term) {
            return;
        }

        $branch = Branch::where('name', 'Juja Road')->first();

        $class = \App\Models\Classes::query()
            ->where('name', $assignment['class'])
            ->where('branch_id', $branch?->id)
            ->first();

        $subject = \App\Models\Subject::query()
            ->where('name', $assignment['subject'])
            ->where('academic_level_id', $class?->academic_level_id)
            ->first();

        if (! $class || ! $subject) {
            return;
        }

        $streamId = \App\Models\Stream::where('class_id', $class->id)->where('name', $assignment['stream'])->value('id');

        \App\Models\SubjectAssignment::firstOrCreate([
            'year_session_id' => $session->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'stream_id' => $streamId,
            'user_id' => $user->id,
            'assignment_role' => 'teacher',
        ], ['branch' => $branch?->name]);
    }

    private function seedIslamicStudiesSubjects(): void
    {
        foreach (AcademicLevel::orderBy('sort_order')->get() as $level) {
            Subject::firstOrCreate(
                ['name' => 'ISLAMIC STUDIES', 'academic_level_id' => $level->id],
                ['code' => 'ISL-' . $level->sort_order, 'is_active' => true],
            );
        }
    }

    private function seedRelationships(): void
    {
        $pairs = [
            ['admin.juja@alameenacademy.com', 'principal@alameenacademy.com'],
            ['admin.southc@alameenacademy.com', 'principal@alameenacademy.com'],
            ['admin.kitisuru@alameenacademy.com', 'principal@alameenacademy.com'],
            ['coord.eye.juja@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['coord.up.juja@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['coord.js.juja@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['coord.eye.southc@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['coord.up.kitisuru@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['saum@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['dht.juja@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['dht.southc@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['dean.juja@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['dean.southc@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['dean.kitisuru@alameenacademy.com', 'head.teacher@alameenacademy.com'],
            ['fleet@alameenacademy.com', 'ceo@alameenacademy.com'],
            ['ict@alameenacademy.com', 'principal@alameenacademy.com'],
            ['hr@alameenacademy.com', 'principal@alameenacademy.com'],
            ['hr@alameenacademy.com', 'ceo@alameenacademy.com'],
            ['deputy.principal@alameenacademy.com', 'principal@alameenacademy.com'],
            ['head.teacher@alameenacademy.com', 'principal@alameenacademy.com'],
            ['principal@alameenacademy.com', 'ceo@alameenacademy.com'],
        ];

        foreach ($pairs as [$reporter, $manager]) {
            $user = User::where('email', $reporter)->first();
            $managerUser = User::where('email', $manager)->first();

            if (! $user || ! $managerUser) {
                continue;
            }

            \App\Models\ReportingRelationship::firstOrCreate([
                'user_id' => $user->id,
                'manager_user_id' => $managerUser->id,
            ], ['type' => 'direct']);

            if ($user->line_manager_id === null) {
                $user->forceFill(['line_manager_id' => $managerUser->id])->save();
            }
        }
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $principal;

    protected User $headTeacher;

    protected User $ceo;

    protected User $adminJuja;

    protected User $adminSouthC;

    protected User $coordinator;

    protected User $fleet;

    protected User $hr;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        (new \Database\Seeders\RolePermissionSeeder())->run();
        (new \Database\Seeders\ReportingAssignmentsSeeder())->run();
        (new \Database\Seeders\ReportTemplatesSeeder())->run();
        (new \Database\Seeders\SchoolStructureSeeder())->run();
        (new \Database\Seeders\OrganizationUsersSeeder())->run();

        $this->principal = User::where('email', 'principal@alameenacademy.com')->firstOrFail();
        $this->headTeacher = User::where('email', 'head.teacher@alameenacademy.com')->firstOrFail();
        $this->ceo = User::where('email', 'ceo@alameenacademy.com')->firstOrFail();
        $this->adminJuja = User::where('email', 'admin.juja@alameenacademy.com')->firstOrFail();
        $this->adminSouthC = User::where('email', 'admin.southc@alameenacademy.com')->firstOrFail();
        $this->coordinator = User::where('email', 'coord.eye.juja@alameenacademy.com')->firstOrFail();
        $this->fleet = User::where('email', 'fleet@alameenacademy.com')->firstOrFail();
        $this->hr = User::where('email', 'hr@alameenacademy.com')->firstOrFail();
        $this->teacher = User::where('email', 'amina@alameenacademy.com')->firstOrFail();
    }

    /**
     * actingAs with a fresh session so AuthenticateSession's password-hash
     * check doesn't log out the newly switched-in user.
     */
    protected function actingAsRole(User $user): static
    {
        \Illuminate\Support\Facades\Session::flush();

        return $this->actingAs($user);
    }

    private function submitTemplate(User $user, string $templateName): Report
    {
        $template = \App\Models\ReportTemplate::query()
            ->where('name', $templateName)
            ->whereHas('role', fn ($q) => $q->whereHas('users', fn ($uq) => $uq->where('users.id', $user->id)))
            ->with('currentVersion.sections')
            ->firstOrFail();

        $report = app(ReportSubmissionService::class)->createFromTemplate($user, $template);

        // Provide narrative for mandatory manual sections so submission validates.
        foreach ($report->responses()->where('is_mandatory', true)->get() as $response) {
            if (trim((string) $response->content) === '') {
                $response->forceFill(['content' => 'Test narrative content for ' . $response->title])->save();
            }
        }

        return app(ReportSubmissionService::class)->submit($report->refresh(), $user);
    }

    public function test_coordinator_sees_dashboard_and_scoped_mappings(): void
    {
        $this->actingAsRole($this->coordinator)
            ->get('/admin/syllabus-dashboard')
            ->assertOk();

        $this->actingAsRole($this->coordinator)
            ->get('/admin/syllabus-mappings')
            ->assertOk();
    }

    public function test_administrator_report_routes_to_principal_only(): void
    {
        $report = $this->submitTemplate($this->adminJuja, 'Administrator Report');

        $this->assertSame(ReportStatus::Submitted, $report->status);
        $this->assertEqualsCanonicalizing([$this->principal->id], $report->recipients->pluck('id')->all());
    }

    public function test_branch_administrator_cannot_view_other_branches_report(): void
    {
        $report = $this->submitTemplate($this->adminJuja, 'Administrator Report');

        $this->actingAsRole($this->adminSouthC)
            ->get('/admin/organizational-reports/' . $report->id)
            ->assertForbidden();
    }

    public function test_head_teacher_cannot_see_administrator_report(): void
    {
        $report = $this->submitTemplate($this->adminJuja, 'Administrator Report');

        // The Head Teacher is not a recipient of administrator reports; the CEO can see everything.
        $this->actingAsRole($this->headTeacher)
            ->get('/admin/organizational-reports/' . $report->id)
            ->assertForbidden();

        $this->actingAsRole($this->ceo)
            ->get('/admin/organizational-reports/' . $report->id)
            ->assertOk();
    }

    public function test_hr_report_routes_to_principal_and_ceo(): void
    {
        $report = $this->submitTemplate($this->hr, 'HR Officer Report');

        $this->assertSame(ReportStatus::Submitted, $report->status);
        $this->assertEqualsCanonicalizing(
            [$this->principal->id, $this->ceo->id],
            $report->recipients->pluck('id')->all()
        );
    }

    public function test_fleet_report_routes_to_ceo_only(): void
    {
        $report = $this->submitTemplate($this->fleet, 'Fleet Manager Report');

        $this->assertSame(ReportStatus::Submitted, $report->status);
        $this->assertEqualsCanonicalizing([$this->ceo->id], $report->recipients->pluck('id')->all());

        $this->actingAsRole($this->principal)
            ->get('/admin/organizational-reports/' . $report->id)
            ->assertForbidden();
    }

    public function test_teacher_can_submit_report_when_granted_module_access(): void
    {
        // Teachers have no organizational reports module by default; granting
        // the permission explicitly unlocks their own-report workflow.
        $this->teacher->givePermissionTo(['reports.module.view', 'reports.view', 'reports.create', 'reports.submit']);

        $report = $this->submitTemplate($this->teacher, 'Teacher Report');

        $this->assertSame(ReportStatus::Submitted, $report->status);
        $this->assertCount(0, $report->recipients); // no routing rules for teachers by default

        $this->actingAsRole($this->teacher)
            ->get('/admin/organizational-reports/' . $report->id)
            ->assertOk();
    }

    public function test_others_reports_not_visible_to_unrouted_users(): void
    {
        $report = $this->submitTemplate($this->fleet, 'Fleet Manager Report');

        $this->actingAsRole($this->hr)
            ->get('/admin/organizational-reports/' . $report->id)
            ->assertForbidden();
    }

    public function test_principal_compilation_flags_missing_sources(): void
    {
        // Seed a learner population so the auto-integration has data to pull.
        $session = \App\Models\YearSession::orderByDesc('id')->firstOrFail();
        $term = \App\Models\Term::where('year_session_id', $session->id)->orderByDesc('id')->firstOrFail();
        \App\Models\Enrollment::create(['branch' => 'Juja Road', 'year_session_id' => $session->id, 'term_id' => $term->id, 'class_breakdown' => [['class_id' => 1, 'class_name' => 'Grade 1 Blue', 'boys' => 320, 'girls' => 320]], 'comment' => 'Seed data']);
        \App\Models\Enrollment::create(['branch' => 'South C', 'year_session_id' => $session->id, 'term_id' => $term->id, 'class_breakdown' => [['class_id' => 2, 'class_name' => 'Grade 1 Blue', 'boys' => 215, 'girls' => 215]], 'comment' => 'Seed data']);
        \App\Models\Enrollment::create(['branch' => 'Kitisuru', 'year_session_id' => $session->id, 'term_id' => $term->id, 'class_breakdown' => [['class_id' => 3, 'class_name' => 'Grade 1 Blue', 'boys' => 140, 'girls' => 140]], 'comment' => 'Seed data']);

        $adminReport = $this->submitTemplate($this->adminJuja, 'Administrator Report');

        // Include only the submitted source; the other two branches are missing.
        $report = app(\App\Services\ReportCompiler::class)->compileDraft(
            $this->principal,
            now()->startOfMonth(),
            now()->endOfMonth(),
            \App\Services\ReportCompiler::KIND_PRINCIPAL,
            [$adminReport->id],
            true,
        );

        $this->assertTrue($report->is_consolidated);
        $this->assertSame(\App\Services\ReportCompiler::KIND_PRINCIPAL, $report->compilation_kind);
        $this->assertSame($this->principal->id, $report->compiled_by_user_id);

        // Every section response should flag the missing branches.
        $flagged = $report->responses()->where('content', 'like', '%MISSING SUBMISSION%')->count();
        $this->assertGreaterThan(0, $flagged);

        // Population always comes from live enrollment data with a total row.
        $population = $report->responses()->where('title', 'like', '%School Population%')->firstOrFail();
        $this->assertStringContainsString('1350', $population->content); // 640 + 430 + 280
        $this->assertStringContainsString('Juja Road', $population->content);

        $this->assertSame(1, $report->compilation?->sources()->count());
        $this->assertSame('Juja Road', $report->compilation?->sources()->first()?->branch_label);

        // Missing should never be silently treated as zeros. The Principal's
        // tracker expects Administrator x3 + ICT + HR + Head Teacher + Deputy
        // Principal = 7 submissions; only admin.juja's was provided.
        $missingRows = collect(app(\App\Services\ReportCompiler::class)->trackingFor($this->principal, now()->startOfMonth(), now()->endOfMonth()))->where('is_missing', true);
        $this->assertSame(6, $missingRows->count());
        $this->assertContains('South C Administrator', $missingRows->pluck('name')->all());
        $this->assertContains('Kitisuru Administrator', $missingRows->pluck('name')->all());
    }

    public function test_head_teacher_consolidates_coordinator_reports_for_principal(): void
    {
        $eyeReport = $this->submitTemplate($this->coordinator, 'Coordinator Report');
        $upCoordinator = User::where('email', 'coord.up.juja@alameenacademy.com')->firstOrFail();
        $upReport = $this->submitTemplate($upCoordinator, 'Coordinator Report');

        $report = app(\App\Services\ReportCompiler::class)->compileDraft(
            $this->headTeacher,
            now()->startOfMonth(),
            now()->endOfMonth(),
            \App\Services\ReportCompiler::KIND_HEAD_TEACHER,
            [$eyeReport->id, $upReport->id],
            false,
        );

        $this->assertTrue($report->is_consolidated);
        $this->assertSame(\App\Services\ReportCompiler::KIND_HEAD_TEACHER, $report->compilation_kind);

        $syllabus = $report->responses()->where('title', 'Syllabus Mapping')->firstOrFail();
        $this->assertStringContainsString('Juja Road', $syllabus->content);

        // Submitting the consolidation routes it to the Principal.
        $submitted = app(ReportSubmissionService::class)->submit($report->refresh(), $this->headTeacher);
        $this->assertEqualsCanonicalizing([$this->principal->id], $submitted->recipients->pluck('id')->all());

        // The Principal can approve it.
        app(ReportSubmissionService::class)->markUnderReview($submitted->refresh(), $this->principal);
        app(ReportSubmissionService::class)->approve($submitted->refresh(), $this->principal);
        $this->assertSame(ReportStatus::Approved, $submitted->refresh()->status);
    }

    public function test_return_and_resubmit_flow_preserves_history(): void
    {
        $adminReport = $this->submitTemplate($this->adminJuja, 'Administrator Report');

        // Principal returns it for revision.
        app(ReportSubmissionService::class)->returnForRevision($adminReport->refresh(), $this->principal, 'Learner numbers need clarification.');
        $this->assertSame(ReportStatus::Returned, $adminReport->refresh()->status);

        // Admin fixes and resubmits.
        $adminReport->refresh();
        $this->assertTrue($adminReport->isEditableBy($this->adminJuja));
        app(ReportSubmissionService::class)->submit($adminReport, $this->adminJuja);
        $this->assertSame(ReportStatus::Resubmitted, $adminReport->refresh()->status);

        // Review history + revisions are preserved.
        $this->assertGreaterThanOrEqual(2, $adminReport->revisions()->count());
        $this->assertSame('returned', $adminReport->reviews()->where('action', 'returned')->first()?->action);

        app(ReportSubmissionService::class)->approve($adminReport->refresh(), $this->principal);
        $this->assertSame(ReportStatus::Approved, $adminReport->refresh()->status);
    }

    public function test_reports_module_hidden_from_teachers(): void
    {
        $this->actingAsRole($this->teacher)
            ->get('/admin/organizational-reports')
            ->assertForbidden();
    }

    public function test_unauthorized_direct_url_is_blocked(): void
    {
        $this->actingAsRole($this->coordinator)
            ->get('/admin/organization-users')
            ->assertForbidden();
    }
}

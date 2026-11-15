<?php

namespace Tests\Feature;

use App\Models\GoalCategory;
use App\Models\GoalMeasure;
use App\Models\GoalSheet;
use App\Models\GoalSheetCategory;
use App\Models\GoalSheetEntry;
use App\Models\GoalSheetGoal;
use App\Models\User;
use App\Services\GoalSheetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GoalSheetsTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = 'tests/Fixtures/2026 GOAL SHEETS.xlsx';

    private User $admin;

    private User $teacher;

    private User $coordinator;

    private User $otherTeacher;

    protected function setUp(): void
    {
        parent::setUp();

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        (new \Database\Seeders\RolePermissionSeeder())->run();
        (new \Database\Seeders\GoalSheetsSeeder())->run();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('Teacher');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinator');

        $this->otherTeacher = User::factory()->create();
        $this->otherTeacher->assignRole('Teacher');
    }

    public function test_six_workbook_categories_are_seeded(): void
    {
        $this->assertEquals(6, GoalSheetCategory::count());

        foreach (['CBE E.Y.E', 'ICDC  E.Y.E', 'CBE UPPER PRIMARY', 'ICDC  UPPER PRIMARY', 'C.B.EJUNIOR SCHOOL', 'I.C.D.C JUNIOR SCHOOL'] as $name) {
            $this->assertDatabaseHas('goal_sheet_categories', ['name' => $name]);
        }
    }

    public function test_goal_categories_and_measures_are_seeded(): void
    {
        $this->assertEquals(4, GoalCategory::count());
        $this->assertSame(
            ['Academic Performance', 'Reading Skills', 'Writing Skills', 'Learner Discipline'],
            GoalCategory::orderBy('sort_order')->pluck('name')->all()
        );

        // Workbook weights: 0.5 / 0.2 / 0.2 / 0.1 (sum 1.00)
        $weights = GoalCategory::orderBy('sort_order')->get()->map(fn ($c) => (float) $c->measures()->first()->performance_weight)->all();
        $this->assertSame([0.5, 0.2, 0.2, 0.1], $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);

        foreach (GoalCategory::all() as $category) {
            $this->assertGreaterThan(0, $category->measures()->count());
        }
    }

    public function test_role_permission_matrix_seeds_goal_sheet_permissions(): void
    {
        $matrix = [
            'super_admin' => ['goalsheets.view', 'goalsheets.create', 'goalsheets.edit', 'goalsheets.delete', 'goalsheets.acknowledge', 'goalsheets.reports', 'goalsheets.admin'],
            'CEO' => ['goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge'],
            'Principal' => ['goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge'],
            'Deputy Principal' => ['goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge'],
            'Head Teacher' => ['goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge'],
            'Deputy Head Teacher' => ['goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge'],
            'Coordinator' => ['goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge'],
            'Dean of Islamic Studies' => ['goalsheets.view', 'goalsheets.reports', 'goalsheets.acknowledge'],
            'Administrator' => ['goalsheets.view', 'goalsheets.reports', 'goalsheets.admin'],
            'ICT Officer' => ['goalsheets.view'],
            'HR Officer' => ['goalsheets.view'],
            'Fleet Manager' => [],
            'Teacher' => ['goalsheets.view', 'goalsheets.create', 'goalsheets.edit'],
        ];

        foreach ($matrix as $role => $expected) {
            $permissions = \Spatie\Permission\Models\Role::findByName($role, 'web')->permissions->pluck('name')->all();

            foreach ($expected as $permission) {
                $this->assertContains($permission, $permissions, "{$role} is missing {$permission}");
            }

            foreach (['goalsheets.edit', 'goalsheets.create', 'goalsheets.admin', 'goalsheets.reports', 'goalsheets.acknowledge'] as $sensitive) {
                if (! in_array($sensitive, $expected, true)) {
                    $this->assertNotContains($sensitive, $permissions, "{$role} should not have {$sensitive}");
                }
            }
        }
    }

    public function test_import_brings_in_all_worksheets_values_and_relationships(): void
    {
        $this->assertFileExists(self::FIXTURE);

        $import = (new GoalSheetImporter())->importFromPath(self::FIXTURE, '2026 GOAL SHEETS.xlsx', $this->admin, false);

        $summary = $import->summary;

        $this->assertSame('completed', $import->status);
        $this->assertSame(0, $summary['failed'], 'Import reported failures: ' . json_encode($import->errors));
        $this->assertSame(55, $summary['created']);
        $this->assertGreaterThan(200, $summary['goals']);
        $this->assertGreaterThan(300, $summary['entries']);

        // Najma Ramadhan (CBE E.Y.E) linked to staff account? create a user to be sure.
        $najma = User::factory()->create(['name' => 'Najma Ramadhan']);
        $saum = User::factory()->create(['name' => 'Saum Wanjiru']);

        $import2 = (new GoalSheetImporter())->importFromPath(self::FIXTURE, '2026 GOAL SHEETS.xlsx', $this->admin, false);
        $this->assertSame(55, $import2->summary['updated']);

        $sheet = GoalSheet::query()->where('staff_name', 'Najma Ramadhan')->whereHas('category', fn ($q) => $q->where('name', 'CBE E.Y.E'))->first();

        $this->assertNotNull($sheet);
        $this->assertSame(2026, $sheet->goal_year);
        $this->assertSame($najma->id, $sheet->staff_user_id);
        $this->assertSame($saum->id, $sheet->coordinator_id);

        // Percentages normalized: 0.8 target stored as 80, actuals keep raw values.
        $academic = $sheet->goals()->where('goal_number', 1)->with('entries')->first();
        $this->assertEquals(80.0, (float) $academic->goal_for_year);
        $this->assertEqualsWithDelta(80.0, (float) $academic->entries->firstWhere('subject_name', 'English')->term_1, 0.001);
        $this->assertSame('HB', $academic->achievement_type);

        // Weight preserved
        $this->assertEqualsWithDelta(1.0, $sheet->weightTotal(), 0.0001);

        // Values round-trip: Kiswahili ACTUAL 78.84 (MID T2) stays the same
        $kiswahili = $academic->entries->firstWhere('subject_name', 'Kiswahili');
        $this->assertEqualsWithDelta(78.84, (float) $kiswahili->mid_t2, 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $kiswahili->end_t2, 0.001);

        // Calculated average (AVERAGE formula equivalent) matches the mean of entries
        $this->assertEqualsWithDelta(83.33, $academic->averageFor('end_t3'), 0.005);
    }

    public function test_reimport_does_not_duplicate_sheets(): void
    {
        (new GoalSheetImporter())->importFromPath(self::FIXTURE, '2026 GOAL SHEETS.xlsx', $this->admin, false);
        $sheets = GoalSheet::count();
        $goals = GoalSheetGoal::count();
        $entries = GoalSheetEntry::count();

        (new GoalSheetImporter())->importFromPath(self::FIXTURE, '2026 GOAL SHEETS.xlsx', $this->admin, false);

        $this->assertSame($sheets, GoalSheet::count());
        $this->assertSame($goals, GoalSheetGoal::count());
        $this->assertSame($entries, GoalSheetEntry::count());
    }

    public function test_sheet_sheet_submission_acknowledgement_and_locking(): void
    {
        $sheet = $this->makeSheet($this->teacher, $this->coordinator);

        // Owner submits
        $this->actingAs($this->teacher);
        $sheet->submit($this->teacher);

        $this->assertSame(GoalSheet::STATUS_SUBMITTED, $sheet->fresh()->status);
        $this->assertTrue($sheet->fresh()->isSignedBy('owner'));
        $this->assertFalse($sheet->fresh()->isSignedBy('line_manager_1'));
        $this->assertTrue($sheet->fresh()->pendingAcknowledgement());

        // Entries locked for the owner after submission
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $entry = $sheet->goals()->first()->entries()->first();
        $entry->update(['subject_name' => 'Sleepy Science']);
    }

    public function test_acknowledgement_flow_marks_sheet_acknowledged(): void
    {
        $sheet = $this->makeSheet($this->teacher, $this->coordinator);

        $sheet->submit($this->teacher);
        $this->actingAs($this->coordinator);
        $sheet->recordAcknowledgement('line_manager_1', $this->coordinator);

        $this->assertSame(GoalSheet::STATUS_SUBMITTED, $sheet->fresh()->status);

        // LM2 (deputy / head teacher equivalent): unrestricted access signs with null LM2
        $headTeacher = User::factory()->create();
        $headTeacher->assignRole('Head Teacher');
        $this->actingAs($headTeacher);
        $sheet->recordAcknowledgement('line_manager_2', $headTeacher);

        $this->assertSame(GoalSheet::STATUS_ACKNOWLEDGED, $sheet->fresh()->status);
        $this->assertFalse($sheet->fresh()->pendingAcknowledgement());
    }

    public function test_weighted_rating_respects_achievement_type(): void
    {
        $sheet = GoalSheet::create([
            'goal_sheet_category_id' => GoalSheetCategory::first()->id,
            'staff_user_id' => $this->teacher->id,
            'staff_name' => $this->teacher->name,
            'goal_year' => 2026,
            'status' => GoalSheet::STATUS_DRAFT,
        ]);

        $academic = GoalCategory::where('name', 'Academic Performance')->firstOrFail();
        $discipline = GoalCategory::where('name', 'Learner Discipline')->firstOrFail();

        $goalHB = $sheet->goals()->create([
            'position' => 1,
            'goal_category_id' => $academic->id,
            'category_name' => $academic->name,
            'goal_number' => 1,
            'goal_statement' => 'HB goal',
            'goal_for_year' => 80,
            'uom' => '%',
            'achievement_type' => 'HB',
            'performance_weight' => 0.5,
        ]);

        $goalLB = $sheet->goals()->create([
            'position' => 2,
            'goal_category_id' => $discipline->id,
            'category_name' => $discipline->name,
            'goal_number' => 2,
            'goal_statement' => 'LB goal',
            'goal_for_year' => 80,
            'uom' => '%',
            'achievement_type' => 'LB',
            'performance_weight' => 0.2,
        ]);

        // HB: actual 40 vs target 80 -> attainment 50
        $goalHB->entries()->create(['position' => 1, 'term_1' => 80, 'end_t1' => 40]);

        // LB: actual 160 vs target 80 -> attainment 50
        $goalLB->entries()->create(['position' => 1, 'term_2' => 80, 'end_t2' => 160]);

        $sheet = $sheet->fresh();

        // 0.5 * 50 (HB) + 0.2 * 50 (LB) = 35.0
        $this->assertEqualsWithDelta(35.0, $sheet->calculatedWeightedRating(), 0.001);
        $this->assertEqualsWithDelta(0.7, $sheet->ratedWeightTotal(), 0.0001);
    }

    public function test_sign_off_flow_through_the_view_page(): void
    {
        $coordinatorUser = User::factory()->create(['name' => 'Coordinator Signature Person']);
        $coordinatorUser->assignRole('Coordinator');
        $sheet = $this->makeSheet($this->teacher, $coordinatorUser);

        // Staff member opens their sheet and submits it for acknowledgement.
        $this->actingAs($this->teacher);

        \Livewire\Livewire::test(\App\Filament\Resources\GoalSheetResource\Pages\ViewGoalSheet::class, ['record' => $sheet->id])
            ->call('submit');

        $this->assertSame(GoalSheet::STATUS_SUBMITTED, $sheet->fresh()->status);
        $this->assertTrue($sheet->fresh()->isSignedBy('owner'));

        // Coordinator (Line Manager 1) opens the sheet and signs.
        $this->actingAs($coordinatorUser);

        \Livewire\Livewire::test(\App\Filament\Resources\GoalSheetResource\Pages\ViewGoalSheet::class, ['record' => $sheet->fresh()->id])
            ->call('startSignature', 'line_manager_1')
            ->assertSet('signatureName', 'Coordinator Signature Person')
            ->set('signatureName', 'Coordinator Signature')
            ->set('signatureNotes', 'Reviewed and agreed')
            ->call('confirmSignature')
            ->assertHasNoErrors();

        $this->assertTrue($sheet->fresh()->isSignedBy('line_manager_1'));
        $this->assertSame('Coordinator Signature', $sheet->fresh()->acknowledgementFor('line_manager_1')?->signer_name);

        // Line Manager 2 (deputy equivalent; unset on the sheet => senior leadership signs as fallback).
        $headTeacher = User::factory()->create();
        $headTeacher->assignRole('Head Teacher');
        $this->actingAs($headTeacher);

        \Livewire\Livewire::test(\App\Filament\Resources\GoalSheetResource\Pages\ViewGoalSheet::class, ['record' => $sheet->fresh()->id])
            ->call('startSignature', 'line_manager_2')
            ->set('signatureName', 'Deputy Signature')
            ->call('confirmSignature');

        $this->assertSame(GoalSheet::STATUS_ACKNOWLEDGED, $sheet->fresh()->status);
    }

    public function test_view_page_blocks_staff_from_other_sheets_and_visitors(): void
    {
        $sheet = $this->makeSheet($this->teacher, $this->coordinator);

        $this->actingAs($this->otherTeacher);

        // Livewire renders the abort(403) error page instead of throwing.
        $html = \Livewire\Livewire::test(\App\Filament\Resources\GoalSheetResource\Pages\ViewGoalSheet::class, ['record' => $sheet->id])->html();

        $this->assertStringContainsStringIgnoringCase('forbidden', $html);
    }

    public function test_spot_check_available_in_fixture_matches_workbook_values(): void
    {
        (new GoalSheetImporter())->importFromPath(self::FIXTURE, '2026 GOAL SHEETS.xlsx', $this->admin, false);

        // Amina Mohamud (ICDC E.Y.E), cached AVERAGE over mid_t1 = 43.75 in the workbook.
        $sheet = GoalSheet::query()->where('staff_name', 'Amina Mohamud')->whereHas('category', fn ($q) => $q->where('name', 'ICDC  E.Y.E'))->first();
        $academic = $sheet->goals()->where('goal_number', 1)->with('entries')->first();

        $this->assertEqualsWithDelta(43.75, $academic->averageFor('mid_t1'), 0.005);
        $this->assertEqualsWithDelta(98.55, $academic->averageFor('end_t3'), 0.005);

        // Discipline rows that were blank stay blank (no invented data)
        $discipline = $sheet->goals()->where('category_name', 'Learner Discipline')->firstOrFail();
        $this->assertNull($discipline->attainment());
    }

    private function makeSheet(User $staff, ?User $coordinator = null, string $status = GoalSheet::STATUS_DRAFT): GoalSheet
    {
        $category = GoalSheetCategory::query()->first();
        $academic = GoalCategory::where('name', 'Academic Performance')->firstOrFail();

        $sheet = GoalSheet::create([
            'goal_sheet_category_id' => $category->id,
            'staff_user_id' => $staff->id,
            'staff_name' => $staff->name,
            'goal_year' => 2026,
            'coordinator_id' => $coordinator?->id,
            'coordinator_name' => $coordinator?->name,
            'status' => $status,
        ]);

        $goal = $sheet->goals()->create([
            'position' => 1,
            'goal_category_id' => $academic->id,
            'category_name' => $academic->name,
            'goal_number' => 1,
            'goal_statement' => 'Test goal',
            'goal_for_year' => 80,
            'uom' => '%',
            'calculation_method' => 'Test',
            'source_of_data' => 'Test',
            'achievement_type' => 'HB',
            'performance_weight' => 0.5,
        ]);

        $goal->entries()->create([
            'position' => 1,
            'subject_name' => 'Mathematics',
            'class_name' => '4B',
            'term_1' => 80,
            'term_2' => 85,
            'term_3' => 90,
            'end_t1' => 60,
        ]);

        return $sheet;
    }
}

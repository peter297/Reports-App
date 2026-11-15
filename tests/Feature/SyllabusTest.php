<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\CoordinatorAssignment;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\SyllabusCoordinatorReview;
use App\Models\SyllabusLesson;
use App\Models\SyllabusLessonProgress;
use App\Models\SyllabusMapping;
use App\Models\Term;
use App\Models\User;
use App\Models\YearSession;
use App\Services\SyllabusCoverage;
use App\Support\SyllabusStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SyllabusTest extends TestCase
{
    use RefreshDatabase;

    private YearSession $session;

    private Term $term;

    private Classes $grade;

    private Subject $subject;

    private User $admin;

    private User $teacher;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        foreach (['super_admin', 'Teacher', 'Coordinator', 'Deputy Head Teacher', 'Administrator'] as $role) {
            \Spatie\Permission\Models\Role::findOrCreate($role, 'web');
        }

        // Seed the permission catalog and role matrix so checks behave like production.
        (new \Database\Seeders\RolePermissionSeeder())->run();

        $branch = \App\Models\Branch::firstOrCreate(['name' => 'Juja Road'], ['code' => 'JUJA', 'is_active' => true]);
        $level = \App\Models\AcademicLevel::firstOrCreate(['name' => 'Upper Primary'], ['sort_order' => 2, 'is_active' => true]);

        $this->session = YearSession::create(['name' => 'Test Year', 'start_date' => '2026-01-05', 'end_date' => '2026-12-04', 'status' => 'active']);
        $this->term = Term::create(['name' => 'Test Term', 'year_session_id' => $this->session->id, 'start_date' => '2026-01-05', 'end_date' => '2026-03-27', 'status' => 'active']);
        $this->grade = Classes::create(['branch_id' => $branch->id, 'academic_level_id' => $level->id, 'name' => 'Grade 5', 'is_active' => true]);
        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'academic_level_id' => $level->id, 'is_active' => true]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('Teacher');

        $this->coordinator = User::factory()->create();
        $this->coordinator->assignRole('Coordinator');
    }

    private function makeMapping(array $overrides = []): SyllabusMapping
    {
        return SyllabusMapping::create(array_merge([
            'year_session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'class_id' => $this->grade->id,
            'subject_id' => $this->subject->id,
            'total_lessons' => 0,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    private function sampleStructure(?string $numbersExpected = '2026-01-31', ?string $measurementExpected = null): array
    {
        return [
            's1' => [
                'id' => null,
                'name' => 'Numbers',
                'description' => null,
                'expected_completion_date' => $numbersExpected,
                'substrands' => [
                    'a1' => [
                        'id' => null,
                        'name' => 'Whole Numbers',
                        'description' => null,
                        'lessons' => [
                            ['id' => null, 'topic' => 'Reading and writing numbers'],
                            ['id' => null, 'topic' => 'Place value'],
                            ['id' => null, 'topic' => 'Ordering numbers'],
                        ],
                    ],
                    'a2' => [
                        'id' => null,
                        'name' => 'Fractions',
                        'description' => null,
                        'lessons' => [
                            ['id' => null, 'topic' => 'Introduction to fractions'],
                            ['id' => null, 'topic' => 'Equivalent fractions'],
                        ],
                    ],
                ],
            ],
            's2' => [
                'id' => null,
                'name' => 'Measurement',
                'description' => null,
                'expected_completion_date' => $measurementExpected,
                'substrands' => [
                    'a3' => [
                        'id' => null,
                        'name' => 'Length',
                        'description' => null,
                        'lessons' => [
                            ['id' => null, 'topic' => 'Units of measurement'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_mapping_creates_structure_and_numbers_lessons(): void
    {
        $mapping = $this->makeMapping();

        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());

        $mapping->refresh();

        $this->assertSame(6, $mapping->total_lessons);
        $this->assertDatabaseHas('syllabus_lessons', ['syllabus_mapping_id' => $mapping->id, 'lesson_number' => 1, 'topic' => 'Reading and writing numbers']);
        $this->assertDatabaseHas('syllabus_lessons', ['syllabus_mapping_id' => $mapping->id, 'lesson_number' => 6, 'topic' => 'Units of measurement']);
        $strands = $mapping->strands()->get()->keyBy('name');
        $this->assertSame('2026-01-31', $strands['Numbers']->expected_completion_date?->toDateString());
        $this->assertNull($strands['Measurement']->expected_completion_date);
    }

    public function test_renumbering_after_structure_change(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());

        // Remove lesson 1 from state; add a new topic only to the Measurement substrand.
        $structure = $mapping->fresh()->load('strands.substrands.lessons');
        $state = $structure->strands->map(fn ($strand) => [
            'id' => $strand->id,
            'name' => $strand->name,
            'description' => $strand->description,
            'expected_completion_date' => $strand->expected_completion_date?->toDateString(),
            'substrands' => $strand->substrands->map(function ($substrand) {
                $lessons = $substrand->lessons
                    ->reject(fn ($lesson) => $lesson->lesson_number === 1)
                    ->map(fn ($lesson) => ['id' => $lesson->id, 'topic' => $lesson->topic])
                    ->values();

                if ($substrand->name === 'Length') {
                    $lessons = $lessons->push(['id' => null, 'topic' => 'New final topic']);
                }

                return [
                    'id' => $substrand->id,
                    'name' => $substrand->name,
                    'description' => $substrand->description,
                    'lessons' => $lessons->all(),
                ];
            })->values()->all(),
        ])->values()->all();

        app(SyllabusStructure::class)->sync($mapping, $state);

        $lessons = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->orderBy('lesson_number')->pluck('topic')->all();

        $this->assertSame(6, count($lessons));
        $this->assertSame('Place value', $lessons[0]);
        $this->assertSame('New final topic', $lessons[5]);
        $this->assertSame(6, $mapping->fresh()->total_lessons);
    }

    public function test_cannot_remove_lessons_that_have_progress(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());

        SubjectAssignment::create(['subject_id' => $this->subject->id, 'class_id' => $this->grade->id, 'stream_id' => null, 'user_id' => $this->teacher->id, 'assignment_role' => 'teacher', 'branch' => 'Juja Road', 'year_session_id' => $this->session->id]);

        $lesson = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->where('lesson_number', 1)->firstOrFail();
        SyllabusLessonProgress::create(['syllabus_lesson_id' => $lesson->id, 'teacher_id' => $this->teacher->id, 'completion_date' => '2026-02-02']);

        // Structure with lesson 1 removed entirely.
        $structure = $mapping->fresh()->load('strands.substrands.lessons');
        $state = $structure->strands->map(fn ($strand) => [
            'id' => $strand->id,
            'name' => $strand->name,
            'description' => $strand->description,
            'expected_completion_date' => $strand->expected_completion_date?->toDateString(),
            'substrands' => $strand->substrands->map(fn ($substrand) => [
                'id' => $substrand->id,
                'name' => $substrand->name,
                'description' => $substrand->description,
                'lessons' => $substrand->lessons
                    ->reject(fn ($l) => $l->id === $lesson->id)
                    ->map(fn ($l) => ['id' => $l->id, 'topic' => $l->topic])
                    ->values()
                    ->all(),
            ])->values()->all(),
        ])->values()->all();

        $this->expectException(ValidationException::class);
        app(SyllabusStructure::class)->guardAgainstDestructiveChanges($mapping, $state);
    }

    public function test_coverage_follows_sheets_formula(): void
    {
        $mapping = $this->makeMapping(['total_lessons' => 6]);
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        $mapping->refresh();

        $this->assignTeacher($mapping);

        $lessons = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->orderBy('lesson_number')->get();

        SyllabusLessonProgress::create(['syllabus_lesson_id' => $lessons[0]->id, 'teacher_id' => $this->teacher->id, 'completion_date' => '2026-02-02']);
        SyllabusLessonProgress::create(['syllabus_lesson_id' => $lessons[1]->id, 'teacher_id' => $this->teacher->id, 'completion_date' => '2026-02-03']);

        $stats = app(SyllabusCoverage::class)->stats($mapping, $this->teacher->id);

        $this->assertSame(6, $stats['total_lessons']);
        $this->assertSame(2, $stats['completed_lessons']);
        $this->assertSame(4, $stats['pending_lessons']);
        $this->assertSame(2, $stats['highest_consecutive']);
        $this->assertEqualsWithDelta(33.33, $stats['coverage_percent'], 0.01);

        $coverage = app(SyllabusCoverage::class);
        $this->assertEqualsWithDelta(16.67, $coverage->lessonCoverage(1, 6, true), 0.01);
        $this->assertEqualsWithDelta(33.33, $coverage->lessonCoverage(2, 6, true), 0.01);
        $this->assertSame(0.0, $coverage->lessonCoverage(3, 6, false));
    }

    public function test_highest_consecutive_stops_at_first_gap_and_flags_it(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        $mapping->refresh();
        $this->assignTeacher($mapping);

        $lessons = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->orderBy('lesson_number')->get();

        foreach ([0, 1, 3] as $index) { // lessons 1, 2 and 4 completed — lesson 3 missing
            SyllabusLessonProgress::create(['syllabus_lesson_id' => $lessons[$index]->id, 'teacher_id' => $this->teacher->id, 'completion_date' => '2026-02-02']);
        }

        $stats = app(SyllabusCoverage::class)->stats($mapping, $this->teacher->id);

        $this->assertSame(3, $stats['completed_lessons']);
        $this->assertSame(2, $stats['highest_consecutive']);
        $this->assertSame([3], $stats['gaps']);
        $this->assertEqualsWithDelta(33.33, $stats['coverage_percent'], 0.01);
    }

    public function test_lesson_status_rules(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure('2026-01-31', null));
        $mapping->refresh();
        $this->assignTeacher($mapping);

        $lessons = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->orderBy('lesson_number')->get();

        // Lesson 1 completed 2 days after the Numbers strand expected date (2026-01-31).
        SyllabusLessonProgress::create(['syllabus_lesson_id' => $lessons[0]->id, 'teacher_id' => $this->teacher->id, 'completion_date' => '2026-02-02']);
        // Lessons 2-5: Numbers strand expected date passed (2026-01-31) with no completion → OVERDUE.
        // Lesson 6: Measurement strand has no expected date → NOT SCHEDULED.

        $stats = app(SyllabusCoverage::class)->stats($mapping, $this->teacher->id);

        $this->assertSame(1, $stats['completed_lessons']);
        $this->assertSame(4, $stats['overdue_lessons']);
        $this->assertSame(0, $stats['upcoming_lessons']);
        $this->assertSame(1, $stats['not_scheduled_lessons']);
        $this->assertSame(1, $stats['late_lessons']);

        // Late by exactly 2 days, and on-time when completion equals the expected date.
        $coverage = app(SyllabusCoverage::class);
        $this->assertSame(2, $coverage->lateDays(\Carbon\Carbon::parse('2026-01-31'), \Carbon\Carbon::parse('2026-02-02')));
        $this->assertSame(0, $coverage->lateDays(\Carbon\Carbon::parse('2026-02-02'), \Carbon\Carbon::parse('2026-02-02')));
        $this->assertSame(-1, $coverage->lateDays(\Carbon\Carbon::parse('2026-02-03'), \Carbon\Carbon::parse('2026-02-02')));
        $this->assertNull($coverage->lateDays(null, \Carbon\Carbon::parse('2026-02-02')));
    }

    public function test_upcoming_status_when_expected_date_is_in_future(): void
    {
        $future = now()->addDays(10)->toDateString();

        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure($future, $future));
        $mapping->refresh();
        $this->assignTeacher($mapping);

        $lessons = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->orderBy('lesson_number')->get();

        SyllabusLessonProgress::create(['syllabus_lesson_id' => $lessons[0]->id, 'teacher_id' => $this->teacher->id, 'completion_date' => now()->toDateString()]);

        $stats = app(SyllabusCoverage::class)->stats($mapping, $this->teacher->id);

        $this->assertSame(1, $stats['completed_lessons']);
        $this->assertSame(0, $stats['overdue_lessons']);
        $this->assertSame(5, $stats['upcoming_lessons']);
        $this->assertSame(0, $stats['not_scheduled_lessons']);
    }

    public function test_duplicate_mapping_copies_strand_expected_dates(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure('2026-03-15', null));

        $copy = app(SyllabusStructure::class)->duplicate($mapping, [
            'year_session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'class_id' => $this->grade->id,
            'subject_id' => $this->subject->id,
        ], $this->admin->id);

        $strands = $copy->strands()->get()->keyBy('name');
        $this->assertSame('2026-03-15', $strands['Numbers']->expected_completion_date?->toDateString());
        $this->assertNull($strands['Measurement']->expected_completion_date);
    }

    public function test_assigned_teacher_can_save_progress_and_coverage_updates(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        $this->assignTeacher($mapping);

        $lesson = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->orderBy('lesson_number')->firstOrFail();

        \Livewire\Livewire::actingAs($this->teacher)
            ->test(\App\Filament\Resources\SyllabusMappingResource\Pages\SyllabusSheet::class, ['record' => $mapping])
            ->set('progress.'.$lesson->id.'.completion', '2026-02-02')
            ->set('progress.'.$lesson->id.'.comment', 'Covered as planned')
            ->call('saveRow', $lesson->id)
            ->assertOk();

        $this->assertDatabaseHas('syllabus_lesson_progress', [
            'syllabus_lesson_id' => $lesson->id,
            'teacher_id' => $this->teacher->id,
            'teacher_comment' => 'Covered as planned',
        ]);
    }

    public function test_school_session_can_be_created_with_nested_terms_and_weeks(): void
    {
        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Filament\Resources\YearSessionResource\Pages\CreateYearSession::class)
            ->fillForm([
                'name' => '2027',
                'start_date' => '2027-01-04',
                'end_date' => '2027-12-15',
                'status' => 'upcoming',
                'terms' => [
                    [
                        'id' => null,
                        'name' => 'Term 1',
                        'start_date' => '2027-01-04',
                        'end_date' => '2027-03-26',
                        'status' => 'upcoming',
                        'weeks' => [
                            ['id' => null, 'number' => 1, 'name' => null, 'start_date' => '2027-01-04', 'end_date' => '2027-01-08'],
                            ['id' => null, 'number' => 2, 'name' => null, 'start_date' => '2027-01-11', 'end_date' => '2027-01-15'],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $session = YearSession::query()->where('name', '2027')->firstOrFail();
        $this->assertCount(1, $session->terms);
        $this->assertCount(2, $session->terms->first()->weeks);
        $this->assertSame('Week 1', $session->terms->first()->weeks->first()->name);
    }

    public function test_duplicate_week_number_in_a_term_is_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        \App\Filament\Resources\YearSessionResource::validateWeekNumbers([
            ['name' => 'Term 1', 'weeks' => [
                ['number' => 1, 'start_date' => '2027-01-04', 'end_date' => '2027-01-08'],
                ['number' => 1, 'start_date' => '2027-01-11', 'end_date' => '2027-01-15'],
            ]],
        ]);
    }

    public function test_deleting_a_stream_does_not_break_stream_history(): void
    {
        $stream = \App\Models\Stream::create(['class_id' => $this->grade->id, 'name' => 'Blue', 'is_active' => true]);
        $mapping = $this->makeMapping();

        \App\Models\SubjectAssignment::create([
            'year_session_id' => $mapping->year_session_id,
            'subject_id' => $mapping->subject_id,
            'class_id' => $mapping->class_id,
            'stream_id' => $stream->id,
            'user_id' => $this->teacher->id,
            'assignment_role' => 'teacher',
            'branch' => 'Juja Road',
        ]);

        // Stream can be deactivated but a referenced stream should never be silently removed.
        $this->assertTrue(\App\Filament\Resources\ClassesResource\RelationManagers\StreamsRelationManager::streamIsReferenced($stream));
    }

    public function test_teacher_cannot_open_unassigned_mapping(): void
    {
        $mapping = $this->makeMapping();

        $otherSubject = Subject::create(['name' => 'English', 'code' => 'ENG', 'academic_level_id' => 1, 'is_active' => true]);
        $otherMapping = $this->makeMapping(['subject_id' => $otherSubject->id]);

        $this->assignTeacher($mapping);

        $url = route('filament.admin.resources.syllabus-mappings.sheet', ['record' => $otherMapping]);
        $this->actingAs($this->teacher)->get($url)->assertForbidden();

        $url = route('filament.admin.resources.syllabus-mappings.sheet', ['record' => $mapping]);
        $this->actingAs($this->teacher)->get($url)->assertOk();
    }

    public function test_coordinator_review_appends_history(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        SubjectAssignment::create(['subject_id' => $this->subject->id, 'class_id' => $this->grade->id, 'stream_id' => null, 'user_id' => $this->coordinator->id, 'assignment_role' => 'coordinator', 'branch' => 'Juja Road', 'year_session_id' => $this->session->id]);

        $strand = $mapping->strands()->firstOrFail();

        \Livewire\Livewire::actingAs($this->coordinator)
            ->test(\App\Filament\Resources\SyllabusMappingResource\Pages\SyllabusReview::class, ['record' => $mapping])
            ->set('comments.'.$strand->id, 'First pass — coverage progressing.')
            ->call('saveReview', $strand->id)
            ->set('comments.'.$strand->id, 'Follow-up — still needs attention.')
            ->call('saveReview', $strand->id)
            ->assertOk();

        $reviews = SyllabusCoordinatorReview::query()->where('syllabus_strand_id', $strand->id)->orderBy('id')->get();

        $this->assertCount(2, $reviews);
        $this->assertSame('First pass — coverage progressing.', $reviews[0]->comment);
        $this->assertSame('Follow-up — still needs attention.', $reviews[1]->comment);
    }

    public function test_teacher_is_forbidden_from_coordinator_review_page(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        $this->assignTeacher($mapping);

        $url = route('filament.admin.resources.syllabus-mappings.review', ['record' => $mapping]);
        $this->actingAs($this->teacher)->get($url)->assertForbidden();

        SubjectAssignment::create(['subject_id' => $this->subject->id, 'class_id' => $this->grade->id, 'stream_id' => null, 'user_id' => $this->coordinator->id, 'assignment_role' => 'coordinator', 'branch' => 'Juja Road', 'year_session_id' => $this->session->id]);

        $this->actingAs($this->coordinator)->get($url)->assertOk();
    }

    public function test_assessment_links_multiple_lessons(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        $this->assignTeacher($mapping);

        $lessons = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->orderBy('lesson_number')->take(2)->pluck('id');

        \Livewire\Livewire::actingAs($this->teacher)
            ->test(\App\Filament\Resources\SyllabusMappingResource\Pages\SyllabusSheet::class, ['record' => $mapping])
            ->set('assessmentDate', '2026-02-10')
            ->set('assessmentType', 'CAT 1')
            ->set('assessmentDescription', 'Whole Numbers assessment')
            ->set('selectedLessons', $lessons->map(fn ($id) => (string) $id)->all())
            ->call('recordAssessment')
            ->assertOk();

        $assessment = \App\Models\SyllabusAssessment::query()->where('syllabus_mapping_id', $mapping->id)->firstOrFail();

        $this->assertSame('2026-02-10', $assessment->assessment_date->toDateString());
        $this->assertEqualsCanonicalizing($lessons->all(), $assessment->lessons()->pluck('syllabus_lessons.id')->all());
    }

    public function test_duplicate_mapping_copies_structure_but_not_progress(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        $this->assignTeacher($mapping);

        $lesson = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->orderBy('lesson_number')->firstOrFail();
        SyllabusLessonProgress::create(['syllabus_lesson_id' => $lesson->id, 'teacher_id' => $this->teacher->id, 'completion_date' => '2026-02-02']);

        $copy = app(SyllabusStructure::class)->duplicate($mapping, [
            'year_session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'class_id' => $this->grade->id,
            'subject_id' => $this->subject->id,
        ], $this->admin->id);

        $this->assertSame(6, $copy->total_lessons);
        $this->assertSame('draft', $copy->status->value);
        $this->assertSame(0, SyllabusLessonProgress::query()->whereIn('syllabus_lesson_id', $copy->lessons()->pluck('id'))->count());
        $this->assertSame(1, SyllabusLesson::query()->where('syllabus_mapping_id', $copy->id)->where('topic', 'Place value')->count());
    }

    public function test_duplicate_exists_check_prevents_double_active_mapping(): void
    {
        $mapping = $this->makeMapping(['status' => 'active']);

        $candidate = new SyllabusMapping([
            'year_session_id' => $this->session->id,
            'term_id' => $this->term->id,
            'class_id' => $this->grade->id,
            'subject_id' => $this->subject->id,
            'status' => 'draft',
        ]);

        $this->assertTrue($candidate->duplicateExists());
        $this->assertFalse($candidate->duplicateExists($mapping->id));
    }

    public function test_accessible_to_scopes_teacher_visibility(): void
    {
        $mapping = $this->makeMapping();
        $this->assignTeacher($mapping);

        $otherSubject = Subject::create(['name' => 'English', 'code' => 'ENG', 'academic_level_id' => 1, 'is_active' => true]);
        $otherClass = Classes::create(['name' => 'Grade 6', 'branch_id' => 2, 'academic_level_id' => 1]);
        $this->makeMapping(['subject_id' => $otherSubject->id, 'class_id' => $otherClass->id]);

        $visible = SyllabusMapping::query()->accessibleTo($this->teacher)->pluck('id')->all();

        $this->assertSame([$mapping->id], $visible);
    }

    public function test_create_page_creates_mapping_with_structure_and_renumbers(): void
    {
        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Filament\Resources\SyllabusMappingResource\Pages\CreateSyllabusMapping::class)
            ->fillForm([
                'year_session_id' => $this->session->id,
                'term_id' => $this->term->id,
                'branch_id' => \App\Models\Branch::where('name', 'Juja Road')->value('id'),
                'academic_level_id' => \App\Models\AcademicLevel::where('name', 'Upper Primary')->value('id'),
                'class_id' => $this->grade->id,
                'subject_id' => $this->subject->id,
                'status' => 'draft',
                'strands' => [
                    [
                        'id' => null,
                        'name' => 'Numbers',
                        'description' => null,
                        'substrands' => [
                            [
                                'id' => null,
                                'name' => 'Whole Numbers',
                                'description' => null,
                                'lessons' => [
                                    ['id' => null, 'topic' => 'Counting in ones'],
                                    ['id' => null, 'topic' => 'Counting in twos'],
                                    ['id' => null, 'topic' => 'Skip counting'],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $mapping = SyllabusMapping::query()->where('subject_id', $this->subject->id)->firstOrFail();

        $this->assertSame(3, $mapping->total_lessons);
        $this->assertSame('draft', $mapping->status->value);
        $this->assertSame($this->admin->id, $mapping->created_by);
        $this->assertDatabaseHas('syllabus_lessons', ['syllabus_mapping_id' => $mapping->id, 'lesson_number' => 3, 'topic' => 'Skip counting']);
    }

    public function test_create_page_blocks_duplicate_active_mapping(): void
    {
        $this->makeMapping(['status' => 'active']);

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Filament\Resources\SyllabusMappingResource\Pages\CreateSyllabusMapping::class)
            ->fillForm([
                'year_session_id' => $this->session->id,
                'term_id' => $this->term->id,
                'branch_id' => \App\Models\Branch::where('name', 'Juja Road')->value('id'),
                'academic_level_id' => \App\Models\AcademicLevel::where('name', 'Upper Primary')->value('id'),
                'class_id' => $this->grade->id,
                'subject_id' => $this->subject->id,
                'status' => 'draft',
                'strands' => [],
            ])
            ->call('create')
            ->assertNotified();

        $this->assertSame(1, SyllabusMapping::query()->where('subject_id', $this->subject->id)->count());
    }

    public function test_section_coordinator_sees_only_own_branch_and_level(): void
    {
        $juja = \App\Models\Branch::firstOrCreate(['name' => 'Juja Road'], ['code' => 'JUJA', 'is_active' => true]);
        $kitisuru = \App\Models\Branch::firstOrCreate(['name' => 'Kitisuru'], ['code' => 'KITI', 'is_active' => true]);
        $eye = \App\Models\AcademicLevel::firstOrCreate(['name' => 'Early Years Education'], ['sort_order' => 1, 'is_active' => true]);
        $upper = \App\Models\AcademicLevel::firstWhere('name', 'Upper Primary');

        // Subject names overlap across levels; uniqueness now defaults to per-level.
        $eyeLiteracy = Subject::create(['name' => 'Early Literacy', 'code' => 'LIT', 'academic_level_id' => $eye->id, 'is_active' => true]);
        $upMathA = $this->subject;

        $jujaGrade6 = Classes::create(['branch_id' => $juja->id, 'academic_level_id' => $upper->id, 'name' => 'Grade 6', 'is_active' => true]);
        $jujaGrade1 = Classes::create(['branch_id' => $juja->id, 'academic_level_id' => $eye->id, 'name' => 'Grade 1', 'is_active' => true]);
        $kitisuruGrade5 = Classes::create(['branch_id' => $kitisuru->id, 'academic_level_id' => $upper->id, 'name' => 'Grade 5', 'is_active' => true]);

        $jujaUp = $this->makeMapping(['subject_id' => $upMathA->id, 'class_id' => $jujaGrade6->id]);
        $jujaEye = $this->makeMapping(['subject_id' => $eyeLiteracy->id, 'class_id' => $jujaGrade1->id]);
        $kitisuruUp = $this->makeMapping(['subject_id' => $upMathA->id, 'class_id' => $kitisuruGrade5->id]);

        CoordinatorAssignment::create(['user_id' => $this->coordinator->id, 'branch_id' => $juja->id, 'academic_level_id' => $upper->id]);

        $visible = SyllabusMapping::query()->accessibleTo($this->coordinator)->pluck('id')->sort()->values()->all();

        $this->assertSame([$jujaUp->id], $visible);

        CoordinatorAssignment::create(['user_id' => $this->coordinator->id, 'branch_id' => $juja->id, 'academic_level_id' => $eye->id]);

        $visible = SyllabusMapping::query()->accessibleTo($this->coordinator)->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$jujaUp->id, $jujaEye->id])->sort()->values()->all(), $visible);

        $this->assertNotContains($kitisuruUp->id, $visible);
    }

    public function test_deputy_headteacher_sees_all_mappings(): void
    {
        $deputy = User::factory()->create();
        $deputy->forceFill(['branch_id' => 1])->save();
        $deputy->assignRole('Deputy Head Teacher');

        $eye = \App\Models\AcademicLevel::firstOrCreate(['name' => 'Early Years Education'], ['sort_order' => 1, 'is_active' => true]);
        $upper = \App\Models\AcademicLevel::firstWhere('name', 'Upper Primary');
        $junior = \App\Models\AcademicLevel::firstOrCreate(['name' => 'Junior School'], ['sort_order' => 3, 'is_active' => true]);

        $eyeSubject = Subject::create(['name' => 'Early Literacy', 'code' => 'LIT', 'academic_level_id' => $eye->id, 'is_active' => true]);
        $upSubject = $this->subject;
        $jsSubject = Subject::create(['name' => 'Integrated Science', 'code' => 'SCI', 'academic_level_id' => $junior->id, 'is_active' => true]);

        $eyeClass = Classes::create(['branch_id' => 1, 'academic_level_id' => $eye->id, 'name' => 'Grade 1', 'is_active' => true]);
        $upClass = Classes::create(['branch_id' => 1, 'academic_level_id' => $upper->id, 'name' => 'Grade 6', 'is_active' => true]);
        $jsClass = Classes::create(['branch_id' => 1, 'academic_level_id' => $junior->id, 'name' => 'Grade 9', 'is_active' => true]);

        $this->makeMapping(['subject_id' => $eyeSubject->id, 'class_id' => $eyeClass->id]);
        $this->makeMapping(['subject_id' => $upSubject->id, 'class_id' => $upClass->id]);
        $this->makeMapping(['subject_id' => $jsSubject->id, 'class_id' => $jsClass->id]);

        $this->assertCount(3, SyllabusMapping::query()->accessibleTo($deputy)->get());

        $deputyUrl = route('filament.admin.resources.syllabus-mappings.index');
        $this->actingAs($deputy)->get($deputyUrl)->assertOk();
    }

    public function test_subject_name_unique_per_level_not_globally(): void
    {
        $upper = \App\Models\AcademicLevel::firstWhere('name', 'Upper Primary');
        $junior = \App\Models\AcademicLevel::firstOrCreate(['name' => 'Junior School'], ['sort_order' => 3, 'is_active' => true]);

        Subject::create(['name' => 'English', 'code' => 'ENG', 'academic_level_id' => $junior->id, 'is_active' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Subject::create(['name' => 'English', 'code' => 'ENG2', 'academic_level_id' => $junior->id, 'is_active' => true]);
    }

    public function test_teacher_dashboard_is_scoped_to_allocations_without_view_all_controls(): void
    {
        $mapping = $this->makeMapping(['status' => 'active']);
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        $this->assignTeacher($mapping);

        \Livewire\Livewire::actingAs($this->teacher)
            ->test(\App\Filament\Pages\SyllabusDashboard::class)
            ->assertSet('canViewAll', false)
            ->assertOk();

        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Filament\Pages\SyllabusDashboard::class)
            ->assertSet('canViewAll', true)
            ->assertOk();
    }

    public function test_month_quotes_are_super_admin_only(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole('Administrator');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Month quotes are configured inside the Events menu; only the super admin may manage them.
        $this->assertFalse(\App\Filament\Resources\EventResource\Pages\MonthQuotes::canAccess());

        $this->actingAs($finance)->get(route('filament.admin.resources.events.quotes'))->assertForbidden();
        $this->actingAs($superAdmin)->get(route('filament.admin.resources.events.quotes'))->assertOk();
    }

    public function test_subject_requires_academic_level_in_form(): void
    {
        \Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Filament\Resources\SubjectResource\Pages\CreateSubject::class)
            ->fillForm([
                'name' => 'Test Subject',
                'academic_level_id' => \App\Models\AcademicLevel::firstWhere('name', 'Upper Primary')->id,
                'code' => 'TST',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('subjects', ['name' => 'Test Subject', 'code' => 'TST']);
    }

    public function test_dashboard_renders_for_management_and_assigned_teacher(): void
    {
        $mapping = $this->makeMapping(['status' => 'active']);
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());
        $this->assignTeacher($mapping);

        $this->actingAs($this->admin)->get('/admin/syllabus-dashboard')->assertOk();

        $url = route('filament.admin.resources.syllabus-mappings.sheet', ['record' => $mapping]);
        $this->actingAs($this->teacher)->get($url)->assertOk();
    }

    public function test_mapping_create_and_edit_pages_render(): void
    {
        $mapping = $this->makeMapping();
        app(SyllabusStructure::class)->sync($mapping, $this->sampleStructure());

        $this->actingAs($this->admin)
            ->get(route('filament.admin.resources.syllabus-mappings.create'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('filament.admin.resources.syllabus-mappings.edit', ['record' => $mapping]))
            ->assertOk();
    }

    private function assignTeacher(SyllabusMapping $mapping, string $role = 'teacher'): void
    {
        SubjectAssignment::create([
            'year_session_id' => $mapping->year_session_id,
            'subject_id' => $mapping->subject_id,
            'class_id' => $mapping->class_id,
            'stream_id' => null,
            'user_id' => $this->teacher->id,
            'assignment_role' => $role,
            'branch' => $mapping->branch,
        ]);
    }
}

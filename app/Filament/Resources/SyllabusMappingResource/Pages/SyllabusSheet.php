<?php

namespace App\Filament\Resources\SyllabusMappingResource\Pages;

use App\Filament\Resources\SyllabusMappingResource;
use App\Models\SyllabusAssessment;
use App\Models\SyllabusLesson;
use App\Models\SyllabusLessonProgress;
use App\Models\SyllabusMapping;
use App\Services\SyllabusCoverage;
use App\Support\SyllabusActivityLogger;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SyllabusSheet extends Page
{
    protected static string $resource = SyllabusMappingResource::class;

    protected static string $view = 'filament.resources.syllabus-mapping-resource.pages.syllabus-sheet';

    public SyllabusMapping $record;

    public ?int $viewingTeacherId = null;

    /** @var array<int, array{completion: ?string, comment: ?string}> */
    public array $progress = [];

    /** @var array<int, string> lesson_id => assessment dates html-safe string */
    public array $assessmentDatesByLesson = [];

    /** @var array<int, string> */
    public array $lastUpdatedByLesson = [];

    public array $selectedLessons = [];

    public ?string $assessmentDate = null;

    public ?string $assessmentType = null;

    public ?string $assessmentDescription = null;

    public bool $canEditProgress = false;

    public function mount(SyllabusMapping $record): void
    {
        $user = Auth::user();

        abort_unless($user, 403);
        abort_unless(
            $user->hasSyllabusManagementAccess()
                || $user->isAssignedToMapping($record),
            403,
            'You are not authorized to view this syllabus mapping.'
        );

        $this->record = $record->load(['yearSession', 'term', 'class', 'subject']);

        $assignedTeacher = $record->assignedTeacher();
        $isTeacherForMapping = $user->hasRole('Teacher')
            && $user->subjectAssignments()
                ->where('assignment_role', \App\Models\SubjectAssignment::ROLE_TEACHER)
                ->where('subject_id', $record->subject_id)
                ->where('class_id', $record->class_id)
                ->where('year_session_id', $record->year_session_id)
                ->exists();
        $this->viewingTeacherId = $isTeacherForMapping ? $user->id : $assignedTeacher?->id;

        $this->canEditProgress = $user->hasSyllabusManagementAccess()
            || ($user->hasRole('Teacher') && $user->id === $this->viewingTeacherId && $user->isAssignedToMapping($record));

        $this->loadProgress();
    }

    protected function teacherOptions(): array
    {
        return $this->record->assignedTeachers()
            ->mapWithKeys(fn ($teacher) => [$teacher->id => $teacher->name])
            ->all();
    }

    public function updatedViewingTeacherId(): void
    {
        $this->loadProgress();
    }

    protected function loadProgress(): void
    {
        $coverage = app(SyllabusCoverage::class);
        $lessons = $coverage->lessons($this->record, $this->viewingTeacherId);

        $this->progress = [];

        foreach ($lessons as $lesson) {
            $progress = $lesson->progress->first();

            $this->progress[$lesson->id] = [
                'completion' => $progress?->completion_date?->toDateString(),
                'comment' => $progress?->teacher_comment,
            ];
        }

        $this->assessmentDatesByLesson = $this->buildAssessmentDates($lessons);
        $this->lastUpdatedByLesson = $this->buildLastUpdatedMap($lessons);
        $this->selectedLessons = [];
    }

    protected function buildLastUpdatedMap($lessons): array
    {
        $lessonIds = $lessons->pluck('id')->all();

        if ($lessonIds === [] || ! $this->viewingTeacherId) {
            return [];
        }

        $rows = SyllabusLessonProgress::query()
            ->whereIn('syllabus_lesson_id', $lessonIds)
            ->where('teacher_id', $this->viewingTeacherId)
            ->with('updatedBy')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $by = $row->updatedBy?->name ?? '—';
            $map[$row->syllabus_lesson_id] = $row->updated_at?->format('d/m/Y H:i').' by '.$by;
        }

        return $map;
    }

    protected function buildAssessmentDates($lessons): array
    {
        $lessonIds = $lessons->pluck('id')->all();

        if ($lessonIds === []) {
            return [];
        }

        $rows = DB::table('syllabus_assessment_lessons')
            ->join('syllabus_assessments', 'syllabus_assessments.id', '=', 'syllabus_assessment_lessons.syllabus_assessment_id')
            ->whereIn('syllabus_assessment_lessons.syllabus_lesson_id', $lessonIds)
            ->orderBy('syllabus_assessments.assessment_date')
            ->get(['syllabus_assessment_lessons.syllabus_lesson_id as lesson_id', 'syllabus_assessments.assessment_date']);

        $map = [];

        foreach ($rows as $row) {
            $map[$row->lesson_id][] = Carbon::parse($row->assessment_date)->format('d/m/Y');
        }

        return array_map(fn ($dates) => implode(', ', $dates), $map);
    }

    protected function lessons()
    {
        return app(SyllabusCoverage::class)->lessons($this->record, $this->viewingTeacherId);
    }

    public function getStatsProperty(): array
    {
        return app(SyllabusCoverage::class)->stats($this->record, $this->viewingTeacherId);
    }

    public function getLessonGroupsProperty(): array
    {
        $lessons = $this->lessons();
        $groups = [];

        foreach ($lessons as $lesson) {
            $strand = $lesson->substrand->strand;
            $strandId = $strand->id;

            $groups[$strandId]['strand'] = $strand;
            $groups[$strandId]['substrands'][$lesson->substrand->id]['substrand'] = $lesson->substrand;
            $groups[$strandId]['substrands'][$lesson->substrand->id]['lessons'][] = $lesson;
        }

        return $groups;
    }

    public function getAssessmentsProperty()
    {
        return $this->record->assessments()->withCount('lessons')->with('createdBy')->latest('assessment_date')->limit(15)->get();
    }

    public function getStrandCoverageProperty(): array
    {
        return app(SyllabusCoverage::class)
            ->strandStats($this->record, $this->viewingTeacherId)
            ->keyBy(fn ($row) => $row['strand']->id)
            ->all();
    }

    public function saveRow(int $lessonId): void
    {
        abort_unless($this->canEditProgress, 403);

        if (! $this->persistRow($lessonId)) {
            return;
        }

        $this->loadProgress();
        Notification::make()->title('Lesson saved.')->success()->send();
    }

    protected function persistRow(int $lessonId): bool
    {
        $data = $this->progress[$lessonId] ?? null;

        if (! $data) {
            return false;
        }

        $completion = $data['completion'] ?: null;

        if ($completion && Carbon::parse($completion)->isFuture()) {
            Notification::make()->title('Completion date for lesson '.$lessonId.' cannot be in the future.')->danger()->send();

            return false;
        }

        $lesson = SyllabusLesson::query()
            ->where('syllabus_mapping_id', $this->record->id)
            ->whereKey($lessonId)
            ->firstOrFail();

        $progress = SyllabusLessonProgress::firstOrNew([
            'syllabus_lesson_id' => $lesson->id,
            'teacher_id' => $this->viewingTeacherId,
        ]);

        $old = $progress->exists ? $progress->only(['completion_date', 'teacher_comment']) : null;

        $progress->fill([
            'completion_date' => $completion,
            'teacher_comment' => filled($data['comment'] ?? null) ? trim((string) $data['comment']) : null,
            'updated_by' => Auth::id(),
        ])->save();

        $strandExpected = $lesson->substrand->strand->expected_completion_date;

        SyllabusActivityLogger::record('syllabus_lesson_progress', $progress->id, $progress->wasRecentlyCreated ? 'created' : 'updated', $old, [
            'completion_date' => $completion,
            'teacher_comment' => $progress->teacher_comment,
            'lesson_id' => $lesson->id,
            'teacher_id' => $this->viewingTeacherId,
            'strand_expected_completion_date' => $strandExpected?->toDateString(),
        ]);

        return true;
    }

    public function saveAll(): void
    {
        abort_unless($this->canEditProgress, 403);

        $failures = 0;

        foreach (array_keys($this->progress) as $lessonId) {
            if (! $this->persistRow((int) $lessonId)) {
                $failures++;
            }
        }

        $this->loadProgress();

        if ($failures > 0) {
            Notification::make()->title($failures.' row(s) could not be saved — see the notifications above.')->warning()->send();

            return;
        }

        Notification::make()->title('All changes saved.')->success()->send();
    }

    public function selectAllLessons(): void
    {
        $this->selectedLessons = SyllabusLesson::query()
            ->where('syllabus_mapping_id', $this->record->id)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedLessons = [];
    }

    public function recordAssessment(): void
    {
        abort_unless($this->canEditProgress, 403);
        $this->validate([
            'assessmentDate' => ['required', 'date', 'before_or_equal:today'],
            'assessmentType' => ['nullable', 'string', 'max:255'],
            'assessmentDescription' => ['nullable', 'string', 'max:500'],
            'selectedLessons' => ['required', 'array', 'min:1'],
        ]);

        $lessonIds = SyllabusLesson::query()
            ->where('syllabus_mapping_id', $this->record->id)
            ->whereIn('id', $this->selectedLessons)
            ->pluck('id');

        $assessment = SyllabusAssessment::create([
            'syllabus_mapping_id' => $this->record->id,
            'assessment_date' => $this->assessmentDate,
            'assessment_type' => $this->assessmentType,
            'description' => $this->assessmentDescription,
            'created_by' => Auth::id(),
        ]);

        $assessment->lessons()->sync($lessonIds);

        SyllabusActivityLogger::record('syllabus_assessment', $assessment->id, 'created', null, [
            'assessment_date' => $this->assessmentDate,
            'assessment_type' => $this->assessmentType,
            'lessons' => $lessonIds->all(),
        ]);

        $this->reset(['assessmentDate', 'assessmentType', 'assessmentDescription', 'selectedLessons']);
        $this->loadProgress();

        Notification::make()->title('Assessment recorded for '.$lessonIds->count().' lesson(s).')->success()->send();
    }

    public function getHeading(): string
    {
        return 'Syllabus Mapping Sheet — '.$this->record->subject->name;
    }

    public function getSubheading(): ?string
    {
        return sprintf(
            '%s · %s · %s · Campus: %s',
            $this->record->grade_stream,
            $this->record->term->name,
            $this->record->yearSession->name,
            $this->record->branch ?? 'School-wide',
        );
    }

    public function getBreadcrumb(): string
    {
        return 'Progress sheet';
    }

    public function getBreadcrumbs(): array
    {
        return [
            SyllabusMappingResource::getUrl('index') => 'Syllabus Mappings',
            '#' => $this->getBreadcrumb(),
        ];
    }
}

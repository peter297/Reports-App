<?php

namespace App\Filament\Resources\SyllabusMappingResource\Pages;

use App\Filament\Resources\SyllabusMappingResource;
use App\Models\SyllabusCoordinatorReview;
use App\Models\SyllabusMapping;
use App\Services\SyllabusCoverage;
use App\Support\SyllabusActivityLogger;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SyllabusReview extends Page
{
    protected static string $resource = SyllabusMappingResource::class;

    protected static string $view = 'filament.resources.syllabus-mapping-resource.pages.syllabus-review';

    public SyllabusMapping $record;

    public ?int $viewingTeacherId = null;

    /** @var array<int, string> */
    public array $comments = [];

    public function mount(SyllabusMapping $record): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        abort_unless(
            $user->hasSyllabusViewAllAccess()
                || $user->coordinatesSyllabusMapping($record)
                || $user->subjectAssignments()
                    ->where('assignment_role', \App\Models\SubjectAssignment::ROLE_COORDINATOR)
                    ->where('subject_id', $record->subject_id)
                    ->where('class_id', $record->class_id)
                    ->where('year_session_id', $record->year_session_id)
                    ->exists(),
            403,
            'You may only review subjects you coordinate.'
        );

        $this->record = $record->load(['yearSession', 'term', 'class', 'subject']);
        $this->viewingTeacherId = $record->assignedTeacher()?->id;

        $this->loadCommentDrafts();
    }

    public function teacherOptions(): array
    {
        return $this->record->assignedTeachers()
            ->mapWithKeys(fn ($teacher) => [$teacher->id => $teacher->name])
            ->all();
    }

    public function updatedViewingTeacherId(): void
    {
        // Coverage recomputes in the view via computed properties.
    }

    protected function loadCommentDrafts(): void
    {
        $this->comments = [];

        foreach ($this->record->strands as $strand) {
            $latest = $strand->reviews()->where('coordinator_id', Auth::id())->latest('reviewed_at')->first();
            $this->comments[$strand->id] = $latest?->comment ?? '';
        }
    }

    public function getStrandRowsProperty(): array
    {
        $stats = app(SyllabusCoverage::class)->strandStats($this->record, $this->viewingTeacherId);
        $mapping = $this->record;

        return $stats->map(function (array $row) use ($mapping): array {
            $strand = $row['strand'];
            $lessonIds = $strand->substrands->flatMap->lessons->pluck('id')->all();

            $assessments = \App\Models\SyllabusAssessment::query()
                ->where('syllabus_mapping_id', $mapping->id)
                ->whereHas('lessons', fn ($query) => $query->whereIn('syllabus_lessons.id', $lessonIds))
                ->orderByDesc('assessment_date')
                ->get();

            $reviews = $strand->reviews()->with('coordinator')->latest('reviewed_at')->limit(20)->get();

            return $row + [
                'assessments' => $assessments,
                'reviews' => $reviews,
                'latest_comment' => $reviews->first()?->comment,
                'latest_comment_by' => $reviews->first()?->coordinator?->name,
                'latest_comment_at' => $reviews->first()?->reviewed_at,
            ];
        })->all();
    }

    public function getStatsProperty(): array
    {
        return app(SyllabusCoverage::class)->stats($this->record, $this->viewingTeacherId);
    }

    public function getTitle(): string
    {
        return 'Coordinator Review';
    }

    public function getHeading(): string
    {
        return 'Coordinator Review — Strand Breakdown';
    }

    public function getBreadcrumbs(): array
    {
        return [
            SyllabusMappingResource::getUrl('index') => 'Syllabus Mappings',
            '#' => 'Coordinator review',
        ];
    }

    public function saveReview(int $strandId): void
    {
        $this->validate([
            'comments.'.$strandId => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $strand = \App\Models\SyllabusStrand::query()
            ->whereKey($strandId)
            ->where('syllabus_mapping_id', $this->record->id)
            ->firstOrFail();

        if (! $this->canReviewMapping()) {
            abort(403);
        }

        $comment = trim($this->comments[$strandId]);

        $review = SyllabusCoordinatorReview::create([
            'syllabus_strand_id' => $strand->id,
            'coordinator_id' => Auth::id(),
            'comment' => $comment,
            'reviewed_at' => now(),
        ]);

        SyllabusActivityLogger::record('syllabus_strand', $strand->id, 'coordinator_comment', null, [
            'comment' => $comment,
            'review_id' => $review->id,
            'coordinator_id' => Auth::id(),
        ]);

        Notification::make()->title('Coordinator comment saved for strand: '.$strand->name)->success()->send();
    }

    public function canReviewMapping(): bool
    {
        $user = Auth::user();

        if ($user->hasSyllabusViewAllAccess()) {
            return true;
        }

        return $user->coordinatesSyllabusMapping($this->record)
            || $user->subjectAssignments()
                ->where('assignment_role', \App\Models\SubjectAssignment::ROLE_COORDINATOR)
                ->where('subject_id', $this->record->subject_id)
                ->where('class_id', $this->record->class_id)
                ->where('year_session_id', $this->record->year_session_id)
                ->exists();
    }
}

<?php

namespace App\Filament\Resources\GoalSheetResource\Pages;

use App\Filament\Resources\GoalSheetResource;
use App\Models\GoalSheet;
use App\Models\GoalSheetAcknowledgement;
use App\Models\GoalSheetEntry;
use App\Models\GoalSheetGoal;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ViewGoalSheet extends Page
{
    protected static string $resource = GoalSheetResource::class;

    protected static string $view = 'filament.resources.goal-sheet-resource.pages.view-goal-sheet';

    // Note: Livewire passes single-class typed model properties by resolved instance,
    // which would be coerced to a JSON string here. The union type makes it pass through
    // as the raw route parameter, matching how Filament's own record pages type this.
    public GoalSheet | int | string | null $record = null;

    public ?string $signatureParty = null;

    public string $signatureName = '';

    public string $signatureNotes = '';

    public function mount(int | string $record): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        $this->record = GoalSheet::query()
            ->with(['category', 'staff', 'coordinator', 'lineManager2', 'acknowledgements.signer', 'goals.entries'])
            ->findOrFail($record);

        // Backend gate: view permission AND object scope.
        abort_unless($user->can('goalsheets.view') && $this->record->isAccessibleBy($user), 403);
    }

    public function getTitle(): string
    {
        return $this->record->staff_name . ' — Goal Sheet ' . $this->record->goal_year;
    }

    public function getBreadcrumbs(): array
    {
        return ['Goal Sheets', $this->record->category?->name ?? 'Goal Sheet', $this->record->staff_name];
    }

    /** Whether the current user may submit the sheet for review. */
    public function canSubmit(): bool
    {
        $user = Auth::user();

        return $this->record->status === GoalSheet::STATUS_DRAFT
            && $user?->can('goalsheets.edit')
            && ($user->isSuperAdmin()
                || (int) $this->record->staff_user_id === (int) $user->id
                || $user->hasUnrestrictedAccess());
    }

    /** Which party (if any) the current user may sign right now. */
    public function signableParty(): ?string
    {
        $user = Auth::user();

        if (! $user || $this->record->status === GoalSheet::STATUS_DRAFT) {
            return null;
        }

        $party = $this->record->acknowledgementPartyFor($user);

        if (! $party || $this->record->isSignedBy($party)) {
            return null;
        }

        if ($party === GoalSheetAcknowledgement::PARTY_OWNER) {
            return $party;
        }

        return $user->can('goalsheets.acknowledge') ? $party : null;
    }

    public function startSignature(string $party): void
    {
        $user = Auth::user();

        $allowed = $this->record->acknowledgementPartyFor($user) === $party
            && ! $this->record->isSignedBy($party)
            && $this->record->status !== GoalSheet::STATUS_DRAFT;

        $isOwner = $party === GoalSheetAcknowledgement::PARTY_OWNER;

        abort_unless($allowed && ($isOwner || $user->can('goalsheets.acknowledge')), 403);

        $this->signatureParty = $party;
        $this->signatureName = $user->name;
        $this->signatureNotes = '';
    }

    public function cancelSignature(): void
    {
        $this->signatureParty = null;
        $this->signatureName = '';
        $this->signatureNotes = '';
    }

    public function confirmSignature(): void
    {
        $user = Auth::user();
        $party = $this->signatureParty;

        $this->validate([
            'signatureName' => ['required', 'string', 'min:2', 'max:255'],
            'signatureNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $allowed = $party && $this->record->acknowledgementPartyFor($user) === $party
            && ! $this->record->isSignedBy($party)
            && $this->record->status !== GoalSheet::STATUS_DRAFT;

        $isOwner = $party === GoalSheetAcknowledgement::PARTY_OWNER;

        abort_unless($allowed && ($isOwner || $user->can('goalsheets.acknowledge')), 403);

        // Persist the typed signature exactly as signed.
        $acknowledgement = $this->record->recordAcknowledgement($party, $user, null, trim($this->signatureNotes) ?: null);

        $acknowledgement->forceFill(['signer_name' => trim($this->signatureName)])->save();

        $this->record = $this->record->fresh(['category', 'staff', 'coordinator', 'lineManager2', 'acknowledgements.signer', 'goals.entries']);

        $this->cancelSignature();

        Notification::make()
            ->title('Acknowledgement recorded')
            ->body('Signed as ' . GoalSheetAcknowledgement::PARTIES[$party] . ' on ' . now()->format('d M Y, H:i'))
            ->success()
            ->send();
    }

    public function submit(): void
    {
        $user = Auth::user();

        abort_unless($this->canSubmit(), 403);

        $this->record->submit($user);

        $this->record = $this->record->fresh(['category', 'staff', 'coordinator', 'lineManager2', 'acknowledgements.signer', 'goals.entries']);

        Notification::make()
            ->title('Goal sheet submitted')
            ->body('The sheet is now with the line managers for acknowledgement.')
            ->success()
            ->send();
    }

    /** Super Admin can re-open a submitted/acknowledged sheet back to draft. */
    public function reopen(): void
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403);

        $this->record->forceFill(['status' => GoalSheet::STATUS_DRAFT])->saveQuietly();

        $this->record = $this->record->fresh(['category', 'staff', 'coordinator', 'lineManager2', 'acknowledgements.signer', 'goals.entries']);

        Notification::make()->title('Goal sheet re-opened for editing')->success()->send();
    }

    /** Per-term average attainment across all goals (HB/LB aware). */
    public function termAttainment(): array
    {
        $out = ['term_1' => null, 'term_2' => null, 'term_3' => null];

        foreach ($out as $term => $_) {
            $checkpoint = match ($term) {
                'term_1' => 'end_t1',
                'term_2' => 'end_t2',
                'term_3' => 'end_t3',
            };

            $ratios = [];

            foreach ($this->record->goals as $goal) {
                foreach ($goal->entries as $entry) {
                    $actual = $entry->{$checkpoint};
                    $target = $entry->{$term};

                    if ($actual === null || $target === null || (float) $target <= 0.0) {
                        continue;
                    }

                    $actual = (float) $actual;
                    $target = (float) $target;

                    $ratios[] = $goal->isLowerBetter()
                        ? ($actual > 0 ? $target / $actual * 100 : null)
                        : $actual / $target * 100;
                }
            }

            $ratios = array_filter($ratios, fn ($ratio) => $ratio !== null);
            $out[$term] = $ratios === [] ? null : round(array_sum($ratios) / count($ratios), 1);
        }

        return $out;
    }

    /** AVERAGE() of every numeric entry column across the entries of a goal. */
    public function columnAverages(GoalSheetGoal $goal): array
    {
        $averages = [];

        foreach (['term_1', 'term_2', 'term_3', ...GoalSheetEntry::CHECKPOINTS] as $column) {
            $averages[$column] = $goal->averageFor($column);
        }

        return $averages;
    }
}

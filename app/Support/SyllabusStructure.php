<?php

namespace App\Support;

use App\Models\SyllabusLesson;
use App\Models\SyllabusMapping;
use App\Services\SyllabusCoverage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SyllabusStructure
{
    public function __construct(
        private readonly SyllabusCoverage $coverage,
    ) {}

    /**
     * Ensure the submitted structure does not delete lessons that already have progress.
     *
     * @param  array<int, array<string, mixed>>  $strandsState
     */
    public function guardAgainstDestructiveChanges(SyllabusMapping $mapping, array $strandsState): void
    {
        $incomingIds = $this->lessonIds($strandsState);

        $atRisk = SyllabusLesson::query()
            ->where('syllabus_mapping_id', $mapping->id)
            ->whereHas('progress')
            ->when($incomingIds !== [], fn ($query) => $query->whereNotIn('id', $incomingIds))
            ->pluck('topic', 'id');

        if ($atRisk->isEmpty()) {
            return;
        }

        $topics = $atRisk->values()->take(10)->implode(', ');

        throw ValidationException::withMessages([
            'strands' => "Cannot update the structure: the following lessons already have recorded progress and cannot be removed: {$topics}.",
        ]);
    }

    /**
     * Synchronise the mapping's strands / substrands / lessons with the given repeater state,
     * preserving lesson identity, then renumber lessons and refresh total_lessons.
     *
     * @param  array<int|string, array<string, mixed>>  $strandsState
     */
    public function sync(SyllabusMapping $mapping, array $strandsState): void
    {
        DB::transaction(function () use ($mapping, $strandsState): void {
            $existingLessons = SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->get()->keyBy('id');
            $keptLessonIds = [];
            $keptSubstrandIds = [];
            $keptStrandIds = [];

            foreach (array_values($strandsState) as $strandIndex => $strandData) {
                $strand = isset($strandData['id']) && $strandData['id']
                    ? $mapping->strands()->whereKey($strandData['id'])->first()
                    : null;

                if (! $strand) {
                    $strand = $mapping->strands()->make();
                }

                $strand->fill([
                    'name' => trim((string) ($strandData['name'] ?? '')),
                    'description' => $strandData['description'] ?? null,
                    'expected_completion_date' => filled($strandData['expected_completion_date'] ?? null) ? $strandData['expected_completion_date'] : null,
                    'sort_order' => $strandIndex + 1,
                ]);
                $strand->save();
                $keptStrandIds[] = $strand->id;

                foreach (array_values($strandData['substrands'] ?? []) as $substrandIndex => $substrandData) {
                    $substrand = isset($substrandData['id']) && $substrandData['id']
                        ? $strand->substrands()->whereKey($substrandData['id'])->first()
                        : null;

                    if (! $substrand) {
                        $substrand = $strand->substrands()->make();
                    }

                    $substrand->fill([
                        'name' => trim((string) ($substrandData['name'] ?? '')),
                        'description' => $substrandData['description'] ?? null,
                        'sort_order' => $substrandIndex + 1,
                    ]);
                    $substrand->save();
                    $keptSubstrandIds[] = $substrand->id;

                    foreach (array_values($substrandData['lessons'] ?? []) as $lessonIndex => $lessonData) {
                        $lessonId = $lessonData['id'] ?? null;
                        $lesson = $lessonId ? $existingLessons->get((int) $lessonId) : null;

                        if (! $lesson) {
                            $lesson = new SyllabusLesson;
                        }

                        $lesson->fill([
                            'syllabus_mapping_id' => $mapping->id,
                            'syllabus_substrand_id' => $substrand->id,
                            'topic' => trim((string) ($lessonData['topic'] ?? '')),
                            'sort_order' => $lessonIndex + 1,
                            'lesson_number' => $lesson->exists ? $lesson->lesson_number : 0,
                        ]);
                        $lesson->save();
                        $keptLessonIds[] = $lesson->id;
                    }
                }
            }

            SyllabusLesson::query()
                ->where('syllabus_mapping_id', $mapping->id)
                ->whereNotIn('id', $keptLessonIds ?: [0])
                ->delete();

            $mapping->strands()->whereNotIn('id', $keptStrandIds ?: [0])->delete();
        });

        $this->coverage->renumber($mapping->fresh());
    }

    /**
     * Deep-copy a mapping's structure into a new mapping (status draft, no progress copied).
     *
     * @param  array{year_session_id: int, term_id: int, class_id: int, subject_id: int, stream_id: int|null}  $target
     */
    public function duplicate(SyllabusMapping $source, array $target, int $createdBy): SyllabusMapping
    {
        return DB::transaction(function () use ($source, $target, $createdBy): SyllabusMapping {
            $mapping = SyllabusMapping::create([
                'year_session_id' => $target['year_session_id'],
                'term_id' => $target['term_id'],
                'class_id' => $target['class_id'],
                'subject_id' => $target['subject_id'],
                'stream_id' => filled($target['stream_id'] ?? null) ? (int) $target['stream_id'] : null,
                'total_lessons' => 0,
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);

            $source->loadMissing('strands.substrands.lessons');

            foreach ($source->strands as $strand) {
                $newStrand = $mapping->strands()->create([
                    'name' => $strand->name,
                    'description' => $strand->description,
                    'expected_completion_date' => $strand->expected_completion_date?->toDateString(),
                    'sort_order' => $strand->sort_order,
                ]);

                foreach ($strand->substrands as $substrand) {
                    $newSubstrand = $newStrand->substrands()->create([
                        'name' => $substrand->name,
                        'description' => $substrand->description,
                        'sort_order' => $substrand->sort_order,
                    ]);

                    foreach ($substrand->lessons as $lesson) {
                        SyllabusLesson::create([
                            'syllabus_mapping_id' => $mapping->id,
                            'syllabus_substrand_id' => $newSubstrand->id,
                            'lesson_number' => $lesson->lesson_number,
                            'topic' => $lesson->topic,
                            'sort_order' => $lesson->sort_order,
                        ]);
                    }
                }
            }

            $this->coverage->renumber($mapping->fresh());

            return $mapping->fresh();
        });
    }

    /**
     * Parse an uploaded syllabus file into mergeable repeater state.
     *
     * Supports both the simple 'strand, substrand, topic' CSV and the school export
     * (LESSON NO / Out of / STRAND / SUB-STRAND / LESSON TOPICS), for .csv and .xlsx,
     * including sparse (merged-cell) strand / sub-strand cells which are filled forward.
     *
     * @param  array<string, array<string, mixed>>  $currentState
     * @return array{state: array<string, array<string, mixed>>, imported_lessons: int, strands: int, substrands: int, declared_out_of: int|null}
     */
    public function stateFromFile(string $absolutePath, array $currentState = []): array
    {
        $rows = $this->readRows($absolutePath);
        $result = ['state' => $currentState, 'imported_lessons' => 0, 'strands' => 0, 'substrands' => 0, 'declared_out_of' => null];

        if ($rows === []) {
            return $result;
        }

        [$headerIndex, $cols] = $this->locateHeader($rows);

        $items = [];
        $strand = '';
        $substrand = '';
        $declaredOutOf = null;

        foreach ($rows as $i => $row) {
            if ($headerIndex !== null && $i <= $headerIndex) {
                continue;
            }

            $row = $this->normalizeRow($row);

            if (! $this->rowHasContent($row) || $this->rowLooksLikeHeader($row)) {
                continue;
            }

            $cell = fn (int $index): string => isset($row[$index]) && $row[$index] !== null ? trim((string) $row[$index]) : '';

            $strandCell = $cell($cols['strand']);
            $substrandCell = $cell($cols['substrand']);
            $outOf = (int) preg_replace('/\D/', '', $cell($cols['out_of']));

            if ($outOf > 0) {
                $declaredOutOf = $outOf;
            }

            if ($strandCell !== '') {
                $strand = $strandCell;
            }
            if ($substrandCell !== '') {
                $substrand = $substrandCell;
            }

            $topic = $cell($cols['topic']);

            if ($topic === '' || $strand === '') {
                continue;
            }

            $items[] = [
                'strand' => $strand,
                'substrand' => $substrand === '' ? 'General' : $substrand,
                'topic' => $topic,
            ];
        }

        if ($items === []) {
            return $result;
        }

        $state = $this->mergeRepeaterState($currentState, $this->csvToRepeaterState($items));
        $strands = count(array_unique(array_column($items, 'strand')));
        $substrands = count(array_unique(array_map(fn ($item) => $item['strand'].'||'.$item['substrand'], $items)));

        return [
            'state' => $state,
            'imported_lessons' => count($items),
            'strands' => $strands,
            'substrands' => $substrands,
            'declared_out_of' => $declaredOutOf,
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readRows(string $absolutePath): array
    {
        if (preg_match('/\.xlsx$/i', $absolutePath) === 1) {
            return $this->readXlsxRows($absolutePath);
        }

        return $this->readCsvRows($absolutePath);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readCsvRows(string $absolutePath): array
    {
        $handle = @fopen($absolutePath, 'r');

        if ($handle === false) {
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Normalise a cell value to a scalar safe for state processing.
     * Dates become Y-m-d strings; other non-scalars are stringified safely.
     */
    private function cellToScalar(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, 'getValue')) {
            return $this->cellToScalar($value->getValue());
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => (string) $this->cellToScalar($item), $value));
        }

        return '';
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readXlsxRows(string $absolutePath): array
    {
        $reader = new \OpenSpout\Reader\XLSX\Reader;
        $reader->open($absolutePath);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $values = array_map(fn ($value) => $this->cellToScalar($value), $row->toArray());
                $rows[] = $values;
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    /**
     * Locate the header row and map the columns we care about.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{0: ?int, 1: array{strand: int, substrand: int, topic: int, out_of: int}}
     */
    private function locateHeader(array $rows): array
    {
        $best = null;

        foreach (array_slice($rows, 0, 10, preserve_keys: true) as $index => $row) {
            foreach ($row as $cellIndex => $cell) {
                $text = mb_strtolower(trim((string) $cell));

                if (str_contains($text, 'topic')) {
                    $best = [$index, $this->mapHeaderColumns($row)];
                    break 2;
                }
            }
        }

        if ($best !== null) {
            return $best;
        }

        // No header row found — detect the layout from the first data row.
        $dataRow = null;

        foreach ($rows as $row) {
            $row = $this->normalizeRow($row);

            if ($this->rowHasContent($row)) {
                $dataRow = $row;
                break;
            }
        }

        // School export: numeric LESSON NO (col 1) and numeric Out of (col 2).
        if ($dataRow !== null && is_numeric(trim((string) ($dataRow[1] ?? ''))) && is_numeric(trim((string) ($dataRow[2] ?? '')))) {
            return [null, ['strand' => 3, 'substrand' => 4, 'topic' => 5, 'out_of' => 2]];
        }

        return [null, ['strand' => 0, 'substrand' => 1, 'topic' => 2, 'out_of' => 2]];
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array{strand: int, substrand: int, topic: int, out_of: int}
     */
    private function mapHeaderColumns(array $row): array
    {
        $cols = ['strand' => null, 'substrand' => null, 'topic' => null, 'out_of' => null];

        foreach ($row as $index => $cell) {
            $text = mb_strtolower(trim((string) $cell));

            if ($text === '') {
                continue;
            }

            if ($cols['topic'] === null && str_contains($text, 'topic')) {
                $cols['topic'] = $index;

                continue;
            }

            if ($cols['substrand'] === null && preg_match('/sub\s*[-_ ]?\s*strand/i', $text) === 1) {
                $cols['substrand'] = $index;

                continue;
            }

            if ($cols['strand'] === null && str_contains($text, 'strand')) {
                $cols['strand'] = $index;

                continue;
            }

            if ($cols['out_of'] === null && preg_match('/out\s*of/i', $text) === 1) {
                $cols['out_of'] = $index;
            }
        }

        if ($cols['topic'] === null) {
            $cols['topic'] = 2;
        }

        if ($cols['substrand'] === null) {
            $cols['substrand'] = $cols['strand'] === 3 ? 4 : 1;
        }

        if ($cols['strand'] === null) {
            $cols['strand'] = $cols['topic'] === 5 ? 3 : 0;
        }

        if ($cols['out_of'] === null) {
            $cols['out_of'] = $cols['strand'] === 3 ? 2 : 2;
        }

        return $cols;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, string>
     */
    private function normalizeRow(array $row): array
    {
        return array_map(function ($value): string {
            $text = (string) ($value ?? '');
            $text = str_replace(["\xc2\xa0", "\u{200b}"], ' ', $text);

            return trim($text);
        }, $row);
    }

    /**
     * @param  array<int, string>  $row
     */
    private function rowHasContent(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function rowLooksLikeHeader(array $row): bool
    {
        $text = mb_strtolower(implode(' ', $row));

        return str_contains($text, 'topic') && (str_contains($text, 'strand') || str_contains($text, 'sub-strand'));
    }

    /**
     * Build repeater state from parsed CSV rows (strand, substrand, topic).
     *
     * @param  array<int, array{strand: string, substrand: string, topic: string}>  $rows
     * @return array<string, array<string, mixed>>
     */
    public function csvToRepeaterState(array $rows): array
    {
        $state = [];

        foreach ($rows as $row) {
            $strandName = trim($row['strand'] ?? '');
            $substrandName = trim($row['substrand'] ?? '');
            $topic = trim($row['topic'] ?? '');

            if ($strandName === '' || $topic === '') {
                continue;
            }

            $substrandName = $substrandName === '' ? 'General' : $substrandName;

            $strandKey = $this->findKeyByName($state, 'name', $strandName);

            if ($strandKey === null) {
                $strandKey = (string) Str::uuid();
                $state[$strandKey] = [
                    'id' => null,
                    'name' => $strandName,
                    'description' => null,
                    'expected_completion_date' => null,
                    'substrands' => [],
                ];
            }

            $substrandKey = $this->findKeyByName($state[$strandKey]['substrands'], 'name', $substrandName);

            if ($substrandKey === null) {
                $substrandKey = (string) Str::uuid();
                $state[$strandKey]['substrands'][$substrandKey] = [
                    'id' => null,
                    'name' => $substrandName,
                    'description' => null,
                    'lessons' => [],
                ];
            }

            $state[$strandKey]['substrands'][$substrandKey]['lessons'][] = [
                'id' => null,
                'topic' => $topic,
            ];
        }

        return $state;
    }

    /**
     * Append ad-hoc lessons (one topic per line) under the given strand/substrand, creating them if missing.
     *
     * @param  array<string, array<string, mixed>>  $state
     * @return array<string, array<string, mixed>>
     */
    public function appendLessons(array $state, string $strandName, string $substrandName, array $topics): array
    {
        $rows = array_map(fn (string $topic) => [
            'strand' => $strandName,
            'substrand' => $substrandName,
            'topic' => $topic,
        ], $topics);

        return $this->mergeRepeaterState($state, $this->csvToRepeaterState($rows));
    }

    /**
     * Merge two repeater state structures, combining substrands/lessons with matching names.
     *
     * @param  array<string, array<string, mixed>>  $base
     * @param  array<string, array<string, mixed>>  $extra
     * @return array<string, array<string, mixed>>
     */
    public function mergeRepeaterState(array $base, array $extra): array
    {
        foreach ($extra as $strandData) {
            $baseKey = $this->findKeyByName($base, 'name', $strandData['name']);

            if ($baseKey === null) {
                $base[(string) Str::uuid()] = $strandData;

                continue;
            }

            foreach ($strandData['substrands'] as $substrandData) {
                $substrandKey = $this->findKeyByName($base[$baseKey]['substrands'], 'name', $substrandData['name']);

                if ($substrandKey === null) {
                    $base[$baseKey]['substrands'][(string) Str::uuid()] = $substrandData;

                    continue;
                }

                foreach ($substrandData['lessons'] as $lessonData) {
                    $base[$baseKey]['substrands'][$substrandKey]['lessons'][] = $lessonData;
                }
            }
        }

        return $base;
    }

    /**
     * @return array<int, int>
     */
    private function lessonIds(array $strandsState): array
    {
        $ids = [];

        foreach ($strandsState as $strandData) {
            foreach ($strandData['substrands'] ?? [] as $substrandData) {
                foreach ($substrandData['lessons'] ?? [] as $lessonData) {
                    if (! empty($lessonData['id'])) {
                        $ids[] = (int) $lessonData['id'];
                    }
                }
            }
        }

        return $ids;
    }

    private function findKeyByName(array $items, string $field, string $name): ?string
    {
        foreach ($items as $key => $item) {
            if (strcasecmp(trim((string) ($item[$field] ?? '')), trim($name)) === 0) {
                return (string) $key;
            }
        }

        return null;
    }
}

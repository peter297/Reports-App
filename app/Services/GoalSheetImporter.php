<?php

namespace App\Services;

use App\Models\GoalCategory;
use App\Models\GoalSheet;
use App\Models\GoalSheetCategory;
use App\Models\GoalSheetGoal;
use App\Models\GoalSheetImport;
use App\Models\User;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

/**
 * Imports the "GOAL SHEETS" workbook: six worksheets, each carrying any number
 * of individual staff goal sheets. Goal sheets are upserted on
 * (workbook category, staff name, goal year) so re-importing the same file
 * never duplicates a sheet — existing sheets are updated in place.
 *
 * Percentages: the workbook stores targets as fractions (0.8, 0.45, …) and
 * recorded actuals as raw percentages (84.49, 92, …). Everything is normalized
 * to a consistent 0–100 percent on import (values ≤ 1 are treated as fractions).
 * Performance weights are fractions and are kept as-is.
 */
class GoalSheetImporter
{
    /** Worksheet/category match tolerance: normalize both names before comparing. */
    protected function normalizeWorksheet(string $name): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
    }

    /** Staff-name match tolerance: lowercase, strip punctuation, collapse spaces. */
    protected function normalizePersonName(?string $name): string
    {
        $name = strtolower(trim((string) $name));
        $name = preg_replace('/[^a-z0-9 ]/', ' ', $name) ?? '';

        return preg_replace('/\s+/', ' ', trim($name)) ?? '';
    }

    /** Fraction (<= 1) -> percent; otherwise already a percent. Null/blank stays null. */
    protected function percent(mixed $value): ?float
    {
        $value = $this->number($value);

        if ($value === null) {
            return null;
        }

        return $value <= 1.0 ? round($value * 100, 2) : round($value, 2);
    }

    protected function number(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '=')) {
            return null;
        }

        $value = str_replace([',', '%'], '', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    protected function text(mixed $value): ?string
    {
        $value = str_replace("\u{00A0}", ' ', (string) $value);
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' || str_starts_with($value, '=') ? null : $value;
    }

    public function importFromPath(string $path, string $filename, User $importer, bool $dryRun = false): GoalSheetImport
    {
        $errors = [];
        $totals = ['created' => 0, 'updated' => 0, 'failed' => 0, 'goals' => 0, 'entries' => 0, 'warnings' => 0];
        $sheets = [];

        $userIndex = $this->buildUserIndex();
        $categories = GoalSheetCategory::query()->get();

        $reader = new Reader();
        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            $worksheet = $sheet->getName();
            $category = $this->matchCategory($worksheet, $categories);

            if (! $category) {
                $errors[] = ['worksheet' => $worksheet, 'row' => 0, 'message' => "Worksheet not recognized as a known goal sheet category; skipped."];
                $totals['warnings']++;
                continue;
            }

            $rows = [];

            foreach ($sheet->getRowIterator() as $i => $row) {
                $rows[] = ['row' => $i + 1, 'cells' => $row->toArray()];
            }

            foreach ($this->splitBlocks($rows) as $block) {
                try {
                    $result = $this->importBlock($block, $category, $userIndex, $dryRun, $errors, $totals);
                    $sheets[] = $result;
                    $totals[$result['status'] === 'created' ? 'created' : 'updated']++;
                    $totals['goals'] += $result['goals'];
                    $totals['entries'] += $result['entries'];
                } catch (Throwable $e) {
                    $totals['failed']++;
                    $errors[] = ['worksheet' => $worksheet, 'row' => $block['start_row'], 'message' => $e->getMessage()];
                }
            }
        }

        $reader->close();

        $summary = array_merge($totals, [
            'worksheets' => count($sheets),
            'sheets' => $sheets,
            'dry_run' => $dryRun,
        ]);

        return GoalSheetImport::create([
            'filename' => $filename,
            'uploaded_by' => $importer->id,
            'status' => $dryRun ? 'dry_run' : 'completed',
            'summary' => $summary,
            'errors' => $errors,
        ]);
    }

    /** @return array<string, User> normalized name => user */
    protected function buildUserIndex(): array
    {
        $index = [];

        foreach (User::query()->get() as $user) {
            $key = $this->normalizePersonName($user->name);

            if ($key !== '' && ! isset($index[$key])) {
                $index[$key] = $user;
            }
        }

        return $index;
    }

    protected function matchCategory(string $worksheet, \Illuminate\Support\Collection $categories): ?GoalSheetCategory
    {
        $needle = $this->normalizeWorksheet($worksheet);

        $best = $categories->first(fn (GoalSheetCategory $c) => $this->normalizeWorksheet($c->name) === $needle);

        if ($best) {
            return $best;
        }

        // Fallback: contains-based match tolerant of double spaces and punctuation.
        return $categories->first(fn (GoalSheetCategory $c) => str_contains($needle, $this->normalizeWorksheet($c->name))
            || str_contains($this->normalizeWorksheet($c->name), $needle));
    }

    /**
     * Split worksheet rows into staff goal-sheet blocks. A block starts at the
     * row carrying the "GOAL SHEET" title.
     */
    protected function splitBlocks(array $rows): array
    {
        $blocks = [];
        $current = null;

        foreach ($rows as $row) {
            $titleIndex = null;

            foreach ($row['cells'] as $cell) {
                $text = $this->text($cell);

                if ($text !== null && preg_match('/(TEACHER\s+GOAL\s+SHEET|GOAL\s*SHEET[\s\-–:]*20\d{2})/i', $text)) {
                    $titleIndex = true;
                    break;
                }
            }

            if ($titleIndex !== null) {
                if ($current) {
                    $blocks[] = $current;
                }

                $title = $this->text(collect($row['cells'])->first(fn ($cell) => $this->text($cell) && preg_match('/(TEACHER\s+GOAL\s+SHEET|GOAL\s*SHEET[\s\-–:]*20\d{2})/i', (string) $cell)));

                $current = ['start_row' => $row['row'], 'title' => $title, 'rows' => []];
                continue;
            }

            if ($current !== null) {
                $current['rows'][] = $row;
            }
        }

        if ($current) {
            $blocks[] = $current;
        }

        return $blocks;
    }

    protected function importBlock(array $block, GoalSheetCategory $category, array $userIndex, bool $dryRun, array &$errors, array &$totals): array
    {
        $warnings = [];
        $rows = $block['rows'];

        // --- Staff header line -------------------------------------------------
        $nameRow = null;

        foreach ($rows as $row) {
            foreach ($row['cells'] as $cell) {
                if ($this->text($cell) && preg_match('/teacher.?s\s*name/i', (string) $cell)) {
                    $nameRow = $row;
                    break 2;
                }
            }
        }

        // Staff sheet whose name cell was left blank still carries Job-Title /
        // Department cells — treat that row as the staff anchor instead of failing.
        $anchorRow = $nameRow;

        if ($anchorRow === null) {
            foreach ($rows as $row) {
                foreach ($row['cells'] as $cell) {
                    $text = $this->text($cell);

                    if ($text !== null && preg_match('/(job[\s\-]?title\s*:|department\s*:|coordinator\s*:)/i', $text)) {
                        $anchorRow = $row;
                        break 2;
                    }
                }
            }
        }

        if ($anchorRow === null) {
            throw new \RuntimeException('No staff header row found in this block.');
        }

        $joined = implode(' | ', array_map(fn ($cell) => (string) $cell, $anchorRow['cells']));

        $staffName = $this->extractField($joined, '/teacher.?s\s*name\s*:?\s*([^|]+)/i');
        $jobTitle = $this->extractField($joined, '/job[\s\-]?title\s*:?\s*([^|]+)/i');
        $department = $this->extractField($joined, '/department\s*:?\s*([^|]+)/i');
        $coordinatorName = $this->extractField($joined, '/coordinator\s*:?\s*([^|]+)/i');

        $staffNameWarnings = [];

        if ($staffName === null) {
            $staffName = 'Unnamed staff member (row ' . $block['start_row'] . ')';
            $staffNameWarnings[] = 'Staff member name is blank in the workbook; imported as a placeholder name — please rename the sheet after linking the staff account.';
        }

        $year = $this->extractYear((string) $block['title']) ?? (int) now()->format('Y');

        if ($staffNameWarnings !== []) {
            $warnings = array_merge($warnings, $staffNameWarnings);
        }

        // --- Column map from the header row -----------------------------------
        // The header row is the one carrying No + Category. The Goal Statement
        // header sometimes lives on a merged banner row above the No/Category
        // row, so missing statement/goal columns are backfilled relative to the
        // category column (No | Category | Statement | Goal).
        $columnMap = null;
        $headerRowNumber = null;

        foreach ($rows as $row) {
            $map = $this->matchColumnMap($row['cells']);

            if ($map !== null) {
                if (! isset($map['statement'])) {
                    $map['statement'] = $map['category'] + 1;
                }

                if (! isset($map['goal'])) {
                    $map['goal'] = $map['category'] + 2;
                }

                $columnMap = $map;
                $headerRowNumber = $row['row'];
                break;
            }
        }

        if ($columnMap === null) {
            throw new \RuntimeException('No column header row found (expected No / Category / Goal Statement…).');
        }

        // --- Goal rows ----------------------------------------------------------
        $goalBlocks = [];
        $current = null;
        $acknowledgement = ['owner' => null, 'line_manager_1' => null, 'line_manager_2' => null];
        $acknowledgementSeen = false;

        foreach ($rows as $row) {
            if ($row['row'] <= ($headerRowNumber ?? 0)) {
                continue;
            }

            if ($this->rowContains($row['cells'], 'acknowledgement')) {
                $acknowledgementSeen = true;
                continue;
            }

            if ($acknowledgementSeen || $this->signOffLabel($row['cells'])) {
                $this->parseSignOffRow($row['cells'], $acknowledgement);
                continue;
            }

            if ($this->rowIsFormulaRow($row['cells']) || $this->rowIsAllEmpty($row['cells'])) {
                continue;
            }

            $no = $this->cell($row['cells'], $columnMap, 'no');
            $categoryCell = $this->cell($row['cells'], $columnMap, 'category');
            $statement = $this->text($this->cell($row['cells'], $columnMap, 'statement'));
            $weight = $this->number($this->cell($row['cells'], $columnMap, 'weight'));

            $subjectCell = $this->text($this->cell($row['cells'], $columnMap, 'subject'));
            $classCell = $this->text($this->cell($row['cells'], $columnMap, 'class'));

            if ($subjectCell !== null && strtoupper(trim($subjectCell)) === 'AVERAGE') {
                $subjectCell = null;
            }

            if ($classCell !== null && strtoupper(trim($classCell)) === 'AVERAGE') {
                $classCell = null;
            }
            $termCells = [
                'term_1' => $this->percent($this->cell($row['cells'], $columnMap, 'term_1')),
                'term_2' => $this->percent($this->cell($row['cells'], $columnMap, 'term_2')),
                'term_3' => $this->percent($this->cell($row['cells'], $columnMap, 'term_3')),
                'op_t1' => $this->percent($this->cell($row['cells'], $columnMap, 'op_t1')),
                'mid_t1' => $this->percent($this->cell($row['cells'], $columnMap, 'mid_t1')),
                'end_t1' => $this->percent($this->cell($row['cells'], $columnMap, 'end_t1')),
                'mid_t2' => $this->percent($this->cell($row['cells'], $columnMap, 'mid_t2')),
                'end_t2' => $this->percent($this->cell($row['cells'], $columnMap, 'end_t2')),
                'mid_t3' => $this->percent($this->cell($row['cells'], $columnMap, 'mid_t3')),
                'end_t3' => $this->percent($this->cell($row['cells'], $columnMap, 'end_t3')),
            ];

            $hasTermData = count(array_filter($termCells, fn ($v) => $v !== null)) > 0;

            // Performance-weight check row (workbook's SUM row): no category, no statement.
            if ($statement === null && $categoryCell === null && $weight !== null) {
                continue;
            }

            // Goal header rows carry number+category; entry rows are the bare subject lines.
            if ($this->number($no) !== null && $categoryCell !== null) {
                if ($current !== null) {
                    $goalBlocks[] = $current;
                }

                $current = [
                    'row' => $row['row'],
                    'number' => (int) $this->number($no),
                    'category_name' => $this->text($categoryCell) ?? 'Uncategorized',
                    'statement' => $statement,
                    'goal_for_year' => $this->percent($this->cell($row['cells'], $columnMap, 'goal')),
                    'uom' => $this->text($this->cell($row['cells'], $columnMap, 'uom')) ?? '%',
                    'calculation_method' => $this->text($this->cell($row['cells'], $columnMap, 'calc')),
                    'source_of_data' => $this->text($this->cell($row['cells'], $columnMap, 'source')),
                    'achievement_type' => $this->normalizeAchv($this->cell($row['cells'], $columnMap, 'achv')),
                    'performance_weight' => $weight,
                    'entries' => [],
                ];

                // An academic-performance goal row may itself carry the first subject line.
                if ($subjectCell !== null || $classCell !== null || $hasTermData) {
                    $current['entries'][] = $this->buildEntry($subjectCell, $classCell, $termCells, count($current['entries']) + 1);
                }

                continue;
            }

            // Continuation subject/class row of the current goal.
            if ($subjectCell !== null || $classCell !== null || $hasTermData) {
                if ($current === null) {
                    $warnings[] = "Row {$row['row']}: entry data before the first goal row; skipped.";
                    continue;
                }

                // Some goal rows carry data only in the checkpoint columns.
                if ($this->number($no) !== null && $categoryCell === null) {
                    // A stray numeric marker without category; treat as entry line.
                }

                $current['entries'][] = $this->buildEntry($subjectCell, $classCell, $termCells, count($current['entries']) + 1);
                continue;
            }

            // Anything else (stray notes, dashes) is ignored.
        }

        if ($current !== null) {
            $goalBlocks[] = $current;
        }

        if ($goalBlocks === []) {
            throw new \RuntimeException('No goals found in this block.');
        }

        // Sanity checks (warnings, never hard failures).
        $weightTotal = array_sum(array_map(fn ($g) => (float) ($g['performance_weight'] ?? 0), $goalBlocks));

        if (abs($weightTotal - 1.0) > 0.001) {
            $warnings[] = "Performance weights total {$weightTotal} (expected 1.00).";
        }

        foreach ($goalBlocks as $index => &$goal) {
            foreach ($goal['entries'] as $entryIndex => $entry) {
                foreach (['term_1', 'term_2', 'term_3', 'op_t1', 'mid_t1', 'end_t1', 'mid_t2', 'end_t2', 'mid_t3', 'end_t3'] as $metric) {
                    $v = $entry[$metric];

                    if ($v !== null && ((float) $v < 0 || (float) $v > 100)) {
                        $warnings[] = "Goal {$goal['number']} entry {$entryIndex}: {$metric} value {$v} is outside 0–100.";
                    }
                }
            }
        }
        unset($goal);

        // --- Persist -------------------------------------------------------------
        $staffUser = $userIndex[$this->normalizePersonName($staffName)] ?? null;
        $coordinatorUser = $coordinatorName ? ($userIndex[$this->normalizePersonName($coordinatorName)] ?? null) : null;

        if ($staffUser === null) {
            $warnings[] = "Staff member '{$staffName}' not matched to a system account; sheet imported unlinked.";
        }

        if ($coordinatorName && $coordinatorUser === null) {
            $warnings[] = "Coordinator '{$coordinatorName}' not matched to a system account.";
        }

        $lm2Name = $acknowledgement['line_manager_2'];
        $lm2User = $lm2Name ? ($userIndex[$this->normalizePersonName($lm2Name)] ?? null) : null;

        if ($lm2Name && $lm2User === null) {
            $warnings[] = "Line Manager 2 '{$lm2Name}' not matched to a system account.";
        }

        $existingBefore = $this->findExistingSheet($category, $year, $staffName) !== null;

        if (! $dryRun) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($category, $year, $staffName, $staffUser, $jobTitle, $department, $coordinatorUser, $coordinatorName, $lm2User, $lm2Name, $goalBlocks) {
                $sheet = $this->findExistingSheet($category, $year, $staffName);

                \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($sheet, $category, $year, $staffName, $staffUser, $jobTitle, $department, $coordinatorUser, $coordinatorName, $lm2User, $lm2Name, $goalBlocks) {
                    if ($sheet === null) {
                        $sheet = new GoalSheet();
                    }

                    $sheet->fill([
                        'goal_sheet_category_id' => $category->id,
                        'goal_year' => $year,
                        'staff_name' => $staffName,
                        'staff_user_id' => $staffUser?->id,
                        'job_title' => $jobTitle,
                        'department' => $department,
                        'coordinator_id' => $coordinatorUser?->id,
                        'coordinator_name' => $coordinatorName,
                        'line_manager_2_id' => $lm2User?->id,
                        'line_manager_2_name' => $lm2Name,
                        'status' => $sheet->exists ? $sheet->status : GoalSheet::STATUS_DRAFT,
                    ]);
                    $sheet->save();

                    // Replace goals and entries wholesale (workbook is the source of truth).
                    $sheet->goals()->delete();

                    $goalCategories = GoalCategory::query()->get()->keyBy(fn ($c) => mb_strtolower($c->name));

                    foreach ($goalBlocks as $position => $goalData) {
                        $categoryModel = $goalCategories->get(mb_strtolower((string) $goalData['category_name']));

                        $goal = $sheet->goals()->create([
                            'position' => $position + 1,
                            'goal_category_id' => $categoryModel?->id,
                            'category_name' => $goalData['category_name'],
                            'goal_number' => $goalData['number'],
                            'goal_statement' => $goalData['statement'],
                            'goal_for_year' => $goalData['goal_for_year'],
                            'uom' => $goalData['uom'],
                            'calculation_method' => $goalData['calculation_method'],
                            'source_of_data' => $goalData['source_of_data'],
                            'achievement_type' => $goalData['achievement_type'],
                            'performance_weight' => $goalData['performance_weight'],
                        ]);

                        foreach ($goalData['entries'] as $entryIndex => $entry) {
                            $goal->entries()->create(array_merge($entry, ['position' => $entryIndex + 1]));
                        }
                    }
                });
            });
        }

        foreach ($warnings as $warning) {
            $errors[] = ['worksheet' => $category->name, 'row' => $block['start_row'], 'message' => $warning, 'warning' => true];
            $totals['warnings']++;
        }

        return [
            'worksheet' => $category->name,
            'staff' => $staffName,
            'staff_linked' => (bool) $staffUser,
            'status' => $existingBefore ? 'updated' : 'created',
            'goals' => count($goalBlocks),
            'entries' => array_sum(array_map(fn ($g) => count($g['entries']), $goalBlocks)),
            'warnings' => $warnings,
        ];
    }

    protected function findExistingSheet(GoalSheetCategory $category, int $year, string $staffName): ?GoalSheet
    {
        $needle = $this->normalizePersonName($staffName);

        return GoalSheet::query()
            ->where('goal_sheet_category_id', $category->id)
            ->where('goal_year', $year)
            ->get()
            ->first(fn (GoalSheet $sheet) => $this->normalizePersonName($sheet->staff_name) === $needle);
    }

    protected function extractField(string $joined, string $pattern): ?string
    {
        if (preg_match($pattern, $joined, $m)) {
            $value = trim(preg_replace('/\s+/', ' ', $m[1]) ?? '');

            return $value === '' ? null : $value;
        }

        return null;
    }

    protected function extractYear(string $title): ?int
    {
        return preg_match('/(20\d{2})/', $title, $m) ? (int) $m[1] : null;
    }

    /**
     * Map the header row's normalized labels to column indexes.
     *
     * @return array<string, int>|null
     */
    protected function matchColumnMap(array $cells): ?array
    {
        $map = [];

        $aliases = [
            'no' => 'no',
            'category' => 'category',
            'goalstatementperformancemeasures' => 'statement',
            'goalforyear' => 'goal',
            'goalforyear2024' => 'goal',
            'goalforyear2026' => 'goal',
            'uom' => 'uom',
            'calculationmethod' => 'calc',
            'sourceofdata' => 'source',
            'achvtypehblb' => 'achv',
            'perfweight' => 'weight',
            'subjects' => 'subject',
            'subject' => 'subject',
            'class' => 'class',
            'term1' => 'term_1',
            'term2' => 'term_2',
            'term3' => 'term_3',
            'opt1' => 'op_t1',
            'midt1' => 'mid_t1',
            'endt1' => 'end_t1',
            'midt2' => 'mid_t2',
            'endt2' => 'end_t2',
            'midt3' => 'mid_t3',
            'endt3' => 'end_t3',
            'weightedrating' => 'weighted',
        ];

        foreach ($cells as $index => $cell) {
            $token = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $cell) ?? '');

            if ($token !== '' && isset($aliases[$token]) && ! isset($map[$aliases[$token]])) {
                $map[$aliases[$token]] = $index;
            }
        }

        // A valid goal-sheet header must at least identify No + Category.
        return isset($map['no'], $map['category']) ? $map : null;
    }

    protected function cell(array $cells, array $columnMap, string $key): mixed
    {
        if (! isset($columnMap[$key])) {
            return null;
        }

        return $cells[$columnMap[$key]] ?? null;
    }

    protected function rowContains(array $cells, string $needle): bool
    {
        $needle = strtolower($needle);

        foreach ($cells as $cell) {
            $text = strtolower((string) $cell);

            if ($text !== '' && str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function rowIsFormulaRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            $text = trim((string) $cell);

            if ($text !== '' && str_starts_with($text, '=')) {
                return true;
            }
        }

        return false;
    }

    protected function rowIsAllEmpty(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /** Sign-off labels used in the workbook's acknowledgement block. */
    protected function signOffLabel(array $cells): bool
    {
        $joined = strtolower(implode(' ', array_map(fn ($c) => (string) $c, $cells)));

        return str_contains($joined, 'goal sheet owner')
            || str_contains($joined, 'line manager 1')
            || str_contains($joined, 'line manager 2');
    }

    /**
     * Extract the printed names from the acknowledgement block rows.
     * Labels may be followed (next cells) by the name, e.g.
     * "Line Manager 1 (Coordinator Name) || Mohamed Keya || … || Signature: || … || Date:"
     */
    protected function parseSignOffRow(array $cells, array &$acknowledgement): void
    {
        $label = null;
        $labelIndex = null;

        foreach ($cells as $index => $cell) {
            $text = strtolower(trim((string) $cell));

            if (str_contains($text, 'goal sheet owner')) {
                $label = 'owner';
                $labelIndex = $index;
                break;
            }

            if (str_contains($text, 'line manager 1')) {
                $label = 'line_manager_1';
                $labelIndex = $index;
                break;
            }

            if (str_contains($text, 'line manager 2')) {
                $label = 'line_manager_2';
                $labelIndex = $index;
                break;
            }
        }

        if ($label === null) {
            return;
        }

        foreach ($cells as $index => $cell) {
            if ($index <= $labelIndex) {
                continue;
            }

            $text = $this->text($cell);

            if ($text === null) {
                continue;
            }

            $lower = strtolower($text);

            if (str_starts_with($lower, 'signature') || str_starts_with($lower, 'date') || $lower === 'signature:' || $lower === 'date:') {
                continue;
            }

            $acknowledgement[$label] = $text;

            return;
        }
    }

    protected function buildEntry(?string $subject, ?string $class, array $termCells, int $position): array
    {
        // The workbook uses a literal "AVERAGE" label in the subject cell of its
        // average rows; it is not a subject and is dropped here.
        if ($subject !== null && strtoupper(trim($subject)) === 'AVERAGE') {
            $subject = null;
        }

        if ($class !== null && strtoupper(trim($class)) === 'AVERAGE') {
            $class = null;
        }

        return array_merge([
            'position' => $position,
            'subject_name' => $subject,
            'class_name' => $class,
        ], $termCells);
    }

    protected function normalizeAchv(mixed $value): string
    {
        $text = strtoupper(trim((string) $value));

        return $text === 'LB' ? 'LB' : 'HB';
    }
}

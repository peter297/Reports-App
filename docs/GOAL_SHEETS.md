# Goal Sheets & Performance Tracking

Implements the 2026 GOAL SHEETS workbook (CBE / ICDC × E.Y.E / Upper Primary / Junior School)
as a first-class module inside the existing reporting system. One goal sheet per staff member per
year; goals, subject/class rows, termly targets, monthly checkpoints, weights and the three-party
acknowledgement block are all captured.

## What lives where

| Piece | Location |
|---|---|
| Data model (migrations) | `database/migrations/2026_11_15_*` |
| Models | `app/Models/GoalSheet*.php`, `GoalCategory.php`, `GoalMeasure.php` |
| Workbook importer | `app/Services/GoalSheetImporter.php` |
| Staff goal sheet list/create/edit/view | `app/Filament/Resources/GoalSheetResource*` |
| Consolidated dashboard | `app/Filament/Pages/GoalSheetDashboard.php` |
| Import page (upload → dry run → import) | `app/Filament/Pages/ImportGoalSheets.php` |
| Categories & measures configuration | `GoalSheetCategoryResource` (workbook sections), `GoalCategoryResource` (goal categories + performance measures) |
| Permissions | `goalsheets.view/.create/.edit/.delete/.acknowledge/.reports/.admin` (see `docs/PERMISSIONS.md`) |
| Seeded data | `GoalSheetsSeeder` (six workbook sections; four goal categories with the workbook's default measures) |
| Tests | `tests/Feature/GoalSheetsTest.php`, fixture `tests/Fixtures/2026 GOAL SHEETS.xlsx` |

## The six workbook sections

Goal sheet categories (`goal_sheet_categories`, names kept exactly as in the workbook):

`CBE E.Y.E` · `ICDC  E.Y.E` · `CBE UPPER PRIMARY` · `ICDC  UPPER PRIMARY` · `C.B.EJUNIOR SCHOOL` · `I.C.D.C JUNIOR SCHOOL`

Super admins may add, rename or retire them under **Goal Sheets → Workbook Categories**. Each goal
sheet belongs to exactly one. The category also drives the academic-level label used in reporting.

## Goal categories & default measures (configurable, not hardcoded)

The workbook contains four goal categories; they are rows in `goal_categories`, not constants:

| Goal Category | Default performance weight |
|---|---|
| Academic Performance | 0.5 |
| Reading Skills | 0.2 |
| Writing Skills | 0.2 |
| Learner Discipline | 0.1 |

Each category carries a list of **performance measures** (`goal_measures`) — the goal statement,
UOM, calculation method, source of data, HB/LB achievement type, default weight and goal-for-year the
workbook suggests. Picking the category on a sheet pre-fills those values; every value remains
editable. Super admins manage categories and measures under **Goal Sheets → Goal Categories &
Measures** (goalsheets.admin).

## Layout of a goal sheet

Header mirrors the workbook: staff name, job title, department, goal year, **Line Manager 1**
(Coordinator) and **Line Manager 2** (Deputy). Each goal records:

Goal number · category · goal statement/performance measures · goal for the year · UOM ·
calculation method · source of data · achievement type (HB/LB) · performance weight.

Each goal carries any number of subject/class rows, each with **termly targets** (Term 1/2/3) and the
seven monthly performance checkpoints (**OP T1, MID T1, END T1, MID T2, END T2, MID T3, END T3**).
Termly targets and monthly checkpoints are stored separately, exactly as in the workbook. Non-subject
goals (Reading / Writing / Learner Discipline) usually have a single subject/class row with the
subject left blank.

### Termly targets vs checkpoints

| Term 1 | Term 2 | Term 3 |
|---|---|---|
| OP T1 · MID T1 · END T1 | MID T2 · END T2 | MID T3 · END T3 |

(The workbook has no OP T2/OP T3 columns; we keep the same set of checkpoints.)

## Percentage normalization

The workbook stores target and goal values as fractions (`0.8`, `0.45`, `0.85`, `0.9`) and actuals as
raw percentages (`84.49`, `92`, `75`, `100`). On import, every percent-like cell is normalized to a
consistent 0–100 unit:

- values `≤ 1` are treated as fractions and multiplied by 100 (`0.8` → `80`),
- values above 1 are kept as-is (`84.49` → `84.49`).

Performance weights are fractions (0.5/0.2/0.1) and are **not** multiplied. Achievement type is
`HB` (higher is better, default) or `LB` (lower is better).

## Calculations (what we compute, and from what)

The workbook provides only two formula kinds: `AVERAGE(...)` over the subject/class rows of a goal,
and `SUM(...)` of performance weights. Everything else is empty (`0`, "To be filled at END OF YEAR").
We implement exactly those, and document the rest rather than invent provenance.

Implemented and displayed on the sheet and dashboard (nothing hardcoded to 2026):

1. **PER-COLUMN AVERAGES (AVERAGE, workbook semantics)** — for each numeric column (term targets and
   every checkpoint), the average of the subject/class rows that carry a value. Labelled
   "AVERAGE (workbook formula)".
2. **Performance weight total** — `SUM(performance_weight)` over the goals (workbook's SUM row;
   expected 1.00, shown in green when it is).
3. **Attainment % (derived, clearly labelled)** — for each goal, for each recorded
   (checkpoint × its term target) pair: HB goals compute `actual / target × 100`, LB goals compute
   `target / actual × 100` (both termless of subject rows; only rows with both values count, nulls are
   excluded — same AVERAGE semantics). The result is shown per goal as **Goal attainment**.
4. **Weighted Rating (calculated)** — `Σ over goals with data (performance_weight × attainment)`.
   When every goal has data (weights sum to 1.00) this is the full-sheet rating. Shown as
   *Weighted Rating (calculated)* next to the manual *Weighted Rating (end of year)* field, which is
   the workbook's own "To be filled at END OF YEAR" slot, editable by line managers at year end.
5. **Attainment coverage** — the sum of weights of goals that have attainment data. Tells you what
   share of the weighting actually had numbers behind it.
6. **Term attainment** — same attainment formula restricted to each term's *END* checkpoint
   (END T1 vs Term 1 target, END T2 vs Term 2, END T3 vs Term 3), per sheet.

What we deliberately do **not** compute: any invented "official" end-of-year weighted rating, since
the workbook itself leaves the Weighted Rating column empty and states it is filled at year end.

## Acknowledgement & sign-off (three-party)

At the end of every sheet:

- **Goal Sheet Owner (Staff Member)**
- **Line Manager 1 (Coordinator)**
- **Line Manager 2 (Deputy)**

In-system, each party signs with a typed signature (full name) and timestamp recorded against their
account; optional notes. The owner signs automatically when the staff member submits the sheet for
review; the two line managers sign from the sheet's view page. When both line managers have signed,
the sheet is marked **Acknowledged**. Wet signatures from the paper workbook are preserved as the same
three-party process digitally.

## Access control

Enforced at three levels — interface (navigation and page access), query scope
(`GoalSheet::scopeAccessibleTo`), and model-level write guards (submitted/acknowledged sheets are
read-only outside Super Admin):

- **Staff** see only their own goal sheets (`goalsheets.view` scope).
- **Line Manager 1 (Coordinator)** sees sheets where they are the assigned coordinator. When the
  imported sheet has no coordinator match, any senior-leadership user (super_admin / CEO / Principal /
  Deputy Principal / Head Teacher) may sign as LM1, so nothing is unassignable.
- **Line Manager 2 (Deputy)** sees sheets where they are the assigned second signature (same fallback).
- **Coordinators / senior leadership** need `goalsheets.acknowledge` to sign; whoever signs needs it
  except the owner, who signs as themselves.
- **Dashboard** requires `goalsheets.reports`; it still scopes data to what you may see.
- **Import & configuration** requires `goalsheets.admin` (Administrator and Super Admin by default).
- **Super Admin** manages all of this via existing Roles & Permissions (goal sheets is a section).
  Teacher by default: own sheets only (view/create/edit own, submit).

## Importing the workbook

Panel → **Goal Sheets → Import Workbook** (goalsheets.admin). Choose *2026  GOAL SHEETS.xlsx*, run a
**dry run** first (nothing is written; report lists every sheet, warnings and errors), then import for
real. The import:

- reads all six worksheets;
- identifies individual goal sheets (title row `TEACHER GOAL SHEET-<year>`);
- imports staff info, goals (numbers and statements), subjects/classes, termly targets and monthly
  checkpoints — no goal guessing, no invented values, blanks stay blank;
- **normalizes percentages** to one unit (see above);
- **matches staff, coordinators and second signatories by name** against existing user accounts
  (case/space/punctuation-insensitive). Matched sheets are linked to accounts (Najma Ramadhan in the
  sample workbook links to najma@gmail.com). Unmatched names are imported as-is with a warning so the
  school can link or create accounts later — re-importing after linking links them automatically;
- **never duplicates**: sheets are upserted by (workbook section, staff name, goal year). Re-importing
  the same workbook updates in place (verified: 55 sheets → 0 created / 55 updated);
- **validates and reports**: per sheet counts of goals/entries, account match, performance-weight
  total (1.00 expected, warning otherwise), out-of-range values, and hard errors. Nothing is silently
  dropped;
- **workbook quirks are handled**:
  - one sheet in the I.C.D.C JUNIOR SCHOOL worksheet (sheet #8, `Arabic/Fiqh/7R/7B`…) has **no staff
    name** in the workbook. It is imported with the placeholder name *"Unnamed staff member (row N)"*
    and a warning, so no data is lost;
  - several sheets are **partially filled in the workbook itself** (e.g. a Vincent Ayana E.Y.E sheet
    with only Academic Performance and Learner Discipline, weights totalling 0.6). These import as-is
    with a weight warning — exactly the data the school filled in;
  - goal numbers that jump (e.g. #1 and #4 with #2/#3 blank) are preserved;
  - `=AVERAGE(...)` and `=` formula cells are consumed as display-only (we recompute and show our own
    labelled average row).

Sample workbook import result (development database): **55 sheets · 206 goals · 319 subject/class
rows · 0 failures · matched account: Najma Ramadhan**.

## Dashboard

Panel → **Goal Sheets → Goal Sheet Dashboard** (goalsheets.reports). Live from recorded data:

- sheets created / submitted / acknowledged / pending acknowledgement, average calculated weighted
  rating (respects row-level accessibility — coordinators see their assigned sections only);
- breakdown by workbook category, coordinator and department (normalized, case-insensitive grouping);
- individual sheets list with status, pending flags and rating; every row links to the full sheet.

## Known limits / notes

- The six workbook worksheets are the canonical goal sheet categories; other sections would be created
  by admins (same mechanism, no code).
- The workbook's Weighted Rating column is empty by design ("To be filled at END OF YEAR"); the
  derived "Weighted Rating (calculated)" shown is computed from recorded checkpoints and is labelled
  accordingly. It is not the workbook's official end-of-year rating.
- Imported sheets arrive as **draft** (the workbook sheets were never signed); staff confirm/complete
  and submit, and line managers acknowledge — the school's wet-signature process becomes digital.
- Import links staff by name only; exact duplicates are updated, never duplicated.
- The workbook fixture used by the test suite (`tests/Fixtures/2026 GOAL SHEETS.xlsx`) contains the
  real staff names from the source document — it is the same document the school provided.

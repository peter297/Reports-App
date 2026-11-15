# RBAC & Organizational Reporting Redesign — Implementation

Date: 2026-11-15. Supersedes Phase 1 audit in `docs/RBAC_AUDIT.md`. Companion test suite: `tests/Feature/ReportingWorkflowTest.php` (13 acceptance tests) + `tests/Feature/SyllabusTest.php` (37 tests, all green).

## 1. What changed at a glance

| Area | Before | After |
|---|---|---|
| Roles | 14 inconsistent names (plural `Coordinators`, `HeadTeacher`, `ICT Department`, `Admin`, `School Administrator`, `Head of Admissions`) | 13 spec roles, normalized in place (user assignments preserved) |
| Permissions | 1 dead row (`page_SyllabusDashboard`), never evaluated | 31-permission catalog, role→permission matrix, Super Admin manages via **Roles & Permissions** UI |
| Branch scope | `users.branch` (free string), coordinator scope in `section_coordinator_assignments` (string, **0 rows**, broken gates) | `users.branch_id` FK; **`coordinator_assignments`** table (user × branch × academic level, FK + unique + `is_active`) |
| Coordinator access | Syllabus dashboard invisible/403 | Coordinator logs in → dashboard + mappings + reports **work** (verified) |
| Reports | Legacy super-admin inbox (0 rows), no workflow | Submission → review → return/resubmit → approve lifecycle, recipient routing, revisions, comments, attachments, templates with versioning, consolidated compilation for Principal & Head Teacher, overdue command |
| Reporting hierarchy | `users.line_manager_id` only | **`reporting_assignments`** (role/scope → recipient, Super-Admin configurable) + **`reporting_relationships`** (person → manager lines, HR into Principal & CEO) |
| Report content | Manual re-entry | **`ReportDataSources`** auto-populates sections from Enrollments, Syllabus mappings/coverage, Attendance, Discipline, Formative assessments. Missing data renders explicit notices, never fabricated figures |
| Historical integrity | none | `template_versions` + section snapshots in `report_section_responses`; revisions preserved; compilations reference all source reports; missing submissions flagged, never silently zeroed |

## 2. Organizational structure (seeded, idempotent)

### 2.1 Accounts (all use password `password123!`, rotate for production)

| Role | Account(s) | Scope |
|---|---|---|
| Super Admin | `admin@alameenacademy.com` | Everything, incl. configuration (gate bypass) |
| CEO | `ceo@alameenacademy.com` | Org-wide read, unrestricted visibility; receives Fleet Manager, HR Officer, Principal consolidated reports |
| Principal | `principal@alameenacademy.com` | Org-wide academic access; receives branch Administrator reports, ICT, HR, Head Teacher consolidated; compiles **Principal Consolidated Report** → CEO |
| Deputy Principal | `deputy.principal@alameenacademy.com` | Org-wide oversight (read-only except cannot compile — no `reports.compile`) |
| Head Teacher | `head.teacher@alameenacademy.com` | All branches academic; receives Coordinator/DHT/Dean reports; compiles **Head Teacher Consolidated Report** → Principal |
| Administrator | `admin.juja@…`, `admin.southc@…`, `admin.kitisuru@…` | Own branch only; submits **Administrator Report** → Principal |
| Deputy Head Teacher | `dht.juja@…`, `dht.southc@…` | Own branch, all levels (no Kitisuru, per spec); reports → Head Teacher |
| Coordinator | `coord.eye.juja@…`, `coord.up.juja@…`, `coord.js.juja@…`, `coord.eye.southc@…`, `coord.up.kitisuru@…` | `coordinator_assignments` rows (branch × level) |
| Dean of Islamic Studies | `dean.juja@…`, `dean.southc@…`, `dean.kitisuru@…` | Own branch; Islamic-Studies-tagged subject scope (ISLAMIC STUDIES subjects seeded per level) |
| ICT Officer | `ict@alameenacademy.com` | Org-wide read (`reports.view_all`); reports → Principal |
| HR Officer | `hr@alameenacademy.com` | HR reports → **Principal AND CEO** (two routing rules) |
| Fleet Manager | `fleet@alameenacademy.com` | Fleet reports → CEO, nothing else |
| Teacher | `amina@alameenacademy.com`, `brian@alameenacademy.com` (Juja Road G1 Blue/Green), plus pre-existing `najma@gmail.com`, `shella@gmail.com` | Teacher assignments; not in the organizational reports module by default |

`School Administrator` merged into `Administrator` and `Admin` removed (both were unused, 0 users); `Head of Admissions` removed (outside spec; 0 users).

### 2.2 Routing (`reporting_assignments`, configurable in UI under System Settings → Reporting Assignments)

Administrator→Principal · Coordinator→Head Teacher · Deputy Head Teacher→Head Teacher · Dean of Islamic Studies→Head Teacher · Deputy Principal→Principal · ICT Officer→Principal · HR Officer→Principal &→CEO · Fleet Manager→CEO · Head Teacher→Principal · Principal→CEO.

### 2.3 Templates (System Settings → Report Templates; versioned — edits create a new version, submissions keep the one they were created from)

Principal Consolidated Report · Head Teacher Consolidated Report · Administrator Report · Coordinator Report · Teacher Report · Deputy Head Teacher Report · Dean of Islamic Studies Report · Deputy Principal Report · HR Officer Report · Fleet Manager Report.

## 3. Super-Admin configuration surfaces (new)

- **Organizational Reports** — submit/review/approve/return/archive workflow (Reports module; badge = pending for me).
- **Report Compilation** — Principal & Head Teacher: expected-submissions tracking matrix (dynamic; coordinator expectation = users with ≥1 active assignment), explicit missing-submission flags, one-click consolidated draft, prior consolidations list.
- **Report Templates** — full section CRUD, mandatory/auto flags, data-source binding, reorder, publish-as-new-version.
- **Reporting Assignments** — routing rules (role/branch/level → recipient role/user).
- **Reporting Lines** — explicit person→manager relationships.
- **Roles & Permissions** — role CRUD + permission matrix (checkbox list); super_admin is read-only.
- **Coordinator Assignments** — branch/level scope management.

## 4. Acceptance criteria (spec §15) — status

**A. Coordinator access** — all ✓ verified: login, dashboard (`/admin/syllabus-dashboard` → 200), scoped mappings, lesson drill-down, no other branch records (`ReportScope::applyTo` at query level), review comments. ✗ gap: *teachers under supervision list* is on the dashboard rows; deep-dive is mapping-by-mapping.

**B. Administrator access** — ✓ own branch template visible; learner numbers auto-pull from Enrollments; submits → Principal; cross-branch report → 403.

**C. Principal access** — ✓ sees all Administrator + ICT + HR + Head Teacher consolidated submissions; pending shown; can compile (missing flagged); submit to CEO; cannot see coordinators' individual reports (only consolidated); HR dual recipient verified.

**D. Head Teacher access** — ✓ coordinator reports across 3 branches; tracking matrix; compile → Principal; cannot see Administrator reports (403).

**E. Fleet Manager** — ✓ submits → CEO; cannot open unrelated reports (403).

**F. HR** — ✓ routes to Principal and CEO; both can view.

**G. CEO** — ✓ all reports visible (`reports.view_all`); can view all submitted reports & statuses.

**H. Permissions and security** — ✓ nav hidden, direct URLs 403, backend gates, query-level branch scoping, parameter tampering contained by `accessibleTo`/`applyTo`, role edits take effect immediately (Spatie flushes its permission cache on role save), existing data preserved (additive migrations; legacy inbox dropped only after confirming 0 rows; backup in `backups/reports-pre-rbac-*.sql`).

## 5. Known limits / follow-ups

1. **Professional documents** and **lesson attendance** have no system tables; those sections render an explicit “not tracked yet” note (never fabricated).
2. **Formative assessment summaries** draw from `syllabus_assessments`; **discipline** draws from class-linked `disciplines`; **attendance** from `attendances`; all are empty today — auto-content says so honestly.
3. Consolidation auto-fill matches sections by keyword; non-matching narrative sections are compiled from tagged source material; the compiler (Principal/HT) reviews and edits before submitting.
4. `reports:mark-overdue` runs daily at 07:00 (routes/console.php) — reminds outstanding submitters and alerts their recipients; not run against live data during delivery.
5. Teacher report submission is off by default (teachers lack `reports.module.view`); grant it explicitly per teacher if required — routing for teachers is unconfigured (by design); add a rule (e.g., Teacher → Coordinator by level) when ready.
6. `Teacher` template exists but is unused until module access is granted — intentional.
7. `hasRole()` is used on seeded roles only; `hasRoleSafe()` retained for defensive contexts.
8. The `Gate::before` super-admin bypass remains, and the Spatie permission layer now actually works underneath it.
9. CEO cannot compile (by design); Deputy Principal is read-only oversight.
10. `backups/reports-pre-rbac-*.sql` is a full logical dump of the dev DB taken before the schema redesign — keep it until the redesigned module has been exercised in production for a full cycle.

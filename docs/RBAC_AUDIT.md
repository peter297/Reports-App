# RBAC & Reporting Redesign — Phase 1 Audit

Date: 2026-11-15. Author: engineering session. Status: **Phase 1 (Audit) complete** — remediation in progress.

## 1. Executive summary

The organization does not have a working RBAC system today. Spatie Permission is installed and Filament Shield is registered, but the permission layer was never seeded or wired into the UI: **exactly one permission exists** (`page_SyllabusDashboard`, granted to role `Coordinators`) and **no code path evaluates it** — the Syllabus Dashboard defines its own `canAccess()` using inline role checks. All authorization is ad-hoc, duplicated across resources, and funnels through two broken data sources:

1. `section_coordinator_assignments` — **empty (0 rows)**. Every coordinator-scoped gate (`hasSyllabusCoordinatorScope()`, `coordinatesSyllabusMapping()`, `canManageAttendance()`, `AttendanceResource::canViewAny()`, dashboard access…) evaluates to `false` for every coordinator account. **This is the direct cause of "Coordinator cannot see the Syllabus Mapping Dashboard."**
2. `subject_assignments` — populated only for the two seeded teachers (users 2, 3); the Coordinator (user 4) has none.

The `reports` module is a legacy super-admin inbox (0 rows), not an organizational workflow. There are **no** tables for report recipients, routing, submissions lifecycle, reviews, revisions, or compilations. `report_templates` (0 rows) stores uploaded Word/Excel files, not role-based section templates.

## 2. Findings (detail)

### 2.1 Roles (`roles` table, 14 rows) — inconsistent with the organization

`Deputy HeadTeacher`, `Dean of Islamic Studies`, `Coordinators`, `School Administrator`, `Head of Admissions`, `Teachers`, `Deputy Principal`, `Fleet Manager`, `CEO`, `Principal`, `HeadTeacher`, `ICT Department`, `Admin`, `super_admin`.

Problems: plural (`Coordinators`, `Teachers`) vs singular models assumed elsewhere; `ICT Department` instead of ICT Officer; `Admin` duplicate of `School Administrator`; `Head of Admissions` outside the defined org; no `Administrator`/`HR Officer` at all. **0 users** hold every specified management role; only `super_admin`(1), `Coordinators`(1), `Teachers`(2) are assigned.

### 2.2 Permissions / Shield — dead weight

- `permissions`: 1 row (`page_SyllabusDashboard`). `role_has_permissions`: 1 row (above). `model_has_permissions`: 0.
- Shield's generated `RoleResource` does not exist; Shield role policy enabled; `super_admin` handled via manual `Gate::before` (AppServiceProvider), not Shield's gate.
- All 25 policies call `$user->can('view_any_*')` style permissions that **do not exist** → false for non-super-admins. Resources compensate with inline `hasRole()` checks (see below).

### 2.3 Authorization hotspots (all inline, no central policy)

- `AppServiceProvider::boot()`: `Gate::before` grants everything to `super_admin` only.
- `User::hasUnrestrictedAccess()` = `super_admin | CEO | Principal | HeadTeacher | ICT Department | Admin` (org-wide data access).
- `User::canAccessPanel('admin')` = unrestricted ∪ `Teachers | Coordinators | School Administrator | Deputy HeadTeacher`.
- Syllabus module: `hasSyllabusManagementAccess()` (unrestricted ∪ School Administrator), `hasSyllabusViewAllAccess()` (management ∪ Deputy HeadTeacher ∪ `hasSyllabusCoordinatorScope()`), `hasSyllabusCoordinatorScope()` = role `Coordinators` **and** `sectionCoordinatorAssignments()->exists()` → **false everywhere today**.
- Filament visibility: most resources override `canViewAny()` with inline checks; some have none (`UserResource`, `ReportResource`, `DisciplineResource`, `ItemRequestResource`, `StreamResource`, `EventResource` are visible to **every** panel user).
- `ReportTemplates` page: visible to all; `deleteTemplate()` has **no authorization check** — any panel user can delete any template.
- `AttendanceReportController` grants any Coordinators-role user download access with **no scope check**.
- Syllabus PDF/Excel routes gate via `canAccessMapping()` (same empty-scope failure).

### 2.4 Data model gaps vs required structure

| Spec entity | Status |
|---|---|
| roles / permissions / role_permissions | exist (Spatie), unseeded/unwired |
| users branch link | `users.branch` string (legacy), `line_manager_id` FK exists |
| Branch/level linkage for assignments | `subject_assignments` (string `branch`), `section_coordinator_assignments` (string `branch`, `section`; **0 rows**; no FKs to `branches`/`academic_levels`) |
| reporting_relationships / assignments | **missing** (only `line_manager_id`) |
| report_templates (role-based, sections, versioning) | legacy file-upload table only |
| reports (workflow, recipients, period, status) | legacy inbox (`category/subject/summary`), no status/period/recipients |
| report_section_responses / report_revisions / report_reviews | **missing** |
| report_recipients / routing | **missing** (`report_user` pivot exists but unused by any workflow; 0 rows) |
| report_compilations / compilation_sources | **missing** |
| attachments / comments | legacy tables exist (0 rows), `attachments` lacks uploader/size metadata |
| notifications infrastructure | **missing** (`notifications` table does not exist) |
| Auto-source data stores | `enrollments`, `attendances`, `disciplines`, `class_sizes` exist (all **0 rows**) — integrations must be null-safe |

### 2.5 Why the Coordinator sees nothing (trace)

`SyllabusDashboard::canAccess()` → `hasSyllabusViewAllAccess()` → `hasSyllabusCoordinatorScope()` → role `Coordinators` ✓ **and** `sectionCoordinatorAssignments()->exists()` ✗ (empty) → false. `subjectAssignments()->exists()` ✗ (user 4 has none) → dashboard hidden; mappings resource hidden; PDF/Excel 403.

### 2.6 Risks noticed

- Hard-coded `password123!` for the super admin (seeder) — retained for dev parity but must be rotated for production.
- Direct `hasRole()` calls throw `RoleDoesNotExist` if a role is renamed without migrating users.
- `SectionCoordinatorAssignmentResource` manages coordinator scope rows but is restricted to super-admins and writes legacy string branch/section values that never match `academic_levels` aliases reliably.
- Legacy `reports`/`ReportResource` ("organization-reports") visible to all panel users, mutations gated by policies whose permissions never existed.

## 3. Decisions (implemented in remediation phases)

1. **Retain Spatie Permission 6.9 + Shield** — extend, do not replace.
2. **Normalize roles to the specification** via migration (rename in place, preserve user assignments; unused duplicates removed: `Admin`, `School Administrator`, `Head of Admissions` — 0 users each; `HeadTeacher`→`Head Teacher`, etc.).
3. **Scope by FK**: `users.branch_id → branches.id` (null = org-wide roles), new `coordinator_assignments` (user/branch/academic_level, FKs, unique) replacing `section_coordinator_assignments`; `subject_assignments` used for teacher assignment scope.
4. **Centralized permissions**: seeded catalog, role→permission matrix migration; resources/pages gate on permissions + scope helpers; navigation and backend both enforced.
5. **Compilation is a report**: consolidated reports are reports whose `template_version_id` is the Principal/Head Teacher consolidated template and whose content was auto-compiled from submitted sources, linked via `report_compilation_sources`. Original lineage retained; missing submissions explicitly flagged, never silently zeroed.
6. **Historical immutability**: responses snapshot section title/order/mandatory flags; template edits create a new version; submitted reports keep their version.
7. **Recipients are snapshotted at submission** (`report_recipients`) from configurable routing (`reporting_assignments`, editable by Super Admin) — changing routing later never rewrites history.
8. **CEO visibility is org-wide by role definition**; Principal visibility via routing rules + explicit head-teacher consolidated receipt; Head Teacher cannot see administrator reports; branch administrators cannot see each other's reports.
9. **Teachers** get no organizational-reports access by default; their syllabus reporting feeds coordinators via the existing module.
10. **Auto data sources** (`ReportDataSources`) are null-safe and return explicit "No data available" when a store is empty — never fabricated numbers.
11. **Demo org chart seeded** (idempotent) so every acceptance scenario in the spec can be executed; credentials documented in `docs/RBAC_REDESIGN.md` §Accounts.
12. **Legacy inbox is preserved but locked down** to `super_admin` (`ReportResource`, `ReportTemplates` page) to avoid data loss; it is superseded by the new module.

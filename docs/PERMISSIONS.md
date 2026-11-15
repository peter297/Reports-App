# Permission Audit — Coverage Matrix

Date: 2026-11-14
Author: engineering (RBAC remediation, phase 2).
Scope: every existing module, action and route in the Laravel/Filament application. No new modules were created; permissions were only added for functionality that exists.

## 1. How authorization works (all three layers)

1. **Navigation** — `canViewAny()` on the resource/page hides the entry from users without the view permission, and pages 403 on direct URL access without it.
2. **Action authorisation** — Filament's `CreateRecord`, `EditRecord` and table row actions are gated by the resource's `canCreate/canEdit/canDelete` where defined, otherwise by the model **policy**. All 22 policies were re-mapped from the dead Shield convention (`create_x`, never evaluated) to the catalog below. Row-level destructive actions (`EditAction`, `DeleteAction`, `DeleteBulkAction`) in every resource are individually gated to the module's manage permission.
3. **Query and model scoping** — records a user can *see* are filtered by `accessibleTo()` scopes (attendance/class sizes by branch+section, syllabus mappings by branch+level assignments, organizational reports by submitter/recipient/view_all, disciplines via class branch, events by branch-or-school-wide, item requests to the author unless `item_requests.manage`), and model `saving()` hooks abort writes outside the user's scope (attendances, disciplines, events), even for crafted requests.

**Enforcement on the write path** (important): attendance records can only be saved through the attendance model's `saving()` hook, which re-validates branch/section/class scope on every write; disciplines and events carry identical hooks. Query-level scoping prevents reading out-of-scope rows; model hooks prevent writing them.

## 2. Permission catalog (name → what it allows)

### Goal sheets module
| Permission | Grants |
|---|---|
| `goalsheets.view` | View goal sheets within scope |
| `goalsheets.create` | Create goal sheets |
| `goalsheets.edit` | Edit goal sheets (draft only) |
| `goalsheets.delete` | Delete goal sheets |
| `goalsheets.acknowledge` | Acknowledge/sign goal sheets (owner or line managers) |
| `goalsheets.reports` | View the consolidated goal sheet dashboard and summaries |
| `goalsheets.admin` | Import the GOAL SHEETS workbook and configure categories/measures |

### Syllabus module
| Permission | Grants |
|---|---|
| `syllabus.dashboard.view` | See the coverage dashboard |
| `syllabus.mappings.view` | View mappings within scope |
| `syllabus.mappings.progress` | Record lesson completion progress |
| `syllabus.mappings.manage` | Create/edit/delete mappings and structures |
| `syllabus.reviews.manage` | Coordinator review comments |
| `syllabus.reports.view` | Mapping PDF/Excel downloads |

### Reports module
| Permission | Grants |
|---|---|
| `reports.module.view` | Reports navigation item |
| `reports.view` | Own/recipient reports |
| `reports.view_all` | All branch/department reports (CEO/ICT-style oversight) |
| `reports.create` | Create reports from templates |
| `reports.submit` | Submit reports to line managers |
| `reports.review` | Mark received / under review / comment |
| `reports.return` | Return for revision |
| `reports.approve` | Approve reports |
| `reports.compile` | Compile consolidated reports |
| `reports.templates.manage` | Manage report templates (Super Admin only) |
| `reports.routing.manage` | Manage reporting routing and line relationships (Super Admin only) |

### School structure
| Permission | Grants |
|---|---|
| `sessions.view` / `sessions.manage` | Sessions, terms, weeks |
| `branches.view` / `branches.manage` | Branches |
| `academic_structure.view` / `academic_structure.manage` | Levels, classes, streams, subjects |
| `teacher_assignments.view` / `teacher_assignments.manage` | Teacher assignments |
| `enrollments.view` / `enrollments.manage` | Learner numbers and enrolments (branch-restricted for scoped roles) |

### Attendance & class sizes
| Permission | Grants |
|---|---|
| `attendance.view` | View attendance and class sizes (within scope) |
| `attendances.manage` | Create/edit/delete attendance records and class sizes (within scope) |

### Discipline
| Permission | Grants |
|---|---|
| `discipline.view` | View discipline records (within scope) |
| `discipline.manage` | Record/manage discipline cases (within scope) |

### Assessments & documents
| Permission | Grants |
|---|---|
| `assessments.view` | View formative assessments |
| `documents.view` | View professional documents |

### Events & requests
| Permission | Grants |
|---|---|
| `events.view` | View events and calendar |
| `events.manage` | Manage events, calendar and month quotes |
| `item_requests.view` | View item requests (own for requesters, all for managers) |
| `item_requests.manage` | Approve, fulfil, or edit any request |

### Operations & activities
| Permission | Grants |
|---|---|
| `billings.view` / `billings.manage` | Billing and fee records |
| `maintenance.view` / `maintenance.manage` | Maintenance work |
| `co_curriculars.view` / `co_curriculars.manage` | Co-curricular activities |
| `extra_curriculars.view` / `extra_curriculars.manage` | Extra-curricular activities |

### Administration
| Permission | Grants |
|---|---|
| `users.view` / `users.manage` | User directory and account management |
| `roles.manage` | Roles & permissions administration (Super Admin only) |
| `coordinator_assignments.view` / `coordinator_assignments.manage` | Coordinator branch/level assignments |
| `settings.manage` | System settings (quotes, events configuration) |

## 3. Scope enforcement

| Module | Scope rule |
|---|---|
| Goal sheets | Staff: own sheets. Line Manager 1 (Coordinator): sheets where they are the assigned coordinator (or senior leadership when unset, so imported sheets remain acknowledgeable). Line Manager 2 (Deputy): sheets where they are the assigned second signature (same fallback). Senior leadership (`hasUnrestrictedAccess` = super_admin/CEO/Principal/Deputy Principal/Head Teacher): everything. Coordinators/Deans also need `goalsheets.acknowledge` to sign; teachers sign only as sheet owner. Carried out at query level (`accessibleTo`) and re-checked in page mount + model guards. |
| Syllabus mappings | Teachers: own assignments. Coordinators/Deans: their branch × level assignments. DHT/Administrator: their branch. Unrestricted roles (CEO/Principal/Deputy Principal/Head Teacher): everything. |
| Attendance & class sizes | Authorised-by `canManageAttendance()`: own branch + assigned section, or assigned branch-and-level for coordinators; unrestricted roles bypass; checked server-side on every write (model hook) and on list load (query scope). |
| Discipline | Via the case's class branch; authorised by `accessibleTo` + write hook; DHT/coordinators/deans see only their branch. |
| Reports | Submitters see their own reports; recipients see reports routed to them; `reports.view_all` sees everything. Compilation sources remain individually scoped. |
| Events | Branch-scoped roles see/manage school-wide events (branch null) and their own branch only. |
| Item requests | Requesters see only their own; holders of `item_requests.manage` see and manage all. |
| Enrollments | Branch-scoped roles see and manage only their branch; unrestricted roles everything. |

## 4. Role matrix (seeded)

Seed matrix (executed via `RolePermissionSeeder`; re-running re-syncs drops of legacy names):

- **super_admin** — every catalog permission (Gate::before).
- **CEO / Principal / Deputy Principal / Head Teacher** — broad view suite + reports + goal sheets (`view`, `reports`, `acknowledge`). Principal/HT also compile/approve reports; HT also `attendances.manage`, `syllabus.reviews.manage`.
- **Deputy Head Teacher** — branch-scoped manage: attendance, discipline, enrolments; views structure modules; goal sheets (`view`, `reports`, `acknowledge`).
- **Coordinator / Dean of Islamic Studies** — attendance view+manage (own section), syllabus mappings + reviews + reports, discipline view, enrolment/events/item-request views; goal sheets (`view`, `reports`, `acknowledge`).
- **Administrator** — own branch manage: attendance, discipline, enrolments, events, item requests; goal sheets (`view`, `reports`, `admin` — import + configure categories/measures).
- **ICT Officer** - reports view/view-all/create/submit + structure views + goal sheets view; **Fleet Manager** - reports view/create/submit + events view; **HR Officer** - reports view/create/submit + events view + users view + goal sheets view; **Teacher** - assignments-scoped visibility only (own mappings, attendance view, item requests view, goal sheets `view`/`create`/`edit` own, submit for acknowledgement).

Users also carry direct (per-user) grants editable by Super Admin via Users → Edit → *Direct permissions*.

## 5. Known limits / notes

- Goal sheet acknowledgement is performed digitally in-system (typed signature + account + timestamp); the paper workbook's wet signature process is preserved as the same three-party sign-off.
- The events calendar PDF download routes now require `events.manage` (previously `gate::before` for super_admin only — now also configurable for administrators and Principals via permission).
- Email delivery of reports remains on the `log` mailer until SMTP is configured.
- `item_requests.view` grants requesters visibility of their own requests only (scope-enforced); `item_requests.manage` opens all requests.
- Permissions are deleted nowhere; renaming a role keeps all grants (rename in place).

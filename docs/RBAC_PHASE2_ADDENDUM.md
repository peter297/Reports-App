# RBAC & Reporting Redesign — Phase 2 addendum (2026-11-14)

Audit remediation. Full matrix in `docs/PERMISSIONS.md`.

- Coordinator can create/manage attendance for their assigned section (root cause was every resource
  falling back to non-existent Shield-convention permissions; policies were re-mapped to the live catalog).
- All modules now have configurable view/manage (plus deliberate action splits for reports).
  Super Admin assigns per role and per user (Roles & Permissions, direct permissions on the user form). Changes apply immediately.
- Scope enforcement (branch / section / assigned level / own reports) at navigation, query and model-save layers.
- Dead permission `page_SyllabusDashboard` removed; Shield's hidden “Roles” nav entry remains disabled
  (current entry is System Settings → Roles & Permissions).
- All existing migrations preserved; permission catalog expanded idempotently; records untouched.

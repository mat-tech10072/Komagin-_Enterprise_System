# Authorization Architecture — Index Note

**Document type:** Phase 0 supporting note (not a numbered deliverable — cross-reference only, to avoid duplicating content).
**Date compiled:** 2026-07-11

The full authorization mechanism (`requireLogin()` → `requirePermission()` → `hasPermission()` → `_loadRolePermissions()` → `role_permissions` table), the `super_admin` bypass, the complete role×permission×action matrix, the list of code locations that bypass this mechanism with hardcoded role checks, and the salary/bank-data masking coverage are all documented in:

- **Architecture Report** (`docs/remediation/Architecture/02-current-architecture-report.md`), §2 — architectural shape
- **Permission Matrix Report** (`docs/remediation/Permissions/04-permission-matrix-report.md`) — full canonical matrix and every deviation from it

This note exists only so the `Authorization/` folder required by the Phase 0 documentation structure is not empty; it intentionally holds no independent content to keep the permission system documented in exactly one place, per the "merge duplicates, don't create duplicate entries" principle applied to this baseline.

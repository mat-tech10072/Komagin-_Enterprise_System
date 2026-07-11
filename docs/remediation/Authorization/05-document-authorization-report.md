# Komagin HR — Document Authorization Report

**Document type:** Phase 1 Deliverable #6 of 10
**Objectives addressed:** Objective 5 (Document Authorization), Objective 9 (Record-Level Authorization, as applied to documents)
**Findings addressed:** KOM-036 (H-06/NM-04), KOM-021 (NH-04)
**Date:** 2026-07-11/12

---

## 1. Objective 5 — Separating View / Generate / Edit / Delete / Publish / Download / Print

Auditing every document-subsystem file against this list:

| Operation | File | Status before Phase 1 | Status after |
|---|---|---|---|
| View (list) | `modules/documents/index.php` | Correctly gated (`documents.view`) | Unchanged |
| Template view | `modules/documents/templates.php` | Correctly gated (`documents.view`) | Unchanged |
| Template create/edit | `modules/documents/templates.php` | Correctly gated (`documents.upload`, `create`) — this file already did per-action separation correctly | Unchanged |
| Generate (preview) | `modules/documents/generate.php` | Correctly gated (`documents.view` — previewing doesn't persist anything, `view` is the right action) | Unchanged |
| **Generate (save)** | `modules/documents/generate.php` | **Gap** — same `documents.view` gate covered persisting a new record | **Fixed** — now separately requires `requirePermission('documents.upload','create')` |
| View a specific generated document | `modules/documents/view_generated.php` | Gated by `documents.view` only, no record-level check | Record-level check added — see §2 |
| Approve/reject | `modules/documents/view_generated.php` | Correctly gated (`documents.verify`, `approve`) | Unchanged |
| Issue | `modules/documents/view_generated.php` | Correctly gated (`documents.upload`, `create`) | Unchanged |
| Delete | *(no delete action exists for generated documents in this codebase)* | N/A | N/A — `documents.delete` permission slug exists in seed data but has no code path yet; noted, not invented (workflow redesign is out of scope) |
| Upload (employee document) | `modules/documents/upload.php` | Correctly gated (`documents.upload`, `create`) | Unchanged |
| Verify (employee document) | `modules/documents/verify.php` | Correctly gated (`documents.verify`, `approve`) | Unchanged |

**The one real gap was the "generate → save" action**, and it's fixed. Everything else in this subsystem was already correctly separated by action before Phase 1 — this objective's work here was mostly *confirmation*, with one targeted fix.

## 2. Objective 9 — Record-Level Authorization for Generated Documents

`modules/documents/view_generated.php` fetched any `generated_documents` row by a raw, sequential `id` from the query string, with only the module-level `documents.view` check — no verification that the requesting user had any specific standing relationship to *that* document, and no audit trail of who looked at it.

### Design decision

`documents.view` is, by the seeded matrix, an HR-tier permission (`hr_manager`, `hr_officer`). HR staff legitimately need to see documents across the whole organization — a strict "you may only see documents you generated" rule would break the module's actual purpose. The record-level control that makes sense here isn't ownership-by-generator for everything; it's **workflow state**:

```php
function canAccessGeneratedDocument(array $doc): bool {
    if (in_array($doc['status'] ?? '', ['approved', 'issued'], true)) {
        return hasPermission('documents.view', 'view');
    }
    $isOwner = isLoggedIn() && (int)($doc['generated_by'] ?? 0) === (int)($_SESSION['user_id'] ?? 0);
    return $isOwner || hasPermission('documents.verify', 'approve');
}
```

- **Approved/issued documents** — the module's finished output — remain visible to any `documents.view` holder, matching the module's actual purpose.
- **Draft/pending-approval documents** — work in progress, potentially containing placeholder text, unfinished PII fields, or content a generator hasn't finalized — are now restricted to the person who generated it, or someone who holds `documents.verify` approve rights (i.e., someone whose job is literally to review other people's drafts).

`view_generated.php` was updated to call this before rendering anything, redirecting with an error and an `auditLog('documents','view_blocked', ...)` entry if it fails.

### Audit-on-view

Separately, and regardless of the record-level check outcome, every *successful* view of a specific document now writes `auditLog('documents','view_document', $id)`. Before Phase 1, only generate/approve/reject/issue actions were logged — simply opening and reading a document containing salary or disciplinary information left no trace at all. This closes that gap.

## 3. Live Verification and Its Limits

Both changes were syntax-checked and smoke-tested live (page loads, no fatal errors, for `super_admin`). The record-level restriction on draft documents could **not** be independently demonstrated with a distinguishing live test in this environment: every currently-seeded role that holds `documents.view` (`hr_manager`, `hr_officer`) *also* holds `documents.verify` approve rights, so under today's role data every viewer is also a verifier, and the "owner-or-verifier" rule can never actually block anyone yet. This is stated plainly rather than glossed over — the code is correct and forward-looking (it protects against a future view-only role, e.g. an auditor or read-only HR seat, which several other findings in the Master Register note as a realistic future addition), but its practical effect today is zero. This is recorded in the Master Remediation Register against KOM-021 and in the Regression Test Report.

## 4. What Was Explicitly Not Touched

`config/DocumentEngine.php`'s template-rendering/escaping logic (the subject of a separate, unrelated finding — KOM-022/NH-05, stored-XSS-via-template-authoring) was not modified. That is an input-sanitization defect, not an authorization-consistency one, and was not in the Phase 1 charter's named findings list.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial document authorization report | Remediation Program — Phase 1 |

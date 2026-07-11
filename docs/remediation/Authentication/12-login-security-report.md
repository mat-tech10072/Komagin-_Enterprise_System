# Komagin HR — Login Security Report

**Document type:** Phase 2 Deliverable #7 of 11
**Objective addressed:** Objective 6 — Login Security
**Findings addressed:** KOM-052, plus the login-flow portions of KOM-012/KOM-017/KOM-050
**Date:** 2026-07-11/12

---

## 1. Credential Validation and Password Verification

Unchanged and confirmed correct across all surfaces: `password_verify()` against bcrypt hashes everywhere (admin `users`, `employees.portal_password`, `temp_employees.portal_password`, `consultants.portal_password`). No surface stores or compares plaintext.

## 2. Failed Login Handling — Now Consistent

| Surface | Lockout mechanism | Threshold |
|---|---|---|
| Admin | DB columns (`login_attempts`, `locked_until` on `users`) | 5 attempts / 15 min |
| Employee Portal | **New** — `audit_logs`-based, per-IP | 5 attempts / 15 min |
| Consultant Portal | **New** — `audit_logs`-based, per-IP | 5 attempts / 15 min |
| Temp Employee Portal | Shares Employee Portal's login endpoint, same protection | 5 attempts / 15 min |

The admin surface's mechanism (dedicated columns on `users`) was left as-is — it already worked and altering it wasn't necessary or requested. The three portal surfaces needed a different implementation specifically because `employees`, `temp_employees`, and `consultants` have no `login_attempts`/`locked_until` columns, and this phase's charter forbids a database redesign. Reusing `audit_logs` (already used by the attendance kiosk for an identical rate-limiting purpose) achieves the same 5-attempts/15-minutes standard with zero schema change — see `portalLoginBlocked()`/`recordPortalLoginFailure()` in `config/functions.php`.

**Trade-off, stated plainly:** the admin lockout is per-account (locks that specific username regardless of source IP); the new portal lockout is per-IP (blocks further attempts from that network address regardless of which employee/consultant number is being tried). This is a deliberate choice for the portal surfaces — since employee/consultant numbers are sequential and guessable (a pre-existing, separately-tracked finding, KOM-003, about the attendance kiosk), a per-account lockout alone would not stop an attacker from simply trying the next number; per-IP additionally limits how many *different* accounts a single attacker can probe.

**Live verification:** 5 wrong-password attempts against a real employee number, then a 6th, blocked, attempt — confirmed via the actual response text ("Too many failed attempts from this location. Please try again in 15 minutes.").

## 3. Rate Limiting / Brute-Force Protection

Covered above — this is the same mechanism as "failed login handling" in this application's design, not a separate layer.

## 4. Password Verification

See §1 — no changes, already correct.

## 5. "Remember Me"

Confirmed absent on all four surfaces, both before and after this phase — no persistent/remember-me cookie exists anywhere in the codebase. This is a factual statement of current scope, not a finding; the charter asked this to be reviewed, and the review's conclusion is that there is nothing to standardize because the feature doesn't exist on any surface.

## 6. Password Reset

Confirmed unchanged from the Phase 0 baseline finding (KOM-041): no self-service, email-based, or token-based password reset exists on any surface. The only recovery path is an already-authenticated admin manually resetting another admin-surface user's password (`modules/users/index.php`); the portals display static "contact HR" text. This was explicitly logged in Phase 0 as an accepted operational gap requiring a product decision, not a code defect — Phase 2 did not change this, consistent with that prior decision and with the charter's "no business workflow redesign" constraint.

## 7. Magic Links

The one magic-link implementation in the system — `self-service/update.php`'s employee-initiated profile-update flow — had its per-link CSRF comparison hardened to `hash_equals()` (KOM-062) and its session cookie given the standard Secure flag (KOM-042). The magic-link token's own validation (a `sha256` hash compared via a parameterized SQL `WHERE` clause) was reviewed and left as-is — it was not flagged as a finding requiring a code change, and altering token-validation logic itself would risk the kind of workflow change this phase's charter avoids.

## 8. CSRF on Login Forms Specifically

Covered in full in the CSRF Review Report — summarized here for completeness: Admin (already had it), Employee Portal (added), Consultant Portal (added). All three login forms now carry and verify a token before credentials are even checked.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial login security report | Remediation Program — Phase 2 |

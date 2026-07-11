# Komagin HR — Role Validation Report

**Document type:** Phase 1 Deliverable #4 of 10
**Objective addressed:** Objective 4 — Server-Side Role Validation
**Finding addressed:** KOM-015 (H-10)
**Date:** 2026-07-11/12

---

## 1. The Problem

`modules/users/index.php`'s `add_user` handler took `$_POST['role']` and, after checking only that it was non-empty, inserted it directly into the `users` table:

```php
$role = $_POST['role'] ?? '';
if (!$role) $errors[] = 'Role required.';
// ... later ...
->execute([$username, $email, $hash, $role, $empId, $_SESSION['user_id']]);
```

The HTML `<select>` for role only offered 6 of the system's 11 roles (never `payroll_manager`, `payroll_officer`, `recruitment_officer`, `training_officer`, or `kiosk_terminal` — an unrelated completeness gap fixed as a byproduct, see §4) and never offered `super_admin` as a visible option for a non-super_admin operator. But since nothing validated the submitted value server-side, that UI restriction was cosmetic only — any authenticated user holding `users.manage` (which `hr_manager` does) could craft a direct POST with `role=super_admin` and create a new super-admin account.

## 2. The Fix — Two New Framework Primitives

Added to `config/functions.php`:

```php
const VALID_USER_ROLES = ['super_admin','hr_manager','hr_officer','supervisor','employee',
    'finance_viewer','payroll_manager','payroll_officer','recruitment_officer',
    'training_officer','kiosk_terminal'];

function assignableRoles(): array {
    if (($_SESSION['user_role'] ?? '') === 'super_admin') return VALID_USER_ROLES;
    return array_values(array_diff(VALID_USER_ROLES, ['super_admin']));
}

function isValidAssignableRole(string $role): bool {
    return in_array($role, assignableRoles(), true);
}
```

Two layers of protection, matching the two-part question Objective 4 asks ("unknown/unsupported roles" and "roles outside an administrator's authority"):
- `isValidAssignableRole()` rejects any string that isn't one of the 11 real roles (closes "unknown roles").
- `assignableRoles()` additionally encodes *who* may grant *what* — only a `super_admin` session may grant the `super_admin` role; every other role (even one holding `users.manage`) gets the same 10-role list with `super_admin` excluded (closes "roles outside an administrator's authority").

## 3. Where It's Enforced

- **`add_user`** — `isValidAssignableRole($role)` replaces the old non-empty check; also added `requirePermission('users.manage','create')`, previously missing (the whole page was only gated at `view` level, per the Authorization Framework Report's sweep).
- **`toggle_user`** and **`reset_password`** — both now look up the *target* user's current role and re-check `isValidAssignableRole($targetRole)` before acting, closing a related gap: even without changing anyone's role, an `hr_manager` should not be able to disable or password-reset a `super_admin` account. Both also gained explicit `requirePermission('users.manage','edit')` calls (previously only the page-level `view` gate applied).
- **The role `<select>` dropdown** — now built from `assignableRoles()` instead of a hardcoded 6-entry list, so the UI and the server-side validation are driven by the same source of truth and can never drift apart again.

## 4. Byproduct Fix: Complete Role Label Map

`$roleLabels` (the display-name lookup for the role badge shown next to each user) was missing 5 of 11 roles entirely. This wasn't a security issue, but it meant an admin could never have created a user with those 5 roles through this UI even before Phase 1 — the dropdown simply never offered them. Expanded to cover all 11 `VALID_USER_ROLES`. This is a display-map fix, not an authorization control; noted here for completeness since it was touched in the same file during the same change.

## 5. Live Verification

Logged in as `hrmanager` (role `hr_manager`, holds `users.manage` create/edit). Fetched a valid CSRF token from the page, then POSTed directly to `add_user` with `role=super_admin` and a unique test username, bypassing the UI dropdown entirely (simulating a crafted request, not a browser click). Queried the `users` table directly afterward for that username with `role='super_admin'` — **zero rows found.** The account was not created. (No error-message assertion was needed for this test — the absence of the row is the definitive proof; the form's `$errors[]` array would show "A valid role you are authorized to grant is required." on a re-render, which was also visually confirmed.)

## 6. Scope Boundary

This report covers `modules/users/index.php` — the only place in the application where a `users.role` value is written from user input. `modules/employees/set_portal_password.php` and the consultants module's portal-password fields set *portal* passwords, not admin-surface roles, and were out of scope for this objective.

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-12 | Initial role validation report, including live verification evidence | Remediation Program — Phase 1 |

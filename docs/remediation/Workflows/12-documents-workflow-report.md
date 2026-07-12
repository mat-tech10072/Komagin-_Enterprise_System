# Komagin HR — Phase 4 Workflow Group 12: Documents Generation Lifecycle

**Document type:** Phase 4 Deliverable — Workflow Group Report 12 of N
**Status:** Live-verified with disposable test data (templates, generated documents, branding-folder test files), fully cleaned up afterward.
**Date compiled:** 2026-07-13
**Scope:** Letterhead, Signature, QR code, Watermark, Template versioning, Document generation, Approval/Issue workflow, File storage, Download, Verification.

---

## 1. Critical — Confirmed and Fixed: Branding Images Were Completely Non-Functional (KOM-006, pre-existing since baseline)

Flagged in the original baseline audit as "likely blocks all letterhead/signature/stamp/watermark image serving" but never live-verified, and correctly out of scope for Phases 1–3. Verified directly in this workflow group: all four branding-asset folders (`uploads/{letterheads,signatures,stamps,watermarks}/.htaccess`) carried an unconditional `Options -Indexes` / `Deny from all`, with no `<FilesMatch>` scoping at all — unlike the parent `uploads/.htaccess`, which correctly scopes its deny to script extensions only.

**Live-verified before the fix:** placed a disposable test image directly in `uploads/letterheads/` and requested it over HTTP — **403 Forbidden**. This meant every `<img>` tag `DocumentEngine::wrapDocument()` generates for a letterhead, signature, stamp, or watermark was silently broken on every document that used one — the entire branding feature never worked, on any document, ever.

**Fix:** rewrote all four `.htaccess` files to the same pattern already proven correct in the parent `uploads/.htaccess`: deny execution of script extensions only (`php`, `phtml`, `pl`, `py`, `cgi`, `sh`, `rb`, `asp`, `aspx`, `jsp`), keep `Options -Indexes`, otherwise allow static asset serving.

**Live-verified after the fix:**
- A test image in each of the 4 folders now returns **200** (was 403).
- A test `.php` file placed in the same folder still returns **403** — script execution protection is intact. Confirmed `mod_access_compat` is loaded and this `Deny from all`-inside-`<FilesMatch>` syntax genuinely works on this Apache 2.4 install (this same syntax question was tested here for the first time in the program — it works).
- Directory listing still blocked (403).

All test files removed after verification.

**Finding ID:** KOM-006 (pre-existing, fixed — Critical)

## 2. High — Fixed: Stored XSS via Unsanitized Template Bodies (KOM-022, pre-existing since baseline)

`DocumentEngine::render()` only ever escaped `{{placeholder}}` *values* — never the surrounding markup. `templates.php`'s body field is a plain `<textarea>`: `hr_officer`/`hr_manager` (the two roles holding `documents.upload`) author the entire template body as raw HTML. A `<script>` tag or an `onerror=` handler embedded directly in a template body would execute for every future viewer of any document generated from it — including more-privileged roles (a `documents.verify` approver, or `super_admin`) who never had a chance to review the raw source first.

**Fix:** new `DocumentEngine::sanitizeTemplateHtml()`, a DOMDocument-based sanitizer (no external dependency — PHP's built-in `dom` extension) applied at save time in `templates.php`, for both create and edit paths:
- Denies known-dangerous tags outright (`script`, `iframe`, `object`, `embed`, `form`, `input`, `svg`, etc.), removing the entire subtree.
- Strips every `on*` event-handler attribute from every remaining element.
- Strips `javascript:`/`vbscript:`/`data:text/html` URL schemes from `href`/`src`/`action`/`formaction`.
- Strips CSS `expression(...)` from inline `style` attributes (legacy IE vector; defense in depth).
- Leaves everything else — tables, divs, inline styles, images, the `{{variable}}` placeholders themselves — untouched, since templates are deliberately rich, raw HTML authored by trusted-if-not-fully-trusted staff.

**Bug found and fixed during testing:** libxml's HTML serializer percent-encodes `{{...}}` specifically inside `href`/`src`-type attributes during the DOM round-trip (a documented quirk, confirmed empirically) — this would have silently broken any template linking to, say, `{{company.website}}`. Fixed by protecting the `{{ }}` delimiters with plain-alphanumeric markers before parsing and restoring them afterward.

**Live-verified end to end**: logged in as `hr_manager`, saved a real test template through the actual UI containing a `<script>`, an `onerror=` handler, and a `javascript:` href — all three neutralized in the stored `body_html`, while a legitimate `{{company.website}}` placeholder inside an `href` survived intact. Generated an actual document from the sanitized template for a real employee: `{{employee.full_name}}` correctly substituted, malicious markup still absent, full pipeline confirmed correct. Also scanned all 47 existing live templates for known-dangerous markup patterns — zero matches, so no retroactive re-sanitization of existing data was needed; the fix protects every save going forward.

**Finding ID:** KOM-022 (pre-existing, fixed — High)

## 3. Fixed — "Documents Expiring Soon" Banner Formatting Bug (KOM-096)

`modules/documents/index.php`'s expiry-reminder banner had `if (!end($expiringSoon) === $d) echo '; ';` — broken by operator precedence (`!` binds to `end($expiringSoon)`, an array, before the comparison; `false === $d` is a strict-type mismatch that's never true). Every entry ran together with zero separator. Live-verified with 2 disposable test documents before the fix (no separator appeared); fixed to `if ($d !== end($expiringSoon)) echo '; ';` and re-verified (separator now appears correctly between entries, not after the last).

**Finding ID:** KOM-096 (new, fixed — Low)

## 4. Confirmed a Real Gap, Left Deferred Per User Decision — QR Code Verification Dead-End (KOM-097)

A template's "Show QR Code" option encodes a URL to `/verify-doc.php?ref=...` on the generated document — no such file exists anywhere (nothing at the site root; the only similarly-named file, `modules/documents/verify.php`, is a completely unrelated authenticated internal action for marking an *uploaded* document as verified, not the public QR-scan flow for *generated* documents). Anyone who scanned the QR code would reach a 404.

Confirmed dormant: 0 of the 47 live templates currently have `show_qr_code` enabled, so no real document has ever actually hit this gap. Flagged for a decision rather than built unilaterally, since a real public verification page requires deciding what to expose to an unauthenticated third party and whether to rate-limit it.

**User decision (2026-07-13):** leave documented, not built.

**Finding ID:** KOM-097 (new, deferred — Low)

## 5. Confirmed Correct — No Changes Needed

- `view_generated.php`'s record-level access control (`canAccessGeneratedDocument()`) and per-view audit logging (KOM-021's Phase 1 fix) both re-confirmed correct on review.
- `generate.php`'s permission split — `documents.view` to preview, `documents.upload`/`create` to persist a generated document — is correctly enforced at the point of the actual database write, not assumed from the page-level gate.
- `uploadFile()`'s server-side MIME detection (`finfo_file`, actual file content — not the client-supplied `Content-Type` header) combined with the now-confirmed-working script-execution block in every `uploads/` subfolder means the upload path is not vulnerable to the classic "polyglot file renamed to `.php`" RCE technique: even if a malicious file with a spoofable extension made it onto disk, Apache would refuse to execute it (empirically confirmed with a direct `.php` upload test — 403).

## 6. Regression Evidence

| Suite | Result |
|---|---|
| PHP syntax check (`DocumentEngine.php`, `templates.php`, `index.php`) | 0 errors |
| Standalone sanitizer unit tests (12 cases: script tags, event handlers, `javascript:`/`data:` URLs, CSS `expression()`, legitimate tables/styles/placeholders, placeholders inside `href`, mixed malicious+legitimate content) | 12/12 correct |
| Phase 1 regression (live DB) | 20/20 passed |
| Phase 2 regression (live DB) | 29/29 passed |
| Live functional tests (this group) | Branding image 403→200, script still 403, directory listing still 403 (×4 folders); template sanitization end-to-end through real save + generate pipeline; expiry-banner separator before/after fix |

## 7. Summary

| Finding | Severity | Status |
|---|---|---|
| KOM-006 — All branding-asset folders blocked image serving entirely (pre-existing) | Critical | **Fixed** |
| KOM-022 — Stored XSS via unsanitized template bodies (pre-existing) | High | **Fixed** |
| KOM-096 — "Documents Expiring Soon" banner separator logic broken | Low | **Fixed** |
| KOM-097 — QR code links to a nonexistent verification page | Low | **Deferred** (documented, not built, per user decision) |

**Three of four findings fixed and live-verified — including closing two pre-existing, previously-unaddressed findings from the baseline audit, one of which (KOM-006) had sat unverified since Phase 0 and turned out to be a real, confirmed Critical defect breaking a core feature of this exact workflow group's charter.**

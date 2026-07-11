# Komagin HR — Document Pipeline Report

**Document type:** Phase 0 Baseline Deliverable #6 of 9
**Status:** Documentation only — no file in this pipeline was modified to produce this report.
**Date compiled:** 2026-07-11
**Baseline tag:** `v1.0-enterprise-baseline`

This is the deep-trace document requested specifically for the document generation and branding-asset pipeline: template authoring → rendering engine → letterhead/signature/stamp/watermark fetch → storage → viewing/printing.

---

## 1. `config/DocumentEngine.php` — Rendering Engine

**Public methods:**
- `render(html, extra)` — substitutes `{{var}}` placeholders using the map from `buildVariables()` merged with `extra`; any leftover unmatched `{{...}}` token is flagged inline as a red "[missing variable]" span rather than left blank or causing an error.
- `wrapDocument(bodyHtml, tpl)` — assembles the final HTML: letterhead/watermark/doc-number/QR/signatures/stamp/footer wrapped around the rendered body.
- `buildVariables()` — builds the full associative variable map: company info, employee info, dates, currency, leave balance, latest payslip, supervisor lookup.
- `extractVariables(html)` *(static)* — regex-extracts `{{...}}` tokens from a template body, used by the template-builder UI to show which variables a template references.
- `catalogue()` *(static)* — returns the grouped variable list for the template-builder UI's sidebar reference panel.

**Placeholder substitution:** literal `str_replace('{{key}}', value, html)` per variable in the map — not a single-pass regex, so it's O(n) string replacement per variable per render.

**Letterhead/signature/stamp/watermark HTML construction:** each asset type is fetched by querying its own `company_*` table `WHERE is_active=1`, then hand-built, inline-styled `<div>/<img>` markup is concatenated: letterhead as a fixed full-page background image, watermark as centered rotated text/image, signatures as a flex row of image+name+designation, stamp as an absolutely-positioned image. All DB failures are caught and silently degrade to an empty string (a document renders without a letterhead rather than erroring, if the asset lookup fails).

**Escaping model:** `signature`, `stamp`, `letterhead`, `watermark`, `qr_code` (and any `signature.*`/`stamp.*` sub-keys) are treated as pre-built raw HTML and are **not** escaped when substituted into the document. Every other variable value is passed through `htmlspecialchars(ENT_QUOTES, 'UTF-8')`. Inside the raw-HTML builder methods themselves, the individual field values used to build that HTML (name, designation, file path) are still escaped — but the resulting HTML blob is then injected into the page unescaped, i.e. the "raw" allowance is scoped to engine-built markup, not to a value coming directly from an arbitrary source.

---

## 2. `modules/documents/templates.php` — Template Authoring

Templates are authored via a monospace `<textarea>` HTML editor using raw `{{var}}` placeholders (no WYSIWYG). A live "Document Configuration" panel lets the author select which letterhead/watermark/stamp/signatures to attach and toggle QR code, doc number, page number, header/footer, and a "requires approval" flag. Saving inserts/updates `doc_templates`; on edit, the previous `body_html`/`version` is archived into `doc_template_versions` before the row is overwritten and `version` incremented — simple linear versioning, no branching/diffing. Access: `requirePermission('documents.view')` to view, `requirePermission('documents.upload','create')` to save/toggle. Slugs auto-derive from the title, deduplicated with a timestamp suffix on collision.

Because the saved `body_html` is rendered by `DocumentEngine::render()` with only `{{placeholder}}` substitution escaped — the surrounding template markup itself is never sanitized — anyone holding template create/edit rights can embed arbitrary HTML/script directly in a template body, which then executes for every future viewer of any document generated from it (documented as-is here; see Master Remediation Register **NH-05** for the risk framing).

---

## 3. `modules/documents/generate.php` — Generation Flow

1. Select a template (dropdown grouped by category) and an active/probation employee.
2. POST `preview` — renders via `DocumentEngine` in-memory only; nothing persisted.
3. POST `save` — re-renders and writes: a row to `generated_documents` (status `draft` or `pending_approval`, depending on the template's `requires_approval` flag), plus a linked stub row in `employee_documents` (`file_path` = literal string `'generated:'.$id`, not an actual file on disk).
4. Redirects to `view_generated.php`.
5. Tables written: `generated_documents`, `employee_documents`, `audit_logs` (via `auditLog()`). `company_settings.doc_number_counter` increments whenever a doc-number placeholder is used.
6. The whole page, including the `save` action that creates a persistent record, is gated by `requirePermission('documents.view')` only — no separate create/upload-level check on the save branch specifically (contrast `templates.php`, which does check per-action).

---

## 4. `view_generated.php` / `missing.php`

**`view_generated.php`** — displays a saved generated document's stored `body_html` and drives its approval workflow (`draft` → `pending_approval` → `approved`/`rejected` → `issued`) via POST actions gated by `documents.verify`/`documents.upload`, each transition logged via `auditLog()`. "Print/PDF" is `window.print()` against a `@media print` CSS block that hides chrome — there is no server-side PDF generation involved (§8). Any row is fetched by `id` with only the blanket `documents.view` check — no additional per-employee/per-record scoping.

**`missing.php`** — cross-references every active/probation employee against `employee_documents` categories versus a hardcoded required-document list (`id_document`, `contract`, `bank_document`), listing anyone missing one, with a direct link to upload.

---

## 5. `modules/settings/branding.php` — Branding Asset Management

Four asset types (letterheads, signatures, stamps, watermarks) share one tabbed page, each with its own save/toggle/delete POST handlers. Uploads route through `uploadFile()` into `uploads/{letterheads|signatures|stamps|watermarks}/`, storing the returned relative path in the respective `company_*` table's `image_path` column. Edits allow leaving the file field blank to keep the existing image. `is_active` is a simple boolean toggled via a dedicated POST action per asset; letterheads additionally support a single `is_default` flag (setting one clears all others first). Entire page gated by `requirePermission('branding.letterheads')` — including signatures/stamps/watermarks CRUD, despite three separate permission slugs (`branding.signatures`, `branding.stamps`, `branding.watermarks`) existing in the seed data unused (see Permission Matrix Report §3).

Replaced/deleted assets are never removed from disk — update and delete handlers only touch the DB row, no `unlink()` call anywhere in the file.

---

## 6. `uploadFile()` — `config/functions.php` (line 555)

- Rejects unless `$file['error'] === UPLOAD_ERR_OK`.
- Size check against `MAX_FILE_SIZE` (10MB) using `$file['size']`.
- **MIME check via `finfo_open(FILEINFO_MIME_TYPE)` on actual file content** — not the client-supplied `Content-Type` header — compared against a caller-supplied allow-list.
- Filename generated as `uniqid('', true) . '_' . time() . '.' . strtolower($ext)` — the extension `$ext` is taken from the **original, client-supplied filename**, with no cross-check against the MIME type actually detected in the step above.
- Destination directory auto-created (`mkdir(..., 0755, true)`) if missing.
- Returns `{success, filename, path: 'uploads/<folder>/<file>', mime}`.

---

## 7. Template Library — `database/phase6_templates.sql`

**47 templates** (8 multi-row `INSERT INTO doc_templates` statements; the file's own header comment claims "50+" — the seeded count is 47) across **10 categories**: `employment_letters`, `hr_letters`, `certificates`, `payroll_documents`, `leave_documents`, `disciplinary`, `compliance`, `onboarding`, `exit_management`, `general`.

---

## 8. PDF / Print Generation

**Definitively browser-print of HTML — no server-side PDF library exists in this codebase.** No `composer.json`, no `vendor/` directory, no reference anywhere to `mpdf`, `dompdf`, `tcpdf`, or any comparable library. All "PDF" output — payslip downloads, generated document printing, ID cards — is `window.print()` against a print-styled HTML view, relying entirely on the visiting browser's native print-to-PDF capability.

---

## 9. Storage / File-Serving Layer

All four branding asset folders (`uploads/letterheads/`, `signatures/`, `stamps/`, `watermarks/`) carry an identical `.htaccess`:
```
Options -Indexes
Deny from all
```
This is legacy Apache 2.2 access-control syntax. The parent `uploads/.htaccess` instead uses modern Apache 2.4 syntax, narrowly scoped to blocking script execution (`<FilesMatch "\.(php|pl|py|cgi|sh|rb|asp|jsp)$">`) rather than blocking the folder outright. `DocumentEngine.php` and `branding.php` both reference these assets via plain `<img src="…/uploads/<type>/<file>">` tags — meaning if the child folders' legacy `Deny from all` is actively honored by the running Apache instance (XAMPP's default Apache 2.4 build loads `mod_access_compat`, which keeps this legacy directive functional), every letterhead/signature/stamp/watermark image would fail to load anywhere it's referenced: generated documents, template previews, and the branding admin page's own preview thumbnails. This was not confirmed with a live HTTP request during this documentation-only pass — see Master Remediation Register **NC-02**, flagged as requiring a live verification step (not a code change) as the very first action of Phase 1.

---

## Summary — Document Pipeline at Baseline

| Layer | State |
|---|---|
| Rendering engine | Functional; consistent escaping model for placeholders, raw-HTML allowance scoped to engine-built markup only |
| Template authoring | Functional; permission-gated per action; versioning present but simple/linear; body_html itself is unsanitized on save |
| Generation → save | Functional; permission gate does not distinguish view from create on the save branch |
| Viewing/approval | Functional; no per-record scoping beyond the module-level `documents.view` permission |
| Branding asset management | Functional; single coarse permission gates all 4 asset types; no disk cleanup on replace/delete |
| Upload validation | Real server-side MIME sniffing; filename randomized; extension trust gap (client filename, not cross-checked to detected MIME) |
| Storage/serving | **Needs live verification** — access-control syntax mismatch between parent and child upload folders may be actively blocking every branded image |
| PDF generation | Does not exist as a server-side capability; browser print only |
| Underlying schema | 4 of the document-pipeline's core tables (`doc_templates`, `doc_categories`, `generated_documents`, `doc_template_versions`) have no `CREATE TABLE` anywhere in the repository (Database Inventory Report §5) |

---

## Change Log for This Document

| Date | Change | Author |
|---|---|---|
| 2026-07-11 | Initial baseline document pipeline report compiled for Phase 0 | Remediation Program — Phase 0 |

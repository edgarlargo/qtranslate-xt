# QTX 4 full security re-audit

Date: 2026-08-24
Audited commit: `0d83d0b005965d82b8908be846787a8704f0f0b6`
Scope: QTX 4 PHP/JavaScript runtime, WordPress entry points, ACF and
WooCommerce adapters, tests, packaging boundaries and GitHub Actions supply
chain. Historical analysis remains in `SECURITY-AUDIT.md` and
`SECURITY-VALIDATION.md`.

## Executive verdict

- Confirmed open runtime Critical: **0**
- Confirmed open runtime High: **0**
- Confirmed open runtime Medium: **0**
- Confirmed open runtime Low: **0**
- Remediated runtime finding: **QTX4-SEC-001**
- Open architectural/hardening observations: **6**
- Build dependency advisories: **10** (6 High, 2 Moderate, 2 Low advisory
  ratings; development graph only)

The QTX4-SEC-001 runtime gate is **green**. Both configuration textarea sinks
now use late contextual escaping and reject non-scalar request shapes. The
release as a whole is still not security/release-green because the independent
development supply-chain and integration gates below remain incomplete.

No current source data flow demonstrates unauthenticated code execution, SQL
injection, arbitrary file inclusion/deletion, object injection, SSRF, open
redirect, authentication bypass or lower-privilege write escalation.

## Method and executed evidence

The audit independently enumerated WordPress actions, AJAX/nopriv handlers,
REST fields/filters, activation/uninstall hooks, direct request reads,
capability/nonce checks, SQL/filesystem/deserialization/redirect/process sinks,
PHP output, JavaScript DOM sinks, third-party trust delegation and CI inputs.

Original audit checks:

- PHPUnit PHP 8.4.16: **319 tests / 7897 assertions, PASS**;
- module-loader security runner: **PASS**;
- JavaScript parser/source security corpus: **PASS**;
- `git diff --check`: **PASS** before this report update;
- `npm audit --json`: **10 development dependency advisories**, zero critical.

Remediation checks on 2026-09-02:

- focused QTX4-SEC-001 regression on PHP 8.4.16: **9 tests / 37 assertions,
  PASS**;
- full PHPUnit on PHP 8.1.29, 8.2.29, 8.3.29, 8.4.16 and 8.5.9:
  **332 tests / 7948 assertions per runtime, PASS**;
- `composer test` on PHP 8.4.16: **332 tests / 7948 assertions, PASS**;
- PHP lint: **179 files, PASS**;
- JavaScript parser/security tests and `git diff --check`: **PASS**.

## Confirmed finding

### QTX4-SEC-001 — reflected XSS in Configuration Files textarea

Status: **RESOLVED on 2026-09-02**

Original severity: **Medium**. Exact-path validation reduced pre-fix
exploitability to **Low / defense in depth**; see the validation correction
below and `SECURITY-BATCH-QTX4-SEC-001.md`.

Affected code: `src/admin/admin_settings.php`, Configuration Files textarea.

Source: `$_POST['json_config_files']`.

Sink:

```php
<textarea ...><?php echo $_POST['json_config_files'] ?? ... ?></textarea>
```

The POST value was emitted without `esc_textarea()`. A raw value containing a
closing `</textarea>` token can leave the element and inject markup/script into
an otherwise unprotected textarea sink.

Exact validation corrected two details in the original finding. The page and
update handler both require `manage_options`. `qtranxf_verify_nonce()` calls
WordPress `check_admin_referer()` with an explicit action, so a missing or
invalid nonce terminates the request; the renderer also repeats this check.
For a valid submission, `qtranxf_edit_config()` applies `stripslashes()`,
`sanitize_text_field()`, path splitting and newline reconstruction before the
value can be rendered. The supplied closing-tag payload is stripped by that
current request path. The previously claimed nonce-free end-to-end reflection
was therefore not reproducible.

The root cause nevertheless remained valid: the output sink relied on input
sanitization and directly emitted both posted and stored fallback values. The
fix applies `esc_textarea()` as late as possible to Configuration Files and the
structurally identical Custom Configuration textarea, and rejects array-shaped
values before string operations. Storage and multilingual parsing are
unchanged. Regression coverage proves closing-textarea, SVG/script-like,
quotes, Unicode, URL-encoded and multilingual-marker inputs remain text rather
than executable markup.

## Historical finding status on QTX 4

| ID | Status | Revalidated evidence |
|---|---|---|
| QTX-SEC-001 admin CSRF | **RESOLVED** | Mutations are POST-only, `manage_options` gated, nonced and single-action validated. |
| QTX-SEC-002 notice AJAX | **RESOLVED** | Capability, AJAX nonce and normalized notice ID are enforced. |
| QTX-SEC-003 i18n path | **RESOLVED** | Canonical approved roots, containment, JSON extension, regular-file, size and schema bounds. |
| QTX-SEC-005 module traversal | **RESOLVED** | Authoritative registry plus canonical loader boundary; option keys cannot construct paths. |
| QTX-SEC-006 deserialization | **RESOLVED** | QTX-owned sink disables class hydration; no gadget path identified. |
| QTX-SEC-007 redirects | **RESOLVED plugin-side** | Canonical/configured host allowlist and scoped `wp_safe_redirect()`. |
| QTX-SEC-008 global REST mutation | **HARDENED / architectural debt** | Exact registered post routes, configured languages and mandatory revisions; native route permission remains authoritative. |
| QTX-SEC-009 SQL values | **RESOLVED for request values** | Values are prepared; remaining identifiers are internal or allowlisted. |
| QTX-SEC-010 output handling | **QTX4-SEC-001 RESOLVED; remaining hardening** | Both configuration textareas use `esc_textarea()` and scalar guards; other privileged DOM/output locations remain hardening observations. |
| QTX-SEC-011 DB splitter | **HARDENED** | Capability, nonce, allowlisted actions, backup confirmation and canonical SQL roots. |
| QTX-SEC-013 slug saves | **OPEN HARDENING** | Post hook accepts an absent plugin nonce, but requires matching core edit request and object capability; no standalone bypass confirmed. |
| QTX-SEC-014/015 debug | **OPEN HARDENING** | Read-only and capability-gated, but lacks a read nonce and returns broad configuration to an authorized admin. |
| QTX-SEC-016 cookies | **OPEN HARDENING** | Language cookies are not HttpOnly; no authentication or sensitive token is stored in them. |

## Entry-point conclusions

### Settings, AJAX and early request mutation

No `admin_post_nopriv_*` endpoint exists. Notice AJAX writes are protected.
Debug AJAX is capability-gated and read-only, although nonce and response
minimization remain desirable.

`qtranxf_collect_translations_posted()` runs early and recursively mutates
request fields. It does not persist or authorize a write; downstream WordPress
handlers remain authoritative. Invalid shapes can produce warnings or
request-local resource use, but no practical availability boundary crossing
was demonstrated under normal PHP input limits.

### REST and Gutenberg

The active editor interceptor checks exact registered post routes, configured
languages and optimistic revisions. It does not call a persistence API and
cannot bypass the route controller's permission callback. Its global filter
design remains fragile and should ultimately be replaced by the registered
field adapter after integration validation.

The opt-in `RegisteredPostRestFieldAdapter` checks object edit permission for
raw reads and writes, restricts fields, validates scalar payloads and requires
revisions. No raw exposure or unauthorized write was confirmed.

### ACF and WooCommerce

The custom ACF post-object field reproduces upstream authenticated and nopriv
AJAX hooks and delegates querying to ACF. Its nonce/query visibility boundary
therefore depends on the installed ACF version. Removing nopriv may break
frontend forms; validate upstream behavior for every claimed version.

QTX defines no custom unauthenticated WooCommerce write endpoint. Order
language uses WooCommerce CRUD for HPOS, technical identifiers are excluded
from translation and REST/AJAX authorization remains upstream-owned.

## Sink conclusions

### SQL and mass operations

No exploitable request-derived SQL injection was confirmed. Dynamic table
names are WordPress-owned; migration columns/actions are allowlisted; mutable
values use placeholders. Administrator mass conversion remains an availability
and data-integrity operation requiring backup confirmation, not remote SQLi.

### Files and deserialization

Dynamic modules are registry/canonical-path constrained. i18n JSON and SQL
inputs are bounded to approved roots. File deletion targets are fixed debug,
temporary language-test or generated split files. No path reaches the plugin
entry file. No production command execution or dynamic PHP evaluation sink was
found.

### Redirects, remote requests and secrets

Language redirects use WordPress safe redirect validation and an explicit host
allowlist. Runtime contains no `wp_remote_*`/cURL client and no embedded token,
password or private key.

## Build and CI supply-chain findings

These do not ship in the runtime ZIP, but affect trusted release production:

1. `npm audit` reports 10 development advisories: 6 High, 2 Moderate and
   2 Low. Affected paths include Babel transforms, AJV, brace-expansion,
   fast-uri, minimatch, picomatch, serialize-javascript,
   terser-webpack-plugin and webpack. There are no shipped application npm
   dependencies.
2. GitHub Actions use mutable major-version tags rather than immutable SHAs.
3. MySQL/Redis images use mutable tags.
4. WP-CLI uses `releases/latest` without a version/checksum; `redis-cache` is
   installed without a version pin.
5. No `composer.lock` exists, so an application-style Composer audit cannot
   establish a fixed graph. Composer dependencies are not bundled in the ZIP.

Treat these as release supply-chain work: update the development lock graph,
pin actions/images/downloads, verify checksums and regenerate/review committed
bundles before release.

## Release decision

QTX4-SEC-001 runtime security gate: **GREEN**. Overall release gate: **RED**.

Required before production promotion:

1. update vulnerable build dependencies and review bundle diffs;
2. pin CI supply-chain inputs;
3. run the outstanding MySQL/Redis WooCommerce integration workflow.

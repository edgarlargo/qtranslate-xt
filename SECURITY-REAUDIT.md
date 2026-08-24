# QTX 4 security re-audit

Date: 2026-08-24
Scope: current `modernisation` tree after Security Batches 1/2 and phases through P/N2. Historical conclusions remain in `SECURITY-AUDIT.md` and `SECURITY-VALIDATION.md`.

## Executive summary

- Confirmed open Critical: **0**
- Confirmed open High: **0**
- Confirmed open Medium: **0**
- Confirmed open Low: **0**
- Open potential/hardening observations: **6**
- Newly introduced confirmed vulnerabilities: **0**

The confirmed admin CSRF is closed. Module loading, i18n JSON loading, redirects and deserialization now have explicit trust boundaries. N2 also closes the low-risk admin-notice AJAX state change. No current data flow demonstrates unauthenticated site compromise or privilege escalation.

This source audit is not a substitute for real WordPress/ACF/WooCommerce testing. The legacy Gutenberg interceptor remains active while its safer registered-field replacement is opt-in. Replacing it is release-readiness work because its current scope is too broad, although original REST controller permissions still gate requests and no standalone exploit chain was confirmed.

## Historical finding status

| ID | Original classification | Current status | Evidence |
|---|---:|---|---|
| QTX-SEC-001 | Confirmed High | **RESOLVED** | POST-only state changes require `manage_options`, settings nonce and a validated single action. |
| QTX-SEC-002 | Confirmed Low | **RESOLVED in N2** | Notice AJAX now requires `manage_options`, `check_ajax_referer`, normalized scalar ID; caller sends generated nonce. |
| QTX-SEC-003 | Hardening Low | **HARDENED / RESOLVED** | `I18nConfigFilePolicy` requires JSON, approved canonical roots, containment, regular file, size bound and schema. |
| QTX-SEC-004 | Potential Medium | **NOT IN RUNTIME / HARDENING** | Development converter is excluded by packaging policy. |
| QTX-SEC-005 | Conditional Medium | **RESOLVED** | Authoritative registry/canonical loaders; option state cannot create paths. |
| QTX-SEC-006 | Hardening Low | **HARDENED / RESOLVED** | All qTranslate sinks use `allowed_classes=false`; no gadget path identified. |
| QTX-SEC-007 | Conditional Low | **RESOLVED plugin-side** | Canonical/configured host allowlist plus scoped `wp_safe_redirect`. |
| QTX-SEC-008 | Potential High | **OPEN ARCHITECTURAL HARDENING** | Legacy REST request mutation is global. Native route permission remains authoritative; no unauthorized write is confirmed. H2 replacement is not active pending real WP tests. |
| QTX-SEC-009 | Potential Medium | **HARDENED** | O1 parameterized option LIKE and slug values. Remaining table/column identifiers are internal/allowlisted. |
| QTX-SEC-010 | Potential Medium | **OPEN OUTPUT HARDENING** | Legacy templates/two JS `innerHTML` assignments trust capability-gated config/localization. No lesser-privilege write chain found. |
| QTX-SEC-011 | Potential Medium | **OPEN ADMIN HARDENING** | DB splitter remains a `manage_options` + settings-nonce operation; no lower privilege crossing shown. |
| QTX-SEC-012 | Informational | **BY DESIGN** | Parser preserves opaque HTML/malformed markers; sanitization and escaping are separate. |
| QTX-SEC-013 | Potential Medium | **HARDENING** | Slug save accepts absent plugin nonce, but writes require submitted language fields and object capability/core edit flow. |
| QTX-SEC-014 | Potential Low | **HARDENED BY REDUCTION** | Debug response removes sensitive configuration and does not directly echo raw request data. |
| QTX-SEC-015 | Potential Low | **HARDENING** | Debug AJAX is read-only and requires `manage_options`; nonce/JSON response remain desirable. |
| QTX-SEC-016 | Hardening Low | **OPEN HARDENING** | Language cookies are not HttpOnly; changing this requires client-compatibility testing. |
| QTX-SEC-017 | Informational | **UNCHANGED** | No remote-request or embedded-secret surface found. |
| QTX-SEC-018 | Hardening Low | **PARTIALLY RESOLVED** | Executable `strftime` and unsafe deserialization are gone; two legacy `FILTER_SANITIZE_STRING` uses remain. |

## Entry-point review

### Settings and AJAX

The main settings dispatcher is capability/nonce gated; state-changing language actions are POST-only. Import/export and mass conversion use the same `manage_options` flow. There are no qTranslate `admin_post_nopriv_*` handlers.

N2 changes `wp_ajax_qtranslate_admin_notice` from an authenticated unprotected option write to an authorized/nonced request. `wp_ajax_admin_debug_info` is read-only and returns data only to `manage_options`; use `wp_send_json` and a read nonce later for defense in depth.

### REST and Gutenberg

QTX 4 provides language/route policies, revision merges and `RegisteredPostRestFieldAdapter`. Structured raw data is edit-capability gated and writes use optimistic revisions. This adapter is not production-registered yet.

The active legacy interceptor reacts to `qtx_editor_lang` on all POST/PUT REST requests before callbacks. WordPress still runs the route's permission callback before persistence and the plugin does not bypass it. Global mutation can nevertheless corrupt unexpected route payloads. Replace it after real autosave/revision testing; this is POTENTIAL release debt, not a confirmed privilege bypass.

### ACF and WooCommerce

The custom ACF post-object field re-registers upstream `ajax_query` for authenticated and `nopriv` names. Nonce/query visibility is implemented by installed ACF and cannot be established from this repository. Removing `nopriv` risks frontend forms. Validate against claimed ACF Free/Pro versions.

No custom qTranslate WooCommerce AJAX/REST write endpoint exists. Technical metadata is preserved, order language is validated and broad cache flushes are removed. Upstream authentication/capabilities remain authoritative.

## Sink review

### Files and serialization

Dynamic PHP loading is limited to trusted registered providers with canonical boundaries. i18n JSON is root/extension/size/schema bounded. Admin DB-file splitting still accepts an administrator path and should gain approved roots, dry-run and output safeguards.

No production `unserialize()` remains outside `qtranxf_maybe_unserialize_safe()`, which disables class hydration. Parser code does not deserialize.

### Database and mass operations

Request-derived migration/slug values use placeholders. Dynamic WordPress table names and selected columns remain interpolated where placeholders do not apply. Full-table conversion is a data-integrity/availability risk, not demonstrated SQL injection: it requires administrator capability/nonce and uses prepared row updates. Add preview, backup guidance and bounded batches.

### Output and JavaScript

The parser intentionally does not sanitize inline payload. Field sanitation and contextual output escaping are separate. Legacy admin templates have direct echoes, but persisted language/config values require `manage_options` and are generally sanitized. Two `innerHTML` calls receive language names/localization; migrate to `textContent` after compatibility validation. No `eval` or dynamic `Function` sink was found.

### Redirects, requests and secrets

Language redirects use canonical/configured allowed hosts and `wp_safe_redirect`. The early `wp_redirect(get_option('home'))` target is a trusted WordPress option, not request input. No plugin `wp_remote_*`/cURL sink or embedded credential was found.

## N2 change

- `src/admin/admin_notices.php`: capability, nonce, scalar/ID validation, deterministic response.
- `js/notices.js`, `dist/notices.js`: generated nonce is sent.
- `tests/Unit/AdminNoticeAjaxSecurityTest.php`: guards the connected server/caller contract.

There is no known confirmed Critical/High in current source. Security alone does not authorize an RC: global Gutenberg interception, contextual output and required real ACF/WooCommerce matrices remain release work.

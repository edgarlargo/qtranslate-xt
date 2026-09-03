# QTX 4 final security re-audit

Date: 2026-09-03
Audited remediation commit: `7a0ca6553bddca329c5b871b589e75364551e59f`
Post-Woo-Blocks delta audited source: `1f6db22834dae1ae96a972da12dea6d1a9b08841`
Branch: `modernisation`

## Executive verdict

**PASS for release-candidate packaging.**

- Open confirmed Critical: **0**
- Open confirmed High: **0**
- Open confirmed Medium: **0**
- Open confirmed Low: **0**
- Open conditional/hardening observations: **4**, none release-blocking
- npm audit: **0 advisories**
- Composer audit of the installed test graph: **0 advisories**

The mandatory order was preserved for the original release gate. After a real
Cart/Checkout Blocks report required additional production code, the expanded
WooCommerce MySQL/Redis matrix passed and the new code received the delta
security re-audit below. The final ZIP must now be rebuilt and validated after
this delta audit before it is distributed.

## Current CI evidence

- PHP/JavaScript run
  [`33756895200`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33756895200):
  **PASS** on commit `7a0ca65`.
- PHP 8.1, 8.2, 8.3, 8.4 and 8.5: **345 tests / 8029 assertions**
  per runtime.
- Production syntax on PHP 7.4 and 8.0: **PASS**.
- Node 24.11.1: clean `npm ci --ignore-scripts`, npm audit, 3 JS tests,
  production build and committed-bundle reproducibility check: **PASS**.
- WooCommerce run
  [`33756895339`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33756895339):
  **PASS**, 173 assertions, WordPress 7.1, WooCommerce 11.0.1, PHP 8.4,
  MySQL 8.4.11, Redis 7.4.11 and Redis Object Cache 2.8.0 with HPOS enabled.
- Local module-loader security runner, `git diff --check`, Composer validation,
  npm audit and Composer audit: **PASS**.

## Post-Woo-Blocks delta security re-audit

Delta verdict: **PASS**. Open confirmed Critical/High/Medium/Low findings in
the delta: **0**.

The review covered `src/modules/woo-commerce/loader.php`,
`src/modules/woo-commerce/front.php`, `js/woocommerce-blocks/`, the generated
bundle, build entry and all associated PHP/JavaScript/integration regressions.

- The REST adapter matches only the exact `/wc/store/` prefix, adds no route or
  write operation, leaves native Woo permission/dispatch behavior intact and
  deliberately excludes authenticated `/wc/v3/` technical APIs.
- Server-side translation reuses the existing Woo presentation filters;
  technical metadata remains behind `WooCommerceDataPolicy`.
- Client configuration contains only configured language codes and the trusted
  bounded `QTX_LANG_CODE_FORMAT` constant. No request data becomes executable
  code or a regular-expression fragment.
- Dynamic output is restricted to `node.nodeValue` below Woo Cart, Checkout and
  Mini-Cart roots. Script/style/textarea/noscript/contenteditable nodes are
  excluded; there is no `innerHTML`, `outerHTML`, `insertAdjacentHTML`, eval,
  remote I/O, storage mutation or credential handling.
- Run
  [`33783568190`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33783568190)
  passed 347 tests / 8041 assertions on PHP 8.1–8.5, four JavaScript tests,
  lint, audits, production build and committed-bundle reproducibility. Run
  [`33783568249`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33783568249)
  passed the expanded 176-assertion WordPress 7.1/WooCommerce 11.0.1/MySQL
  8.4.11/Redis 7.4.11 matrix.

## Historical findings revalidated

| Finding | Current status | Current evidence |
|---|---|---|
| QTX-SEC-001 admin CSRF | **RESOLVED** | Mutations are POST-only, `manage_options` gated, nonced and single-action validated. |
| QTX-SEC-003 i18n-config path disclosure | **RESOLVED** | Canonical approved roots, containment, `.json`, regular-file, size and schema boundaries. |
| QTX-SEC-005 module traversal/include | **RESOLVED** | Built-in registry plus canonical loader containment; option keys cannot construct paths. |
| QTX-SEC-006 object injection | **RESOLVED** | QTX-owned deserialization uses `allowed_classes => false`; no QTX gadget hydration path remains. |
| QTX-SEC-007 language redirect | **RESOLVED plugin-side** | Every language redirect uses `wp_safe_redirect()` with canonical/configured hosts; permissive reverse-proxy routing remains an environment condition. |
| QTX4-SEC-001 settings textarea XSS | **RESOLVED** | Scalar guards and late `esc_textarea()` cover both configuration textareas. |

## Findings discovered by this re-audit

### QTX4-SEC-002 — legacy ACF field output escaping

Classification before remediation: **Medium / CONFIRMED**.  
Status: **RESOLVED**.

The legacy multilingual File field rendered attachment title, URL, filename,
size and invalid basic-uploader value without contextual escaping. The legacy
WYSIWYG field also relied on editor filters without an explicit
case-insensitive `</textarea` closing-tag guard. These are stored admin/editor
output boundaries and were release-blocking.

The adapter now uses `esc_url()`, `esc_html()` and `esc_attr()` at the exact
sinks, adds `noopener noreferrer` to the external file link, escapes every ACF
language-tab label/attribute, and applies the same targeted closing-tag
replacement used by the native WordPress editor. Inline multilingual storage
and rich-content formatting are unchanged. `AcfOutputEscapingContractTest`
protects the boundary.

### QTX4-SEC-003 — vulnerable development dependency graph

Classification before remediation: **Medium / CONFIRMED release-integrity**.  
Status: **RESOLVED**.

`npm audit` initially returned 11 development advisories: 7 High, 2 Moderate
and 2 Low. They did not ship in `node_modules`, but they participated in the
trusted production-bundle build. The lock graph was updated and now reports
zero advisories. CI installs it with `npm ci --ignore-scripts`, audits it,
rebuilds all bundles and rejects any uncommitted bundle difference.

### QTX4-SEC-004 — mutable CI supply-chain inputs

Classification before remediation: **Medium / CONFIRMED release-integrity**.  
Status: **RESOLVED**.

GitHub Actions, runner labels and MySQL/Redis service images were mutable.
Third-party actions now use exact 40-character commit SHAs, checkout does not
persist credentials, runners use `ubuntu-24.04`, and MySQL/Redis images use
exact SHA-256 digests. WP-CLI remains fail-closed behind its recorded SHA-256;
WordPress, WooCommerce and Redis Object Cache versions are explicit.

### QTX4-SEC-005 — debug AJAX read without endpoint nonce

Classification before remediation: **Low / HARDENING**.  
Status: **RESOLVED**.

The diagnostic endpoint was read-only and required `manage_options`, so no
cross-origin response disclosure was demonstrated. It nevertheless returned
broad configuration without an endpoint nonce. The handler now fails closed
on capability and `check_ajax_referer()`, the settings client sends the
localized nonce, and output uses `wp_send_json()`.

### QTX4-SEC-006 — slug post-save nonce accepted when absent

Classification before remediation: **Low / HARDENING**.  
Status: **RESOLVED** (historical QTX-SEC-013).

Core post-save authorization already constrained the hook, but QTX accepted an
absent plugin nonce. It now detects whether a multilingual slug mutation was
requested and then requires a scalar, unslashed, valid QTX nonce plus the exact
object edit capability. Requests without QTX slug fields remain no-ops.

### QTX4-SEC-007 — privileged DOM HTML sinks and direct fallback redirect

Classification before remediation: **Low / HARDENING**.  
Status: **RESOLVED**.

Language names and the “copy from” label used `innerHTML`; both now use
`textContent`. The early invalid-base redirect used `wp_redirect()` even though
the main language redirect was already safe; it now goes through the scoped
QTX safe-redirect policy.

## Entry-point and sink conclusions

### Admin, CSRF, AJAX and cookies

State-changing settings paths require `manage_options`, POST, a valid nonce and
an allowlisted action. Notice AJAX and diagnostic AJAX both require capability
and nonce. Slug post writes require object capability and QTX nonce. Language
cookies contain no authentication material and are Secure when configured,
HttpOnly and SameSite=Lax.

### REST and Gutenberg data integrity

The production interceptor operates only on registered `/wp/v2/<rest-base>/<id>`
and autosave routes, configured languages and edit-context raw data. Native
route permission callbacks remain authoritative. Writes require per-field
SHA-256 revisions; stale writes return HTTP 409 before mutation. The request is
only merged during the normal controller callback phase, and autosaves do not
overwrite the parent. The registered-field foundation independently enforces
object edit permission, field allowlists, scalar payloads and revisions.

### ACF and WooCommerce

ACF value translation remains restricted to the declared text-like field
types; technical values are untouched. The custom post-object AJAX handler
mirrors ACF's official authenticated/nopriv registration and delegates to
ACF's own version-specific nonce/field verification. No bypass was found.

WooCommerce defines no QTX unauthenticated write endpoint. Product/order
technical fields remain outside translation, order language uses WooCommerce
CRUD under HPOS, mail is captured before transport, and Woo REST/AJAX
authorization remains upstream-owned. The complete MySQL/Redis matrix is
green on the remediation commit.

### SQL, filesystem and deserialization

Request values reaching SQL use placeholders; table/column identifiers are
WordPress-owned or internal allowlists. Destructive conversion is capability,
nonce, action and backup-confirmation gated. i18n JSON and local SQL paths are
canonical and restricted to approved roots; SQL output is limited to adjacent
`.sql` variants. Module loading cannot be redirected by stored option keys.
QTX-owned unserialization cannot instantiate PHP classes.

### Redirects, remote I/O and secrets

Language redirects share the safe host policy. Runtime contains no QTX remote
HTTP client, command execution or dynamic evaluation sink. Repository scanning
found no private key, GitHub token, cloud access key or production credential.
CI credentials are generated/disposable and mail/payment traffic is local or
offline.

## Non-blocking conditional observations

| Observation | Classification | Release treatment |
|---|---|---|
| A permissive reverse proxy could accept an attacker-selected Host before WordPress. | **Low / CONDITIONAL** | Operational deployment constraint; plugin still uses safe redirect validation. |
| ACF post-object nopriv behavior depends on the installed ACF implementation. | **Informational / CONDITIONAL** | QTX mirrors and delegates to the official ACF nonce/field policy; retain version testing. |
| Term-slug hooks rely on the native WordPress taxonomy nonce/capability path. | **Low / HARDENING** | No standalone QTX endpoint or bypass demonstrated; post-slug fail-open path is fixed. |
| Interactive two-browser Gutenberg conflict UI and newer ACF Pro releases were not executed. | **Informational** | Compatibility claim is constrained; protocol/data-integrity tests are green. |

## Supply-chain and package boundary

The npm graph is development-only and excluded from the archive. Composer
packages are not bundled; `composer/installers` is bounded to supported major
versions and the installed test graph audits clean. A `composer.lock` is not
required as a shipped application graph because the release ZIP contains no
`vendor` directory. The final archive must be built with `git archive`, honor
`export-ignore`, contain one `qtranslate-xt/` root and be checked for excluded
tests, development files, credentials and private packages.

## Final decision

Final security gate: **PASS**.
Release-candidate ZIP permission: **GRANTED**, subject to exact-archive build,
fresh WordPress installation/activation, integrity inspection and preservation
tests against the exact ZIP bytes.

## Post-audit packaging result

The authorized fifth gate subsequently passed in GitHub Actions run
[`33758229929`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33758229929)
from source commit `b6a7aa7`. The exact installed and published ZIP has SHA-256
`c8bf5a59d6db98ad31cefc245a4edaf3a0f40a8ec45ff04ace5a24f09af02bc3`.

That artifact was later withdrawn after the real Cart/Checkout Blocks report.
The first replacement archive from run `33783568249` proved the corrected code
and exact-ZIP path but was built before the delta audit recorded above. It is
not the final distributable until the exact packaging job is rerun after this
audit and its bytes are documented.

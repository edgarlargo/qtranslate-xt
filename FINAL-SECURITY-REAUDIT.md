# QTX 4 final security re-audit

Date: 2026-09-04
Audited remediation commit: `7a0ca6553bddca329c5b871b589e75364551e59f`
Post-Woo-Blocks delta audited source: `1f6db22834dae1ae96a972da12dea6d1a9b08841`
CI reproducibility follow-up audited commit: `ef83e1effc3f60eb88c186ee0bb86371bbc30734`
Post-ACF-Options-Bridge delta audited source: `8782c8804217b17b49d3e6441b690f095a0b5858`
Expanded-Safe-Bridge-0.4 delta audited source: `eef319b985614aad7a37eb69de99f6b15294c247`
Post-ACF-frontend-fallback delta audited source: `8fa5f23621b22dbc0a2782326796b461b53bd713`
Post-Woo-core-block-bootstrap delta audited source: `26b49eef7b56418d74af3f90531a239c157d5172`
Post-exact-ZIP-HTTP-gate delta audited source: `4c7f928f49a1997f67895730099beecd095ffbd0`
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
security re-audit below. The final ZIP was subsequently rebuilt and validated
after this delta audit.

## Current CI evidence

- Current PHP/JavaScript run
  [`33879843135`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33879843135):
  **PASS** on audited source `4c7f928f49a1997f67895730099beecd095ffbd0`.
- PHP 8.1, 8.2, 8.3, 8.4 and 8.5: **355 tests / 8102 assertions**
  per runtime; PHP 7.4/8.0 production syntax, six Node tests, npm audit,
  production build and committed-bundle comparison: **PASS**.
- Current WooCommerce/exact-ZIP run
  [`33879843211`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33879843211):
  **PASS**, 176 assertions, WordPress 7.1, WooCommerce 11.0.1, PHP 8.4,
  MySQL 8.4.11, Redis 7.4.11, HPOS, exact-ZIP install/reactivation,
  LV/RU/EN HTTP routes, REST and Store API. Redis remained connected.

- PHP/JavaScript run
  [`33869856763`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856763):
  **PASS** on commit `f145b5c637d438e2c7c9df0b5a3d5ba27336a4e2`.
- PHP 8.1, 8.2, 8.3, 8.4 and 8.5: **349 tests / 8064 assertions**
  per runtime.
- Production syntax on PHP 7.4 and 8.0: **PASS**.
- Node 24: clean `npm ci --ignore-scripts`, npm audit, 6 JS tests,
  production build and committed-bundle reproducibility check: **PASS**.
- WooCommerce run
  [`33869856719`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856719):
  **PASS**, 176 assertions, WordPress 7.1, WooCommerce 11.0.1, PHP 8.4,
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
  [`33784794810`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33784794810)
  passed 347 tests / 8042 assertions on PHP 8.1–8.5, four JavaScript tests,
  lint, audits, production build and committed-bundle reproducibility. Run
  [`33784794846`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33784794846)
  passed the expanded 176-assertion WordPress 7.1/WooCommerce 11.0.1/MySQL
  8.4.11/Redis 7.4.11 matrix.

The first post-audit rerun exposed a nondeterministic Redis Object Cache test
prefix: WordPress's generated salt could contain Redis glob characters such as
`[` or `*`, preventing `flush_group()` from matching keys. Commit `ef83e1e`
sets `WP_REDIS_PREFIX` to the quoted, run-isolated value
`qtx-woo-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}:` and adds a workflow contract
assertion. Both interpolated values are GitHub-provided numeric run identifiers;
the prefix is not a credential, is confined to the disposable lab and contains
no shell or Redis glob metacharacters. This workflow-only follow-up adds no
runtime endpoint, package code, secret or production data path. Delta security
verdict remains **PASS** with no new finding.

## Post-ACF-Options-Bridge delta security re-audit

Delta verdict: **PASS**. Open confirmed Critical/High/Medium/Low findings in
the delta: **0**.

The review covered the new `js/acf/options-bridge.js`, its integration with
the existing ACF field lifecycle, CSS, generated production bundle, default
configuration change, JavaScript security regression and PHP source contract.

- The bridge runs only in the existing authenticated ACF admin asset context
  and registers no PHP/AJAX/REST route, remote request or storage operation.
- Language entries come from qTranslate-XT's server-produced configuration.
  Tabs use `document.createElement()`, `textContent`, DOM properties and event
  listeners; no string-built HTML sink is present.
- A tab is attached only when the standard ACF input has a real qTranslate
  content hook. Disabled field types do not receive a misleading UI.
- Initial fields and official ACF `new_field/type=*` lifecycle additions use
  the same idempotent bridge. Existing multilingual storage is unchanged.
- No `active_plugins` read/write, fake plugin basename, filesystem path,
  deserialization, SQL, command execution, credential or external resource was
  introduced.
- Enabling tabs by default affects only configurations without a saved explicit
  preference; an existing administrator choice remains authoritative.

Local Node execution passed five JavaScript tests and the production Webpack
bundle rebuilt successfully. Post-audit run `33786090026` then passed the ACF
contract in the 349-test / 8054-assertion PHP 8.1–8.5 matrix, five JavaScript
tests, audits and reproducible bundle build.

## Expanded Safe Bridge 0.4 delta security re-audit

Delta verdict: **PASS**. Open confirmed Critical/High/Medium/Low findings in
the expanded bridge delta: **0**.

The review covered source commit
`eef319b985614aad7a37eb69de99f6b15294c247`, including
`js/acf/options-bridge.js`, `js/acf/options-bridge-values.js`, ACF lifecycle and
configuration integration, CSS, the generated production bundle, tests and the
supplied standalone 0.4 reference implementation.

- The bridge creates no PHP/AJAX/REST endpoint, remote request, filesystem or
  database operation. It remains limited to authenticated ACF admin assets.
- All UI is created with `document.createElement()`, `textContent`, value and
  attribute properties. There is no `innerHTML`, `outerHTML`,
  `insertAdjacentHTML`, dynamic evaluation or script-bearing markup sink.
- Language identifiers originate in qTranslate-XT configuration and must match
  the bounded `[a-z0-9_-]{2,12}` policy before becoming a panel identifier or
  serialized language tag.
- Per-language editors have no form name. The existing original ACF input stays
  the sole named submission field and receives the canonical serialized value
  on every edit, so no parallel write endpoint or storage schema is introduced.
- The native parser handles bracket, comment and curly legacy values. Content
  for currently disabled languages is preserved during serialization, avoiding
  destructive language-configuration changes.
- Only ACF Text/Textarea fields already admitted by the existing field and
  post-type policy are enhanced. Technical fields, clone templates and
  unsupported field types remain outside the bridge.
- A live in-memory marker makes repeated callbacks idempotent. A serialized
  marker copied by ACF row cloning causes generated UI to be rebuilt with fresh
  event handlers. The standalone bridge marker is both recognized and emitted,
  preventing double UI regardless of script order during a transition.
- Older saved ACF settings are completed from safe defaults with
  `array_replace_recursive`; explicit administrator values remain authoritative.
  Neither code path reads or mutates `active_plugins`.

Local evidence is six passing JavaScript tests, including configured/disabled
language round-trip coverage, a reproducible production Webpack build and clean
`git diff --check`. PHP 7.4–8.5 and exact-archive evidence must follow this audit
before a new ZIP is designated.

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
and exact-ZIP path but was built before the delta audit recorded above and is
superseded. Post-audit run
[`33784794846`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33784794846)
then passed 176/176 assertions on source commit `46280f8`, built and inspected
the exact archive, installed and reactivated it in fresh WordPress 7.1, and
published it with Redis still connected. The final ZIP has SHA-256
`146209dd78de77fd14c32551d8048dc897f67a89f2c62dce548609ffb1263ab6`,
size 1,466,760 bytes, 1,138 entries, exactly one `qtranslate-xt/` root, the
Latvian MO file and Woo Blocks bundle present, and zero forbidden entries.
Those bytes predate the ACF Options Bridge and are now withdrawn.

The final post-ACF-bridge archive was built from
`f793c024b0cf48d086d15552ccfca872db6536d8` only after the delta audit. GitHub
Actions run
[`33786089998`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33786089998)
passed 176/176 assertions, built and inspected the archive, installed and
reactivated those exact bytes in fresh WordPress 7.1, reran the matrix and
confirmed Redis remained connected. Independent download verification matched
SHA-256 `53ca21bf862200a06f3ac69c014573ae94e1bd4645cbc6d2168a3958221d0868`,
size 1,467,442 bytes and 1,138 entries. The archive has one `qtranslate-xt/`
root, contains the Latvian MO, Woo Blocks and ACF bundles, and has zero
forbidden development/private/database/mail entries. Packaging gate: **PASS**.

Those post-ACF-bridge bytes predate the complete Safe Bridge 0.4 isolated-panel
behavior and are also withdrawn. After the expanded delta audit, companion run
[`33869856763`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856763)
passed 349 tests / 8064 assertions on PHP 8.1–8.5, six JavaScript tests, zero
npm advisories, production build and committed-bundle comparison. Final run
[`33869856719`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856719)
passed 176/176 WooCommerce assertions on source
`f145b5c637d438e2c7c9df0b5a3d5ba27336a4e2`, built and installed the exact
archive, and retained Redis connectivity. Independent download verification
matched SHA-256
`449209b7a6856a63426389dbe6d43f3df773fbf2fc26942d786f6e7d908b0047`,
size 1,469,533 bytes and 1,138 entries. It has one `qtranslate-xt/` root,
contains the Latvian MO, Woo Blocks and ACF bundles, and has zero forbidden
development/private/database/mail entries. Final packaging gate: **PASS**.

## 2026-09-04 ACF frontend fallback delta security re-audit

Delta verdict: **PASS**. Open confirmed Critical/High/Medium/Low findings in
the delta: **0**.

A real theme-embedded/legacy ACF Options path subsequently exposed the complete
inline marker string on the frontend. The `449209…b0047` archive is withdrawn.
The cause was the native adapter's dependency on legacy module state and field
metadata, which are not guaranteed for theme-embedded and older Options Pages.

The review covered `AcfSafeBridgeValueAdapter`, its core registration, exact
EN/LV/RU/FI/SV regression, source contract and PHPUnit bootstrap:

- The fallback is read-only and is limited to ACF's official type-specific
  `text`, `textarea` and `wysiwyg` format hooks at priority 99.
- It registers after language detection, uses the existing bounded qTranslate
  parser/selection function and does not depend on `active_plugins`, legacy
  module state or optional field metadata.
- Normal wp-admin requests retain raw storage for editing; frontend and admin
  AJAX reads receive only the selected language, matching Safe Bridge 0.4.0.
- Non-string and plain values are returned unchanged. If the earlier adapter
  already projected a value, the late fallback sees no `[:` marker and is a
  no-op.
- No HTML/DOM sink, SQL, deserialization, command execution, filesystem or
  remote I/O, credential, option mutation, AJAX/REST endpoint or global cache
  flush was introduced.

GitHub PHP/JavaScript run
[`33871964457`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33871964457)
passed PHP 7.4/8.0 production syntax, **353 tests / 8078 assertions** on each
PHP 8.1–8.5 runtime, six JavaScript tests, zero npm advisories, production build
and committed-bundle reproducibility. Required WooCommerce run
[`33871964443`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33871964443)
passed **176/176 assertions** with WordPress 7.1, WooCommerce 11.0.1, MySQL 8.4,
Redis 7.4.11, Redis Object Cache 2.8.0 and HPOS enabled. Security gates 3 and 4
are complete. Post-audit run
[`33872439659`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33872439659)
reconfirmed the PHP/JavaScript matrix, and exact-ZIP run
[`33872439685`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33872439685)
passed 176/176 assertions, archive inspection, fresh install/reactivation and
Redis connectivity. The independently downloaded ZIP matches SHA-256
`e9f53257b486fc6749b58f831b31d094a84cda61c8332e835e6e4663d96a53f7`,
size 1,470,737 bytes and 1,139 entries, with one `qtranslate-xt/` root, all
required Latvian/Woo/ACF files and zero forbidden entries. Gate 5: **PASS**.

## 2026-09-04 Woo core block bootstrap delta security re-audit

Delta verdict: **PASS**. Open confirmed Critical/High/Medium/Low findings in
the delta: **0**.

The review covered `WooCommerceBlocksAdapter`, its core initialization, removal
of the former module-scoped block bootstrap, the exact product-title regression
and the disposable workflow fixture.

- The adapter registers only `rest_pre_dispatch` and `wp_enqueue_scripts`. It
  creates no route, write endpoint, permission override or external request.
- REST activation is restricted to the exact `/wc/store/` prefix; authenticated
  `/wc/v3/` technical APIs remain outside the adapter.
- The included frontend file is a fixed plugin-owned path. No option, route or
  request value participates in filesystem selection.
- Reapplying the existing Woo presentation filter graph is idempotent and
  changes presentation strings only. SKU, price, stock, tax, IDs, quantities,
  payment identifiers and order data remain protected by existing policy.
- Client configuration contains only resolved language codes and the bounded
  language-code pattern. The existing client modifies text nodes only and has
  no HTML sink or dynamic execution.
- The production adapter does not read or mutate `active_plugins` or
  `qtranslate_modules_state`; independence from stale state is deliberate.
- The workflow-only fixture is WP-CLI guarded, uses the disposable CI database,
  creates and removes its product, performs no external payment/mail traffic
  and restores the module state before the full matrix.
- No SQL construction from request data, deserialization, command execution,
  credential, remote I/O, cache flush or new data-retention path was introduced.

PHP/JavaScript run
[`33873804508`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33873804508)
passed PHP 7.4/8.0 production syntax, **354 tests / 8090 assertions** on each PHP
8.1–8.5 runtime, six JavaScript tests, zero npm advisories, production build and
bundle reproducibility. WooCommerce run
[`33873804477`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33873804477)
first passed the exact Russian title with legacy Woo module state inactive,
then passed the complete **176/176** WordPress 7.1/WooCommerce 11.0.1/MySQL
8.4.11/Redis 7.4.11/HPOS matrix and retained Redis connectivity. Security gates
3 and 4 are complete. Post-audit PHP/JavaScript run
[`33874105427`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33874105427)
reconfirmed 354 tests / 8090 assertions and six JavaScript tests. Exact-ZIP run
[`33874105335`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33874105335)
reconfirmed the inactive-module title and 176/176 matrix, installed/reactivated
the exact archive and retained Redis connectivity. Independent verification
matched SHA-256
`19fa840eb4c7467d0d04df212122d253e383a8907882aa4fedc3e17d5d3bcc55`,
size 1,471,467 bytes and 1,140 entries, with one `qtranslate-xt/` root, all
required Latvian/Woo/ACF adapters and zero forbidden entries. Gate 5: **PASS**.

## 2026-09-04 exact-ZIP HTTP gate delta security re-audit

Audited source: `4c7f928f49a1997f67895730099beecd095ffbd0`.
Delta verdict: **PASS**. Open confirmed Critical/High/Medium/Low findings in
the delta: **0/0/0/0**. Confirmed exploitable findings: **0**.

No production PHP or JavaScript changed after the already audited Woo core
block bootstrap. The delta contains release documentation, a workflow-only
HTTP exercise, its localhost router and a source-contract test.

- The test server binds only to `127.0.0.1` in a disposable GitHub runner.
  `qtx.test` is resolved explicitly to that address and is never derived from
  request, repository or secret data.
- `home` and `siteurl` are changed only inside the disposable WordPress lab so
  canonical default-language redirects retain the test port. No production
  option, database or credential is accessed.
- The router is excluded from the release ZIP, has no persistence or remote
  client, and enters only the fixed WordPress `index.php` under the supplied
  document root. Its decoded request path is used solely to allow the built-in
  server to serve an existing static file; PHP execution remains WordPress'
  fixed front controller.
- Curl follows at most five redirects, fails closed, checks expected LV/RU/EN
  output and rejects raw `[:lv]` marker leakage. REST and the Woo Store API are
  read-only smoke requests in the isolated lab.
- Existing immutable action/image pins, checksum-verified WP-CLI, disposable
  administrator password, offline COD payment and captured-mail boundaries
  remain unchanged. The release archive still excludes workflow, tests,
  router, development dependencies and private data.

Historical findings remain: QTX-SEC-001 **RESOLVED**, QTX-SEC-003
**RESOLVED**, QTX-SEC-005 **RESOLVED**, QTX-SEC-006 **RESOLVED**,
QTX-SEC-007 **RESOLVED plugin-side**, and QTX4-SEC-001 **RESOLVED**.
ACF, Gutenberg and WooCommerce security/data-integrity verdicts remain
**PASS**. The production-only HTTP 500 report is a compatibility/release
incident pending its fatal stack trace; this audit found no security cause and
does not misclassify the unresolved deployment incident as fixed.

Security gates 3 and 4 are complete. Exact release-candidate construction and
installation may now run as gate 5; production designation remains subject to
the separately documented activation blocker.

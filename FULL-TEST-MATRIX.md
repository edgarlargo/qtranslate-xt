# QTX 4 full test matrix

Date: 2026-09-04
Branch: `modernisation`

Current release status: **BLOCKED**. A real installation returns HTTP 500 with
qTranslate-XT Modern active and recovers when the plugin is deactivated. The
previous exact ZIP is withdrawn pending the production fatal-error stack trace,
remediation and a complete rerun of the ordered release gates.

Status is limited to what was actually executed. `PASS` never means inferred
compatibility with an absent WordPress or third-party runtime.

## Automated local gates

| Gate | Status | Result |
|---|---|---|
| PHPUnit PHP 8.1–8.5 | **PASS CI** | run `33879843135`: 355 tests, 8102 assertions per runtime, 0 failures/errors |
| Production PHP lint 7.4.33 / 8.0.30 | **PASS** | Covers the WordPress 7.1 backward-compatible PHP floor |
| PHP syntax | **PASS** | all production PHP files, 0 lint failures |
| JavaScript shared corpus/security | **PASS local and CI** | 27 corpus cases plus 6 Node tests; ACF bridge value round-trip and text-only DOM assertions PASS |
| Webpack production build | **PASS** | Node 24.11.1 CI rebuild matched all committed bundles exactly |
| npm audit | **PASS** | 0 development/runtime advisories after lock-graph update |
| Composer audit | **PASS** | 0 advisories in installed test graph; Composer packages excluded from ZIP |
| `git diff --check` | **PASS** | no whitespace errors |
| Module loader traversal regression | **PASS** | registry, traversal, wrapper, absolute/unknown/corrupt-state cases covered |
| Exact-ZIP HTTP language/REST routes | **PASS local and CI** | local actual-theme lab passed; run `33879843211` passed LV/RU/EN, raw-marker rejection, REST and Store API against the exact installed ZIP |
| Real production activation | **FAIL / BLOCKER** | production HTTP 500 only while qTranslate-XT Modern is active; exact PHP fatal/stack trace not yet available |

Release CI uses exact Node 24.11.1, installs the lock graph with lifecycle
scripts disabled, audits it, rebuilds production assets and fails on any bundle
drift. Third-party Actions and MySQL/Redis service images are immutable SHAs or
digests. The local Node 18.14 engine warning is therefore not release evidence.

## Parser and core

| Scenario | Status | Evidence |
|---|---|---|
| Legacy characterization corpus | **PASS** | shared 27-case corpus |
| QTX 4 parser/detector/builder | **PASS** | unit and differential tests |
| Bracket/comment/curly syntax | **PASS** | lossless/parser-builder tests |
| Malformed/duplicate/adjacent markers | **PASS** | characterization and merge rejection tests |
| Unicode and HTML payloads | **PASS** | opaque-content corpus tests |
| Large content and parser cache | **PASS** | generated corpus/performance tests |
| PHP/JavaScript semantic parity | **PASS** | shared corpus projection/token assertions |

## WordPress and integrations

| Area | Status | Reason/evidence |
|---|---|---|
| Clean WordPress activation/deactivation | **PASS** | WordPress 7.1 / PHP 8.4 / SQLite lab |
| Settings/posts/options/metadata/taxonomy | **NOT TESTED** end-to-end | core/unit characterization PASS; real WP lifecycle absent |
| Query/path/domain/domains URL modes | **NOT TESTED** end-to-end | unit/source redirect policy PASS |
| Multisite/reverse proxy | **NOT TESTED** | infrastructure absent |
| Classic Editor | **NOT TESTED** end-to-end | legacy runtime retained |
| REST GET/POST/PUT permissions/raw exposure | **NOT TESTED** end-to-end | policy/adapter unit tests PASS |
| Gutenberg REST edit/save | **PASS** | authenticated real WordPress controller test |
| Gutenberg autosave/revision conflict | **PASS protocol** | autosave preserved parent; stale save returned 409; interactive UI message still NOT TESTED |

## ACF required matrix

| Scenario | Status |
|---|---|
| ACF Free lifecycle/detection | **PASS smoke — 6.8.8** |
| ACF Pro lifecycle/detection | **PASS smoke — 5.7.7** |
| Theme-bundled/non-standard ACF | **PASS detection — 6.8.8** |
| Options API (`option`/`options`) | **PASS ACF Free 6.8.8 and Pro Options Page 5.7.7 runtime/storage** |
| Text/Textarea/WYSIWYG frontend LV/RU/EN | **PASS — ACF Free 6.8.8 and Pro 5.7.7; interactive browser JS NOT EXECUTED** |
| Group/Repeater/Flexible Content | **PASS native runtime — ACF Pro 5.7.7** |
| Built-in ACF Options language panels | **PASS CI and exact ZIP** — isolated Text/Textarea editors, initial/dynamic ACF 5.x/6.x fields, disabled-language preservation, no `active_plugins` mutation; priority-99 fallback has the exact reported EN/LV/RU/FI/SV regression; interactive live-theme test NOT EXECUTED |

Repository unit tests for runtime capability detection, field schema,
projection/merge, stable field keys, nested leaves, dynamic ACF JS actions and
conflicts are **PASS**. They do not replace execution against ACF distributions.
The supplied ACF Pro 5.7.7 package passed the native runner. Newer ACF Pro
versions are not covered by that result.

## WooCommerce required matrix

| Scenario | Status |
|---|---|
| Products/categories/attributes/variations | **PASS — LV/RU/EN; tags outside required gate remain NOT TESTED** |
| Permalinks/slugs/canonical URLs | **NOT TESTED; outside required transactional gate** |
| Classic cart/AJAX/fragments/variation selection | **PASS** |
| Cart/Checkout Blocks Store API and dynamic labels | **PASS CI AND EXACT ZIP** — core registration independent from legacy Woo module state; exact Russian product-title regression PASS |
| Classic checkout/order review/offline COD | **PASS** |
| Orders/customer language/HPOS/emails | **PASS** |
| WooCommerce REST | **PASS — authenticated products/variations/orders** |
| Cache behavior on Redis | **PASS — isolation, group invalidation, no global flush** |

Data-policy and cache-boundary unit/source tests are **PASS**. WooCommerce
claims are limited to the installed 11.0.1 transactional matrix;
`WOOCOMMERCE-COMPATIBILITY.md` makes no broader version claim.

The final disposable workflow and fail-closed runner passed after the expanded
Safe Bridge delta audit on 2026-09-04 in GitHub Actions run
[`33869856719`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856719),
commit `f145b5c637d438e2c7c9df0b5a3d5ba27336a4e2`: **176/176 assertions**, WordPress 7.1, WooCommerce 11.0.1,
PHP 8.4, MySQL 8.4.11, Redis 7.4.11 and Redis Object Cache 2.8.0. HPOS was
enabled and order language used WooCommerce CRUD. After the delta security
audit, the job built, inspected, installed, reactivated and published the exact
final archive. Companion run
[`33869856763`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856763)
passed 349 tests / 8064 assertions on PHP 8.1–8.5, six JavaScript tests,
audits, build and committed-bundle reproducibility.

The current incident-cycle rerun is
[`33879843211`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33879843211)
on `4c7f928`: **176/176 assertions** with the same pinned stack, plus exact-ZIP
install/reactivation, LV/RU/EN HTTP projection, raw-marker rejection,
WordPress REST, Store API and connected Redis. Companion run
[`33879843135`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33879843135)
passes 355 tests / 8102 assertions on PHP 8.1–8.5 and all JavaScript, lint,
audit and bundle checks. The following delta security audit passes; the
production-only HTTP 500 remains a separate blocker pending its fatal trace.

Historical release-gate attempt on 2026-09-02: QTX4-SEC-001 was confirmed resolved first;
`actionlint` 1.7.12 returned zero findings for the Woo workflow. The local
`modernisation` branch is still absent from `origin`, and both signed-out GitHub
UI state and a non-interactive push check confirmed that no authenticated
dispatch path is available. A disposable local MySQL 8.0.31 instance started
successfully, but the WordPress 7.1 archive could not be represented on the
Windows filesystem because of a trailing-dot filename; the lab was shut down
and removed. The required Ubuntu MySQL 8.4/Redis 7.4 matrix was **NOT RUN** at
that checkpoint.

The retry also found that the original WP-CLI release-asset URL returned 404.
The workflow now downloads the official WP-CLI 2.12.0 build, verifies its
recorded SHA-256 before execution, and pins Redis Object Cache 2.8.0. Three
workflow contract tests (15 assertions) and the full PHP 8.1-8.5 suite at
335 tests / 7963 assertions passed at that checkpoint. This repaired
provisioning before the later successful Actions job.

Exact-ZIP checkpoint: `4.0.0-rc1` installed and activated on a second fresh
WordPress 7.1 lab; LV/RU/EN HTTP frontend, ACF Pro 5.7.7 native runner and
deactivate/reactivate raw-data preservation passed. Woo checkout reached stock
reservation and was stopped by SQLite's inability to execute WooCommerce's
MySQL locking/interval SQL; this does not promote the transaction row to PASS.

## Security

| Scenario | Status |
|---|---|
| QTX-SEC-001 admin CSRF regression | **PASS** |
| QTX-SEC-002 notice AJAX nonce/capability | **PASS** source contract; real AJAX **NOT TESTED** |
| QTX-SEC-003 JSON path/schema boundary | **PASS** |
| QTX-SEC-005 module traversal | **PASS** |
| QTX-SEC-006 class-disabled unserialization | **PASS** |
| QTX-SEC-007 allowed-host redirect policy | **PASS** unit/source; proxy **NOT TESTED** |
| QTX4-SEC-001 configuration textarea escaping | **PASS** — 9 focused tests / 37 assertions; full PHP 8.1-8.5 suite green |
| ACF legacy attachment/WYSIWYG output escaping | **PASS** — contextual sink and closing-textarea contracts |
| Admin debug AJAX capability/nonce | **PASS** — fail-closed source/client contract |
| Slug post-save capability/nonce | **PASS** — missing/invalid QTX nonce cannot mutate |
| CI/build supply-chain immutability | **PASS** — exact actions/images, zero audits, reproducible bundle build |
| REST object permissions/raw exposure | **PASS** unit and historical authenticated WordPress controller test |
| ACF AJAX upstream nonce/visibility | **PASS delegated boundary** — official ACF nopriv registration and nonce/field verification retained |
| WooCommerce AJAX/REST upstream permissions | **PASS required matrix** — authenticated REST and upstream AJAX lifecycle |

The final re-audit has zero confirmed open Critical, High, Medium or Low
findings. Conditional/hardening observations are non-blocking and explicitly
listed in `FINAL-SECURITY-REAUDIT.md`.

## Q phase conclusion

A real Cart/Checkout Blocks report invalidated the previous broad cart claim
and the associated archive. The Store API/frontend-filter and dynamic text
adapter remediation passes the expanded MySQL/Redis CI, and its delta security
re-audit is **PASS**. The first built-in ACF Options Bridge passed its delta
gates. Its later expansion to the complete standalone 0.4 isolated-panel
behavior passed its previous local/CI gates. A later real-site report exposed
raw ACF markers through a theme-embedded/legacy Options path that did not have
the module adapter. A standalone-compatible priority-99 core fallback and an
EN/LV/RU/FI/SV regression were added; delta audit, PHP CI and WooCommerce CI
passed in runs `33871964457` and `33871964443`. Replacement post-audit runs
`33872439659` and `33872439685` passed, including exact-ZIP installation and
Redis connectivity. The previous `449209…b0047`
archive is withdrawn.

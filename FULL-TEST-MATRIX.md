# QTX 4 full test matrix

Date: 2026-08-24
Branch: `modernisation`

Status is limited to what was actually executed. `PASS` never means inferred
compatibility with an absent WordPress or third-party runtime.

## Automated local gates

| Gate | Status | Result |
|---|---|---|
| PHPUnit PHP 8.1.29 | **PASS** | 335 tests, 7963 assertions, 0 failures/errors |
| PHPUnit PHP 8.2.29 | **PASS** | 335 tests, 7963 assertions, 0 failures/errors |
| PHPUnit PHP 8.3.29 | **PASS** | 335 tests, 7963 assertions, 0 failures/errors |
| PHPUnit PHP 8.4.16 | **PASS** | 335 tests, 7963 assertions, 0 failures/errors |
| PHPUnit PHP 8.5.9 | **PASS** | 335 tests, 7963 assertions, 0 failures/errors |
| Production PHP lint 7.4.33 / 8.0.30 | **PASS** | Covers the WordPress 7.1 backward-compatible PHP floor |
| PHP syntax | **PASS** | 180 plugin/test PHP files, 0 lint failures |
| JavaScript shared corpus | **PASS** | 27/27 cases; parser security assertion PASS |
| Webpack production build | **PASS** | `core`, ACF, options, block-editor and notices bundles emitted |
| `git diff --check` | **PASS** | no whitespace errors |
| Module loader traversal regression | **PASS** | registry, traversal, wrapper, absolute/unknown/corrupt-state cases covered |

Build environment note: Node 18.14.1 is below `babel-loader@10`'s declared
recommended engine floor (18.20). Build and tests passed, but release CI should
use Node 20 LTS or newer. `npm ci` reported 10 vulnerabilities in development
dependencies (2 low, 2 moderate, 6 high); they are not bundled PHP runtime
dependencies and were not auto-upgraded in this compatibility batch.

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

Repository unit tests for runtime capability detection, field schema,
projection/merge, stable field keys, nested leaves, dynamic ACF JS actions and
conflicts are **PASS**. They do not replace execution against ACF distributions.
The supplied ACF Pro 5.7.7 package passed the native runner. Newer ACF Pro
versions are not covered by that result.

## WooCommerce required matrix

| Scenario | Status |
|---|---|
| Products/categories/tags/attributes/variations | **PASS product smoke; categories/attributes/variations BLOCKED for RC** |
| Permalinks/slugs/canonical URLs | **BLOCKED for RC** |
| Cart/AJAX/fragments/variation selection | **PASS cart/fragments smoke; variation selection BLOCKED for RC** |
| Checkout/order review/gateways | **BLOCKED for RC** |
| Orders/customer language/emails | **BLOCKED for RC** |
| WooCommerce REST | **BLOCKED for RC** |
| Cache behavior on real backends | **BLOCKED for RC** |

Data-policy and cache-boundary unit/source tests are **PASS**. No WooCommerce
broader behavior is limited to the installed WooCommerce 11.0.1 smoke matrix;
`WOOCOMMERCE-COMPATIBILITY.md` makes no claim beyond it.

The full disposable workflow and fail-closed runner now exist, but have not
been dispatched because this checkout has no authenticated GitHub CLI/control
surface and the new workflow is not present on the remote. Its result is
**NOT RUN**. HPOS order-language CRUD regression coverage is PASS locally.

Release-gate retry on 2026-09-02: QTX4-SEC-001 was confirmed resolved first;
`actionlint` 1.7.12 returned zero findings for the Woo workflow. The local
`modernisation` branch is still absent from `origin`, and both signed-out GitHub
UI state and a non-interactive push check confirmed that no authenticated
dispatch path is available. A disposable local MySQL 8.0.31 instance started
successfully, but the WordPress 7.1 archive could not be represented on the
Windows filesystem because of a trailing-dot filename; the lab was shut down
and removed. The required Ubuntu MySQL 8.4/Redis 7.4 matrix remains **NOT RUN**.

The retry also found that the original WP-CLI release-asset URL returned 404.
The workflow now downloads the official WP-CLI 2.12.0 build, verifies its
recorded SHA-256 before execution, and pins Redis Object Cache 2.8.0. Three
workflow contract tests (15 assertions) and the full PHP 8.1-8.5 suite at
335 tests / 7963 assertions pass. This repairs provisioning but is not a
substitute for the unexecuted Actions job.

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
| REST object permissions/raw exposure | **PASS** unit; real routes **NOT TESTED** |
| ACF AJAX upstream nonce/visibility | **BLOCKED for RC** |
| WooCommerce AJAX/REST upstream permissions | **BLOCKED for RC** |

Current re-audit has zero confirmed open Critical/High findings. Potential
Gutenberg scope/output/third-party lifecycle issues remain release work.

## Q phase conclusion

All locally executable quality gates are green. The ACF Pro package blocker is
resolved for 5.7.7. The release quality gate remains **NOT GREEN** because the
required comprehensive WooCommerce matrix is incomplete. Producing an RC ZIP
would still misrepresent the stated release criteria.

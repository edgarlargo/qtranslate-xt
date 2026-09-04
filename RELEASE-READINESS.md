# QTX 4 release readiness

Date: 2026-09-04
Decision: **NOT READY — PRODUCTION ACTIVATION BLOCKER OPEN**

## Current production incident

The ZIP previously designated below is withdrawn. On the real
`edgarlargo.com` installation the site returned HTTP 500 while qTranslate-XT
Modern was active and recovered immediately after deactivation. Raw language
markers displayed with the plugin disabled, confirming that stored multilingual
content remains present.

The same exact ZIP passes activation and HTTP language routes locally with
WordPress 7.1, PHP 8.4.16, WooCommerce 11.0.1 and the user's actual
`edgarlargo` theme with embedded ACF Pro 6.3.3. This makes the production
fatal-error line and stack trace mandatory evidence; the cause must not be
guessed from the generic Apache 500 page.

A new exact-ZIP HTTP gate has been added for LV/RU/EN output, raw-marker
leakage, WordPress REST and Woo Store API. It passes in the local disposable
lab, but GitHub CLI authentication is currently invalid and the updated gate
has not run in CI. After the production defect is fixed, gates 2 through 5 must
be rerun in order. No current ZIP is approved for release.

## Mandatory release gates

The table below records the last completed historical cycle. Its final ZIP is
now **WITHDRAWN** by the later production incident and is not current release
approval.

| Order | Gate | Result |
|---:|---|---|
| 1 | Resolve QTX4-SEC-001 | **PASS** — scalar guards, `esc_textarea()`, focused regressions |
| 2 | WooCommerce MySQL/Redis CI | **PASS** — run `33873804477`; inactive-module regression plus 176/176 matrix |
| 3 | Final security re-audit | **PASS** — Woo core-bootstrap delta audited at `26b49ee`; zero confirmed findings |
| 4 | Fix release-blocking re-audit findings | **PASS** — delta found no release-blocking finding |
| 5 | Build and validate final RC ZIP | **PASS** — run `33874105335`; exact downloaded bytes independently verified |

The gates were executed in the required order. A later real-site Cart/Checkout
Blocks report exposed a missing Store API path in the original Woo matrix, so
that artifact was withdrawn. The Store API and dynamic block-label remediation
now passes the expanded matrix and its delta security re-audit. The exact ZIP
job was rerun after that audit. The later ACF Options Bridge integration passed
its own delta security review, full CI and a new exact-ZIP validation.

## Current automated evidence

- GitHub PHP/JavaScript run
  [`33874105427`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33874105427):
  **PASS** on post-audit commit `a9a1a6713876c0e5c736100150c3218baa358bc9`.
- PHP 8.1–8.5: **354 tests / 8090 assertions** on every runtime.
- PHP 7.4/8.0 production lint: **PASS**.
- Node 24: `npm ci --ignore-scripts`, audit, six JavaScript tests,
  production build and exact committed-bundle comparison: **PASS**, zero
  advisories.
- Composer manifest validation and installed-graph audit: **PASS**, zero
  advisories.
- Module-loader security runner and `git diff --check`: **PASS**.
- GitHub WooCommerce run
  [`33874105335`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33874105335):
  **PASS**, 176 assertions, WordPress 7.1, WooCommerce 11.0.1, PHP 8.4,
  MySQL 8.4.11, Redis 7.4.11, Redis Object Cache 2.8.0 and HPOS. The same job
  first passed the inactive-module Russian product-title regression, then built,
  inspected, installed, reactivated and published the designated exact ZIP.

## Security decision

The final audit found and fixed release-integrity and output-hardening defects:

- legacy ACF attachment/editor output escaping;
- vulnerable npm development lock graph;
- mutable GitHub Actions and service-container inputs;
- diagnostic AJAX nonce;
- slug post-save fail-open nonce behavior;
- language-label DOM HTML sinks;
- the remaining direct language redirect sink.

Open confirmed Critical/High/Medium/Low findings: **0**. Non-blocking
conditional observations are recorded in `FINAL-SECURITY-REAUDIT.md`.

## Withdrawn post-Woo-product-title-fix ZIP

- File: `build/qtranslate-xt-4.0.0-rc1.zip`
- Source commit: `a9a1a6713876c0e5c736100150c3218baa358bc9`
- PHP/JavaScript workflow: `33874105427` — **PASS**
- WooCommerce/exact-ZIP workflow: `33874105335` — **PASS**, inactive-module regression plus 176/176
- SHA-256: `19fa840eb4c7467d0d04df212122d253e383a8907882aa4fedc3e17d5d3bcc55`
- Size: **1,471,467 bytes**
- ZIP entries: **1,140**
- Top-level root: exactly `qtranslate-xt/`
- Latvian MO, Woo Blocks bundle, ACF bundle, `AcfSafeBridgeValueAdapter.php`
  and `WooCommerceBlocksAdapter.php`: **present**
- Forbidden development/private/database/mail content: **0 entries**

Withdrawal reason: real-site HTTP 500 with qTranslate-XT Modern active. These
bytes remain useful as reproducible evidence but are not designated for staging
or distribution.

The workflow built with `git archive`, inspected, installed, activated and
exercised these exact bytes in fresh WordPress 7.1. It explicitly set the legacy
Woo module inactive and verified the reported Russian title before restoring
the state and running the full matrix. Independent SHA/entry inspection matches
the pre-upload values; Redis remained connected.

## Integration evidence retained

- WordPress 7.1/PHP 8.4 activation/deactivation and LV/RU/EN HTTP frontend:
  **PASS** in the disposable integration lab.
- Gutenberg authenticated REST edit/save, autosave parent preservation and
  stale HTTP 409 conflict: **PASS**; current source/unit regression remains
  green. Interactive two-browser UI conflict messaging was not executed.
- ACF Free 6.8.8 plugin/theme-bundled runtime and scalar value matrix:
  **PASS**.
- ACF Pro 5.7.7 Options Page, Group, Repeater and Flexible Content native
  runtime/storage matrix: **PASS**. Newer Pro versions are not inferred.
- Woo product/cart/checkout/order/HPOS/email/REST/AJAX/cache matrix:
  **PASS** on the current remediation commit.

## Withdrawn pre-ACF-frontend-fix ZIP

- File: `build/qtranslate-xt-4.0.0-rc1.zip`
- Source commit: `f145b5c637d438e2c7c9df0b5a3d5ba27336a4e2`
- CI workflow: `33869856719`
- SHA-256: `449209b7a6856a63426389dbe6d43f3df773fbf2fc26942d786f6e7d908b0047`
- Size: **1,469,533 bytes**
- ZIP entries: **1,138**
- Top-level root: exactly `qtranslate-xt/`
- Required `lang/qtranslate-lv.mo`, `dist/woocommerce-blocks.js` and
  `dist/modules/acf.js`: **present**
- Forbidden development/private/database/mail content: **0 entries**

These exact bytes passed the recorded automated gates, but the subsequent
real-site report proved that a theme-embedded/legacy ACF Options value could
still expose its complete `[:lang]` storage string. They are withdrawn. A new
archive may be designated only after the priority-99 standalone-compatible
fallback passes its delta audit, PHP/JavaScript CI, WooCommerce CI and exact
archive installation.

## Withdrawn pre-expanded-bridge ZIP

- File: `build/qtranslate-xt-4.0.0-rc1.zip`
- Source commit: `f793c024b0cf48d086d15552ccfca872db6536d8`
- CI workflow: `33786089998`
- SHA-256: `53ca21bf862200a06f3ac69c014573ae94e1bd4645cbc6d2168a3958221d0868`
- Size: **1,467,442 bytes**
- ZIP entries: **1,138**
- Top-level root: exactly `qtranslate-xt/`
- Required `lang/qtranslate-lv.mo`, `dist/woocommerce-blocks.js` and
  `dist/modules/acf.js`: **present**
- Forbidden development/private/database/mail content: **0 entries**

These previously designated bytes were built by `git archive`, installed and activated in a
second disposable WordPress 7.1 site, exercised against the WooCommerce matrix,
downloaded independently and matched to the raw SHA-256 printed before artifact
upload. Redis remained connected after the archive test. They are now withdrawn
because they predate the complete isolated-panel behavior imported from Safe
Bridge 0.4.0.

## Withdrawn pre-ACF-bridge ZIP

These bytes were built and validated after the Woo Blocks delta security audit
but predate the built-in ACF Options Bridge. They are withdrawn.

- Source commit: `46280f86a0a03241070f4dada69b04dc1a43ff14`
- CI workflow: `33784794846`
- SHA-256: `146209dd78de77fd14c32551d8048dc897f67a89f2c62dce548609ffb1263ab6`
- Size: **1,466,760 bytes**
- ZIP entries: **1,138**
- Top-level root: exactly `qtranslate-xt/`
- `lang/qtranslate-lv.mo`: present
- Forbidden development/private/database/mail content: **0 entries**

The exact published bytes:

1. were built with `git archive` from source commit `46280f8`;
2. identify as `4.0.0-rc1` in the plugin header and `QTX_VERSION`;
3. were installed and activated in disposable WordPress 7.1;
4. preserved exact raw multilingual storage through deactivate/reactivate;
5. projected the expected Latvian, Russian and English title/content;
6. retained a connected Redis object-cache backend;
7. were downloaded from the successful workflow and matched its SHA-256.

The earlier archive from `b6a7aa7` with SHA-256
`c8bf5a59d6db98ad31cefc245a4edaf3a0f40a8ec45ff04ace5a24f09af02bc3`
remains withdrawn and must not be distributed.
The pre-delta-audit replacement with SHA-256
`0a2b9c9b1bf118c9fd846fd4e5e97c7eaab107f07ae6bd07fcee0344aba8c367`
is also superseded and must not be distributed.

## Constrained compatibility claims

- WooCommerce claims apply to 11.0.1 and the required matrix; tags,
  permalink/canonical behavior and REST writes were outside that matrix.
- Classic Editor and interactive ACF/Gutenberg browser switching were not
  re-executed as full browser automation.
- Optional SEO/forms/Jetpack/Site Kit/Events integrations remain limited to
  their documented source/runtime evidence.
- A permissive reverse proxy remains an operational host-validation risk even
  though plugin redirect sinks are safe.

## Upgrade and rollback

Follow `UPGRADE.md`: take complete database/files backups, validate on staging,
do not run database conversion automatically, and retain the previous plugin
directory plus database backup for rollback.

No ZIP identified in this document is currently designated for staging or
distribution. The hashes remain only to distinguish historical artifacts and
must not be treated as current release approval.

# QTX 4 release readiness

Date: 2026-09-03
Decision: **NOT READY — POST-AUDIT EXACT-ZIP RERUN REQUIRED**

## Mandatory release gates

| Order | Gate | Result |
|---:|---|---|
| 1 | Resolve QTX4-SEC-001 | **PASS** — scalar guards, `esc_textarea()`, focused regressions |
| 2 | WooCommerce MySQL/Redis CI | **PASS** — run `33783568249`, 176/176 assertions |
| 3 | Final security re-audit | **PASS** — `FINAL-SECURITY-REAUDIT.md` |
| 4 | Fix release-blocking re-audit findings | **PASS** — remediation commit `7a0ca65` |
| 5 | Build and validate final RC ZIP | **PENDING** — rebuild after the Woo Blocks delta security re-audit |

The gates were executed in the required order. A later real-site Cart/Checkout
Blocks report exposed a missing Store API path in the original Woo matrix, so
that artifact was withdrawn. The Store API and dynamic block-label remediation
now passes the expanded matrix and its delta security re-audit. The exact ZIP
job must be rerun after that audit before the replacement is designated final.

## Current automated evidence

- GitHub PHP/JavaScript run
  [`33783568190`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33783568190):
  **PASS** on `1f6db22`.
- PHP 8.1–8.5: **347 tests / 8041 assertions** on every runtime.
- PHP 7.4/8.0 production lint: **PASS**.
- Node 24.11.1: `npm ci --ignore-scripts`, audit, JS tests, production build
  and exact committed-bundle comparison: **PASS**, zero advisories.
- Composer manifest validation and installed-graph audit: **PASS**, zero
  advisories.
- Module-loader security runner and `git diff --check`: **PASS**.
- GitHub WooCommerce run
  [`33783568249`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33783568249):
  **PASS**, 176 assertions, WordPress 7.1, WooCommerce 11.0.1, PHP 8.4,
  MySQL 8.4.11, Redis 7.4.11, Redis Object Cache 2.8.0 and HPOS. The same job
  built, installed, reactivated and published the exact final ZIP.

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

## Pre-audit replacement ZIP evidence

The following bytes passed exact installation checks but were built before the
Woo Blocks delta audit. They are evidence only until the post-audit rerun.

- Source commit: `1f6db22834dae1ae96a972da12dea6d1a9b08841`
- CI workflow: `33783568249`
- SHA-256: `0a2b9c9b1bf118c9fd846fd4e5e97c7eaab107f07ae6bd07fcee0344aba8c367`
- Size: **1,466,760 bytes**
- ZIP entries: **1,138**
- Top-level root: exactly `qtranslate-xt/`
- `lang/qtranslate-lv.mo`: present
- Forbidden development/private/database/mail content: **0 entries**

The exact published bytes:

1. were built with `git archive` from source commit `1f6db22`;
2. identify as `4.0.0-rc1` in the plugin header and `QTX_VERSION`;
3. were installed and activated in disposable WordPress 7.1;
4. preserved exact raw multilingual storage through deactivate/reactivate;
5. projected the expected Latvian, Russian and English title/content;
6. retained a connected Redis object-cache backend;
7. were downloaded from the successful workflow and matched its SHA-256.

The earlier archive from `b6a7aa7` with SHA-256
`c8bf5a59d6db98ad31cefc245a4edaf3a0f40a8ec45ff04ace5a24f09af02bc3`
remains withdrawn and must not be distributed.

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

Any current archive with the same name or `qtranslate-xt-modern-rc1.zip` is a
staging artifact until the post-audit exact-ZIP rerun completes and its new
final identity is recorded here.

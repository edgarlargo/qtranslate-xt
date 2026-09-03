# QTX 4 release readiness

Date: 2026-09-03
Decision: **SECURITY GREEN — FINAL ZIP VALIDATION AUTHORIZED**

## Mandatory release gates

| Order | Gate | Result |
|---:|---|---|
| 1 | Resolve QTX4-SEC-001 | **PASS** — scalar guards, `esc_textarea()`, focused regressions |
| 2 | WooCommerce MySQL/Redis CI | **PASS** — run `33756895339`, 173/173 assertions |
| 3 | Final security re-audit | **PASS** — `FINAL-SECURITY-REAUDIT.md` |
| 4 | Fix release-blocking re-audit findings | **PASS** — remediation commit `7a0ca65` |
| 5 | Build and validate final RC ZIP | **NEXT / IN PROGRESS** |

The gates were executed in the required order. No earlier staging archive is
the final release candidate.

## Current automated evidence

- GitHub PHP/JavaScript run
  [`33756895200`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33756895200):
  **PASS** on `7a0ca65`.
- PHP 8.1–8.5: **345 tests / 8029 assertions** on every runtime.
- PHP 7.4/8.0 production lint: **PASS**.
- Node 24.11.1: `npm ci --ignore-scripts`, audit, JS tests, production build
  and exact committed-bundle comparison: **PASS**, zero advisories.
- Composer manifest validation and installed-graph audit: **PASS**, zero
  advisories.
- Module-loader security runner and `git diff --check`: **PASS**.
- GitHub WooCommerce run
  [`33756895339`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33756895339):
  **PASS**, 173 assertions, WordPress 7.1, WooCommerce 11.0.1, PHP 8.4,
  MySQL 8.4.11, Redis 7.4.11, Redis Object Cache 2.8.0 and HPOS.

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

## Exact ZIP requirements

The final archive must:

1. be produced from the final documented Git commit with `git archive`;
2. contain exactly one top-level `qtranslate-xt/` directory;
3. identify as `4.0.0-rc1` in both plugin header and `QTX_VERSION`;
4. include the committed production bundles and Latvian `.mo` file;
5. exclude `.git`, GitHub workflows, tests, development sources, `node_modules`,
   `vendor`, private ACF packages, license keys/credentials, local labs, databases,
   mail data and prior build archives;
6. pass a fresh WordPress 7.1 install/activate check and LV/RU/EN data
   preservation checks using the exact ZIP bytes;
7. have recorded SHA-256, file count and byte size.

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

The previous `build/qtranslate-xt-4.0.0-rc1.zip` and any earlier
`qtranslate-xt-modern-rc1.zip` are staging artifacts and must not be promoted.

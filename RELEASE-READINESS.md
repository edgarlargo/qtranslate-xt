# QTX 4 release readiness

Date: 2026-09-03
Decision: **NOT READY FOR RELEASE CANDIDATE**

## Completed quality gates

- PHP 7.4.33 and 8.0.30 production lint: **PASS**.
- PHP 8.1.29, 8.2.29, 8.3.29, 8.4.16 and 8.5.9: **PASS**,
  335 tests / 7963 assertions each.
- PHP lint: **PASS**, 180 plugin/test PHP files at the current checkpoint.
- JavaScript shared parser corpus and production build: **PASS**.
- `git diff --check`: **PASS** at checkpoints.
- Parser/storage compatibility: **PASS** for bracket/comment/curly,
  malformed/duplicate/Unicode/HTML/large inputs.
- Security: zero confirmed open Critical/High in current source re-audit.
- WordPress 7.1/PHP 8.4 fresh activation/deactivation: **PASS** on temporary
  SQLite integration lab.
- Gutenberg REST save/autosave and stale HTTP 409 conflict: **PASS** on that lab.
- ACF Free 6.8.8 activation/scalar smoke and theme-bundled runtime detection:
  **PASS**.
- ACF Pro 5.7.7 Options Page, Group, Repeater and Flexible Content native
  runtime/storage matrix: **PASS**; required QTX admin bundles are enqueued.
- WooCommerce 11.0.1 required transactional gate: **PASS**, 173/173 assertions
  in Actions run `33754558280` on WordPress 7.1/PHP 8.4, MySQL 8.4.11 and
  Redis 7.4.11 with HPOS enabled.
- QTX4-SEC-001 configuration textarea escaping: **RESOLVED**; focused regression
  is 9 tests / 37 assertions and the full PHP 8.1-8.5 matrix is green at
  332 tests / 7948 assertions per runtime.
- Exact `qtranslate-xt-modern-rc1.zip` installation on fresh WordPress 7.1:
  **PASS** for activation, LV/RU/EN HTTP frontend, ACF Pro 5.7.7 regression and
  deactivate/reactivate raw-data preservation.

## Current release-blocking gate

The WooCommerce MySQL/Redis blocker is resolved. In the mandatory release
order, the final security re-audit must now be performed and every finding it
classifies as release-blocking must be fixed before final ZIP construction.

## Remaining compatibility limitations (not promoted to tested claims)

- Interactive ACF admin language switching was not executable in the available
  browser surface; newer ACF Pro versions are not inferred from the 5.7.7 run.
- WooCommerce tags, permalink/canonical behavior and authenticated REST writes
  were outside the required transactional matrix and remain NOT TESTED.
- Classic Editor and interactive two-browser Gutenberg conflict UI were not
  executed.
- A complete upgrade database fixture from qTranslate-XT 3.16.1 was not
  available, although inline values survived deactivate/reactivate.

These limitations constrain compatibility claims. Final packaging remains
blocked by the mandatory final security re-audit, not by the completed Woo
transactional gate.

## Versioning

The development package identity is `4.0.0-rc1`, producing
`qtranslate-xt-modern-rc1.zip`. No official upstream release identity is
claimed. This is a staging artifact and must not be promoted before the final
security re-audit and exact final-package validation. Public
constants/options/hooks/storage markers are not renamed for branding.

## Known limitations

- A browser with a pre-H3 cached Gutenberg bundle must reload before saving;
  missing revisions fail safely with 409.
- Optional SEO/forms/Jetpack/Site Kit/Events modules have source validation but
  no current third-party version claims.
- Node 18.14 passed locally but is below a build dependency's declared engine;
  release CI should use Node 20 LTS or newer.
- npm reports vulnerabilities in development dependencies; runtime bundles do
  not ship `node_modules`, but build dependencies should be upgraded in a
  separately tested batch.

## Upgrade and rollback

Follow `UPGRADE.md`: full DB/files backup, staging verification, no automatic
database conversion, prior plugin directory plus database backup for rollback.

## Required evidence to unblock RC

1. Final security re-audit of the post-Woo tree and remediation of every
   release-blocking result.
2. A 3.16.1 upgrade fixture and interactive browser runs for Classic/Gutenberg
   where those are retained as release requirements.
3. Green rerun of all local and real-installation gates against the exact final
   ZIP.

The Woo infrastructure is encoded in
`.github/workflows/woocommerce-integration.yml` with disposable credentials,
offline COD and pre-transport mail capture. Its required run is green:
[`33754558280`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33754558280),
173/173 assertions.

The local attempt to extend exact-ZIP validation into Woo checkout stopped at
WooCommerce's MySQL-only stock reservation SQL (`INTERVAL`, `FOR UPDATE`,
`LOCK IN SHARE MODE`), unsupported by the official SQLite integration. The ZIP
is a prepared staging artifact, not a fully green release.

Historical 2026-09-02 attempt: the Woo gate was retried after QTX4-SEC-001 became green.
`actionlint` 1.7.12 passed the workflow, but `modernisation` is not present on
`origin` and no GitHub CLI, signed-in browser session or stored non-interactive
Git credentials are available. Consequently no workflow run exists. A local
disposable MySQL 8.0.31 fallback was started and stopped cleanly, but WordPress
7.1 could not be extracted on Windows because the archive contains a filename
ending in a dot. The temporary lab was removed. Neither attempt supplies the
required MySQL 8.4/Redis 7.4 Actions evidence at that checkpoint, so the Woo
gate was still FAIL / NOT RUN then.

The pre-CI review also corrected a real provisioning defect: the former
GitHub release-asset URL for WP-CLI returned 404. The workflow now uses the
official WP-CLI build with a fail-closed SHA-256 check and pins Redis Object
Cache 2.8.0. Its three new contract tests pass with 15 assertions; the complete
PHP 8.1-8.5 suite passes at 335 tests / 7963 assertions per runtime.

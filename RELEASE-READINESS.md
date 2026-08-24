# QTX 4 release readiness

Date: 2026-08-24
Decision: **NOT READY FOR RELEASE CANDIDATE**

## Completed quality gates

- PHP 7.4.33 and 8.0.30 production lint: **PASS**.
- PHP 8.1.29, 8.2.29, 8.3.29, 8.4.16 and 8.5.9: **PASS**,
  317 tests / 7890 assertions each.
- PHP lint: **PASS**, 173 plugin/test PHP files at the final checkpoint.
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
- WooCommerce 11.0.1 product/cart/checkout-load/fragments smoke: **PASS**.
- WooCommerce HPOS order-language CRUD regression: **PASS** locally; the
  reproducible MySQL/Redis transactional workflow is implemented but NOT RUN.
- Exact `qtranslate-xt-modern-rc1.zip` installation on fresh WordPress 7.1:
  **PASS** for activation, LV/RU/EN HTTP frontend, ACF Pro 5.7.7 regression and
  deactivate/reactivate raw-data preservation.

## Release-blocking gaps

- Interactive ACF admin language switching was not executable in the available
  browser surface; newer ACF Pro versions are not inferred from the 5.7.7 run.
- WooCommerce categories/attributes/variations, checkout transaction, orders,
  refunds, customer/admin emails, authenticated REST writes, full AJAX and
  persistent-cache backends were not executed.
- No MySQL/MariaDB WordPress installation was available; the real lab used the
  official SQLite integration.
- Classic Editor and interactive two-browser Gutenberg conflict UI were not
  executed.
- A complete upgrade database fixture from qTranslate-XT 3.16.1 was not
  available, although inline values survived deactivate/reactivate.

The remaining WooCommerce and platform NOT TESTED/BLOCKED items prevent
versioning and final RC packaging.

## Versioning

The development package identity is `4.0.0-rc1`, producing
`qtranslate-xt-modern-rc1.zip`. No official upstream release identity is
claimed. This is a staging artifact and must not be promoted while the external
WooCommerce transactional gate remains NOT RUN. Public
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

1. A MySQL/MariaDB-capable WordPress test environment (or approved CI) for the
   required integration matrix.
2. WooCommerce transactional/email fixtures and mail capture.
3. A 3.16.1 upgrade fixture and interactive browser runs for Classic/Gutenberg.
4. Green rerun of all local and real-installation gates against the exact final
   ZIP.

The first two infrastructure items are now encoded in
`.github/workflows/woocommerce-integration.yml` with disposable credentials and
pre-transport mail capture. Release readiness remains red until an authenticated
repository actor pushes/dispatches it and the recorded run is green.

The local attempt to extend exact-ZIP validation into Woo checkout stopped at
WooCommerce's MySQL-only stock reservation SQL (`INTERVAL`, `FOR UPDATE`,
`LOCK IN SHARE MODE`), unsupported by the official SQLite integration. The ZIP
is a prepared staging artifact, not a fully green release.

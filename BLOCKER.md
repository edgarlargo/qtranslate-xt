# Resolved WooCommerce release-candidate blocker

Date: 2026-09-03
Status: **RESOLVED**

## Resolution

The required WooCommerce MySQL/Redis blocker is **RESOLVED**. GitHub Actions
run [`33754558280`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33754558280)
passed **173/173 assertions** on commit `111eb5a` using WordPress 7.1,
WooCommerce 11.0.1, PHP 8.4, MySQL 8.4.11, Redis 7.4.11 and Redis Object Cache
2.8.0. HPOS was enabled. Credentials were disposable, COD was offline and mail
was captured before transport.

The final security re-audit passed after all release-blocking findings were
remediated. A later real-site report found that WooCommerce Cart/Checkout
Blocks bypassed the classic hooks covered by run `33758229929`. Its ZIP is
withdrawn. Expanded Store API/block-label CI and replacement exact-ZIP
validation is now the active release blocker.

## Historical problem

The supplied ACF Pro 5.7.7 package has passed the native Options Page, Group,
Repeater and Flexible Content runtime/storage matrix. The remaining QTX 4
release blocker is the comprehensive WooCommerce transactional matrix. The
local environment has no MySQL/MariaDB service, mail capture, payment/order
fixtures or persistent object-cache backend required to validate the remaining
WooCommerce order/email/REST/cache behavior. Producing an RC ZIP now would
violate the explicit quality gate.

An executable GitHub Actions environment now exists at
`.github/workflows/woocommerce-integration.yml`, backed by MySQL 8.4 and Redis
7.4, with WordPress 7.1, WooCommerce 11.0.1 and WP-CLI provisioned from clean
state. Its fail-closed runner is
`tests/Integration/WooCommerce/transaction-matrix.php`. This checkout cannot
dispatch it: GitHub CLI is absent, no authenticated CI control surface is
available, and the workflow is an uncommitted workspace change that does not
exist on the remote. Therefore its integration result remains **NOT RUN**, not
PASS.

## Evidence

- `REAL-WORDPRESS-TEST.md` records the executed WordPress 7.1 lab.
- ACF Pro 5.7.7 Options Page registration/lookup, raw storage, LV/RU/EN reads,
  Group, Repeater and Flexible Content passed in the disposable lab.
- WooCommerce 11.0.1 product/cart/checkout-load/fragments smoke passed, but no
  order/payment/refund/email/authenticated Woo REST/persistent-cache matrix was
  executed.
- `FULL-TEST-MATRIX.md` and `RELEASE-READINESS.md` mark these required items
  BLOCKED/NOT TESTED rather than PASS.

## Attempts made

1. Confirmed Docker, Podman, MySQL/MariaDB, system PHP/Composer and WP-CLI were
   absent.
2. Used portable PHP 8.4 with SQLite extensions.
3. Downloaded official WordPress 7.1, SQLite Database Integration 3.0.0, ACF
   Free 6.8.8, WooCommerce 11.0.1 and WP-CLI into a disposable temp lab.
4. Installed WordPress and executed activation, frontend, REST, Gutenberg,
   ACF Free/theme-bundled and Woo smoke tests.
5. Fixed four real defects found by that lab: portable option LIKE SQL, numeric
   Intl date formats, ACF theme runtime state and Gutenberg stale conflicts.
6. Implemented the native early ACF runtime bootstrap, explicit value context,
   field whitelist and ACF storage boundary; repeated plugin/theme-bundled
   Options API LV/RU/EN tests with ACF Free 6.8.8.
7. Verified the theme-bundled run had no ACF entry in `active_plugins`.
8. Re-ran PHP 8.1–8.5: 317 tests / 7890 assertions per version, all green;
   production sources also lint cleanly on PHP 7.4 and 8.0.
9. Ran the supplied ACF Pro 5.7.7 package against native Options Page, Group,
   Repeater and Flexible Content fixtures; all passed and fixtures were removed.
9. Removed disposable labs and test credentials after recording results.
10. Added a reproducible MySQL/Redis GitHub Actions lab and a transactional
    LV/RU/EN runner covering products, variations, cart, checkout, orders,
    captured mail, authenticated Woo REST, technical-data invariants and cache
    isolation/invalidation.
11. Found and fixed WooCommerce HPOS incompatibility in order-language storage:
    checkout language now uses WooCommerce order CRUD rather than post meta.
    The local suite passes at 319 tests / 7897 assertions on PHP 8.4.
12. Attempted to locate an executable CI control path: `gh` is absent and the
    only configured remote is HTTPS. No workflow dispatch is possible before
    these uncommitted changes are reviewed and pushed by an authenticated
    repository actor.
13. Built the exact `4.0.0-rc1` development ZIP and installed it into a fresh
    WordPress 7.1/PHP 8.4/SQLite lab. Activation, LV/RU/EN HTTP frontend,
    ACF Pro 5.7.7 native regression and deactivate/reactivate raw-data
    preservation passed.
14. Executed the Woo runner locally until the database boundary. WooCommerce
    stock reservation emitted MySQL-only `INTERVAL`, `FOR UPDATE` and
    `LOCK IN SHARE MODE` SQL that the official SQLite integration cannot
    execute. This is concrete confirmation that the remaining matrix requires
    the provisioned MySQL CI job; it is not a QTX failure or a pass.

## Why repository information cannot resolve the remaining blocker

The ACF limitation was resolved with the legally supplied package; no fake ACF
API was used. Source/unit tests still cannot prove WooCommerce checkout
transactions, order snapshots, email
language, authenticated REST behavior and cache invalidation on real backends.
Claiming these as PASS would be false.

## Compatibility and security impact

- Releasing without Woo transaction/email tests risks wrong-language customer
  communications or unintended transformation of historical/technical data.
- No confirmed open Critical/High security issue is known, but integration data
  integrity is a release-blocking compatibility requirement.

## Possible solutions

1. Review and push the integration workflow, then allow its GitHub Actions job
   to run. It creates only disposable credentials and captures mail before
   transport; no production credentials are required.
2. If project owners intentionally remove full WooCommerce support from the RC
   scope, revise the release objective and
   compatibility claims explicitly; this is a product decision, not a safe
   technical assumption.

## Exact human input/resource required

Provide an authenticated path to push/dispatch the now-reproducible integration
environment. Once the workflow result is available, resume at any failed
assertion, then run exact-ZIP
installation validation, select a non-upstream development RC identity and only
then build the final RC ZIP.

# WooCommerce compatibility status

This document records evidence, not aspirational compatibility. WooCommerce
11.0.1 passed the required 176-assertion transactional matrix on WordPress
7.1/PHP 8.4, MySQL 8.4.11 and Redis 7.4.11. One tested version is not a broad
supported range; unrelated areas remain explicit.

The latest ACF frontend fallback is outside WooCommerce behavior but changes
production bytes. Therefore run `33869856719` remains valid historical Woo
evidence while a release-gate rerun is required before a replacement ZIP can be
designated.

| Area | Current implementation | Status |
| --- | --- | --- |
| Product title/description/short description | WordPress post adapters plus existing WooCommerce hooks | PASS (11.0.1, LV/RU/EN) |
| Categories | term repository/frontend adapters | PASS (labels and stable IDs) |
| Tags/category descriptions | term repository/frontend adapters | NOT TESTED; outside required Woo gate |
| Attribute and variation labels | existing module hooks and raw-slug preservation | PASS |
| SKU/price/stock/tax/IDs | protected by L1 technical data policy | PASS |
| Product/category/tag URLs | existing URL/slugs module | NOT TESTED |
| Classic cart/mini-cart | language-aware hash; simple/variation labels, quantities, totals and fragments | PASS |
| Cart/Checkout Blocks | Store API frontend presentation filters plus text-only dynamic label adapter | PASS (11.0.1, LV/RU/EN) |
| Classic checkout/order review | COD-only transaction and translated payment label | PASS |
| Historical orders/HPOS | no rewrite; explicit `_user_language` through Woo CRUD | PASS |
| Customer emails | captured processing/completed LV/RU/EN plus cancelled/refund contexts | PASS |
| REST | authenticated products, variations and orders | PASS |
| AJAX/cart fragments | add-to-cart, variation selection, fragments and language routing | PASS |
| Cache | Redis language isolation and group-scoped webhook invalidation | PASS |

## Technical data never translated

SKU, price fields, stock values/status, IDs, hashes, dimensions, download and
product-attribute structures, payment/transaction identifiers, order keys,
customer technical/address metadata and arbitrary non-string structures are not
translation targets. Product names/descriptions and intentionally registered
human-readable labels remain eligible.

## Orders and email policy

Orders are historical records. qTranslate-XT does not rewrite product-name or
variation snapshots. New checkout orders retain the existing `_user_language`
context. Admin status links, AJAX links and resend context use it only when it is
a currently configured language; older orders without it retain prior behavior.

## Versions and release gate

Tested WooCommerce version: **11.0.1**. No supported version range is claimed.
The required products, categories, attributes, variations, cart, checkout,
orders, HPOS, emails, REST, AJAX and Redis matrix is green for this exact stack.

Webhook delivery uses group-scoped invalidation on supporting modern WordPress
object caches. Redis Object Cache 2.8.0 passed; backends without group flushing
remain NOT TESTED.

## Reproducible integration gate

`.github/workflows/woocommerce-integration.yml` provisions an isolated MySQL
8.4/Redis 7.4 WordPress 7.1 lab with WooCommerce 11.0.1. The WP-CLI runner at
`tests/Integration/WooCommerce/transaction-matrix.php` contains the LV/RU/EN
transaction, captured-email, REST and cache assertions.

## 2026-09-04 final release-gate result

GitHub Actions run
[`33869856719`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856719)
passed after the Cart/Checkout Blocks and ACF Options Bridge delta security
audits on commit `f145b5c637d438e2c7c9df0b5a3d5ba27336a4e2`.
The disposable job used WordPress 7.1,
WooCommerce 11.0.1, PHP 8.4, MySQL 8.4.11, Redis 7.4.11 and Redis Object Cache
2.8.0. All **176 assertions passed**.

The matrix covers simple/variable products; title, long/short description,
category, attribute and variation presentation; stable IDs, SKU, price, stock,
tax and serialized technical metadata; simple/variation cart rows, quantities,
totals and fragments; offline COD checkout; create/processing/completed/
cancelled/refund order paths; HPOS order language and historical snapshots;
captured LV/RU/EN mail; authenticated product/variation/order REST reads; AJAX
language isolation; and persistent Redis isolation/invalidation without a
global flush. No production secret, external payment or external email was
used. The WooCommerce release blocker is resolved.

The same job built and installed the exact release-candidate ZIP, verified LV/RU/EN raw
data preservation across plugin deactivation/reactivation, confirmed the
Latvian MO file, retained Redis connectivity and published the validated
artifact.

## Cart/Checkout Blocks regression and resolution

A real WooCommerce block-cart and block-checkout page returned raw QTX markers
in Store API product names and dynamically rendered labels. The prior matrix
exercised classic PHP cart hooks and AJAX fragments, so its broad cart claim
was incomplete and the corresponding ZIP was withdrawn.

The remediation activates the existing Woo frontend presentation filters only
for the exact `/wc/store/` REST namespace. It also adds a 1.9 KiB client bundle
that uses WordPress JavaScript i18n filters and replaces only text-node values
inside Woo Cart/Checkout/Mini-Cart roots. It never writes HTML and does not
change price, SKU, ID, quantity, tax, stock or order data. The expanded matrix
now performs an actual `/wc/store/v1/cart` request and proves the Russian name
is projected while product ID, quantity and price remain unchanged. Run
`33869856719` also installed and exercised the exact post-audit replacement
ZIP, so the compatibility regression and packaging gate are closed.

WooCommerce 11 HPOS order-language storage was corrected to use the
`WC_Abstract_Order` metadata API. The legacy checkout metadata hook remains for
compatibility, while `woocommerce_checkout_order_created` covers modern order
storage.

## Historical: 2026-09-02 release-gate attempt

The QTX4-SEC-001 prerequisite is resolved and its PHP 8.1-8.5 regression matrix
is green. `actionlint` 1.7.12 accepts
`.github/workflows/woocommerce-integration.yml` with no findings. The reviewed
job pins WordPress 7.1, WooCommerce 11.0.1, PHP 8.4, MySQL 8.4 and Redis 7.4;
uses a generated one-run administrator password; uses only the offline COD
gateway; and captures mail through `pre_wp_mail` before transport.

The workflow is committed only on the local `modernisation` branch. That branch
does not exist on `origin`, GitHub CLI is unavailable, the browser session is
signed out, and a non-interactive `git push --dry-run` confirmed that no saved
GitHub credentials are available. No Actions run was therefore created.

A separate Windows fallback proved that an isolated MySQL 8.0.31 datadir can be
started without touching WAMP data, but WordPress 7.1 cannot be extracted on
this Windows filesystem because its archive contains a trailing-dot filename.
The temporary server shut down normally and the complete lab was removed. This
is not equivalent to the Ubuntu MySQL 8.4/Redis 7.4 workflow and does not change
any Woo row from NOT TESTED/BLOCKED to PASS.

Pre-CI review found that the original `releases/latest` WP-CLI asset URL
returned 404. The workflow now uses the official WP-CLI build with a fixed
SHA-256 verification and pins Redis Object Cache 2.8.0. Three source-contract
tests cover the pinned stack, fail-closed download, generated credential,
offline COD payment, local mail capture and `.example.test` recipients. They
pass with 15 assertions; the full PHP 8.1-8.5 suite passes at 335 tests / 7963
assertions per runtime. The transactional result remains NOT RUN.

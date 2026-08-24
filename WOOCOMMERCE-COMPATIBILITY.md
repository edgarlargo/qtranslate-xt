# WooCommerce compatibility status

This document records evidence, not aspirational compatibility. WooCommerce
11.0.1 was smoke-tested on WordPress 7.1/PHP 8.4 with the official SQLite
integration. Untested areas remain explicit; one smoke version is not a broad
supported range.

| Area | Current implementation | Status |
| --- | --- | --- |
| Product title/description/short description | WordPress post adapters plus existing WooCommerce hooks | PASS smoke (11.0.1) |
| Categories/tags/descriptions | term repository/frontend adapters | NOT TESTED |
| Attribute and variation labels | existing module hooks and dropdown raw-ID preservation | NOT TESTED |
| SKU/price/stock/IDs | protected by L1 technical data policy | PASS unit + product smoke |
| Product/category/tag URLs | existing URL/slugs module | NOT TESTED |
| Cart/mini-cart | language-aware hash; names/hooks retained | PASS add/cart smoke; mini-cart NOT TESTED |
| Checkout/order review | existing presentation hooks | PASS page load; transaction NOT TESTED |
| Historical orders | no rewrite; explicit `_user_language` policy | PASS (unit only) |
| Customer emails | stored order language used when valid | NOT TESTED |
| REST | no dedicated WooCommerce REST matrix yet | NOT TESTED |
| AJAX/cart fragments | existing `wc-ajax` language handling | PASS fragments smoke |
| Cache | cart hash includes language; webhook invalidation is group-scoped | PARTIAL |

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

Tested WooCommerce version: **11.0.1 smoke only**. No supported version range is
claimed yet. The release gate still requires integration tests for
products, taxonomies, attributes, variations, cart, checkout, orders, emails,
REST, AJAX and caching against explicitly recorded WooCommerce versions.

Webhook delivery uses group-scoped invalidation on supporting modern WordPress
object caches. Behavior on backends without group flushing remains NOT TESTED.

## Reproducible integration gate

`.github/workflows/woocommerce-integration.yml` provisions an isolated MySQL
8.4/Redis 7.4 WordPress 7.1 lab with WooCommerce 11.0.1. The WP-CLI runner at
`tests/Integration/WooCommerce/transaction-matrix.php` contains the LV/RU/EN
transaction, captured-email, REST and cache assertions. The workflow has not
yet run from this checkout, so none of those pending rows are promoted to PASS.

WooCommerce 11 HPOS order-language storage was corrected to use the
`WC_Abstract_Order` metadata API. The legacy checkout metadata hook remains for
compatibility, while `woocommerce_checkout_order_created` covers modern order
storage.

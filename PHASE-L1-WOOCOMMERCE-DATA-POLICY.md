# Phase L1 — WooCommerce data policy

## Objective

Make the existing WooCommerce module's data boundary explicit before extending
its hooks. Human-readable strings may be translated; commerce identifiers,
prices, stock, hashes, payment/order keys and serialized technical structures
must remain raw.

## Implementation

`WooCommerceDataPolicy` provides three focused contracts:

- an exact/prefix technical post-meta denylist covering SKU, price, stock,
  product attribute structures, dimensions, downloads, IDs, payment/order keys,
  customer addresses and WooCommerce internal metadata;
- scalar-only presentation translation that never traverses arrays, objects,
  numeric or boolean values;
- deterministic language-aware cart hashing and validated order-language
  selection.

The WooCommerce metadata filter now falls through for bulk reads and technical
keys. This prevents the generic recursive metadata translator from touching a
technical structure even if it contains marker-like text. Human-readable custom
metadata such as purchase notes and explicit product labels retains the normal
translation path.

Order language handling distinguishes an explicit configured `_user_language`
from an effective fallback language. Admin status/AJAX URLs and resend context
continue to append/switch only when the order actually stored a valid language,
preserving historical-order behavior. Email content may fall back to the current
configured language. No order snapshot is rewritten.

## Compatibility

- Product post title/content/excerpt, terms and existing presentation hooks are
  unchanged.
- SKU, prices, stock, IDs and product attribute structures remain raw.
- Existing language-aware mini-cart hash output is byte-for-byte preserved.
- Existing orders without `_user_language` do not gain a new language query.
- Invalid/stale order language values cannot change runtime language context.

## Tests

Tests cover the technical meta boundary, human-readable opt-in keys,
scalar-only presentation translation, unchanged cart data, per-language cart
hashes, normalized configured order language, invalid language fallback and
absence of explicit context for legacy orders.

PHP 8.1, 8.2, 8.3 and 8.4 each pass 278 tests and 7730 assertions with zero
failures.

Real WooCommerce product/cart/checkout/order/email/REST/AJAX execution remains
required. L1 is a foundation batch and does not claim WooCommerce compatibility
complete.

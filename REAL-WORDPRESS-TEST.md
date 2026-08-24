# Real WordPress installation test

Date: 2026-08-24

## Environment

- WordPress 7.1, clean temporary installation
- PHP 8.4.16
- official SQLite Database Integration 3.0.0 (temporary test DB)
- ACF Free 6.8.8
- ACF Pro 5.7.7 (separate disposable retest using the supplied package)
- WooCommerce 11.0.1
- current `modernisation` working-tree plugin copied as `qtranslate-xt/`

This environment is disposable and is not a production-support claim for
SQLite. No ACF Pro package/license or MySQL/MariaDB server was available.

## Results

| Test | Status | Evidence |
|---|---|---|
| Fresh WordPress install | **PASS** | WP-CLI core installation completed |
| qTranslate activation | **PASS** | plugin active, default EN/DE configuration created |
| ACF Free activation | **PASS** | ACF 6.8.8 loaded |
| WooCommerce activation | **PASS** | WooCommerce 11.0.1 loaded and pages created |
| qTranslate deactivate/reactivate | **PASS** | post and ACF raw multilingual values unchanged |
| Inline post frontend EN/DE | **PASS** | title/content projected correctly with `lang` |
| Public REST translated view | **PASS** | DE title/content returned |
| Gutenberg authenticated edit/save | **PASS** | projected EN edit saved, DE raw block preserved |
| Gutenberg stale conflict | **PASS** | stale revision returned HTTP 409, no overwrite |
| Gutenberg autosave | **PASS** | autosave kept both languages; parent unchanged |
| Modern date output | **PASS after fix** | internal `%2` renders ISO-8601 `datetime` |
| ACF Free scalar stored/read | **PASS smoke** | DB retained inline raw; current language projected |
| Theme-bundled/non-standard ACF detection | **PASS** | ACF loaded from theme path and QTX ACF module became active by runtime capability |
| Native ACF lifecycle retest | **PASS** | `acf/init` initialized exactly once; generic runtime provider available |
| Native ACF Options API | **PASS Free core API** | `option` and `options` reads returned selected LV/RU/EN; storage retained markers |
| Native Text/Textarea/WYSIWYG fixtures | **PASS** | all four reported production strings returned language-only content |
| No fake ACF plugin state | **PASS** | theme-bundled run had no ACF entry in `active_plugins` |
| Woo product title/description/short description | **PASS smoke** | DE product page projected content |
| Woo price/SKU integrity | **PASS smoke** | 19.99 and `SKU-QTX-1` retained |
| Woo add-to-cart/cart | **PASS smoke** | session cookies, DE title and price rendered |
| Woo checkout page | **PASS load** | HTTP 200; no order/payment executed |
| Woo fragments AJAX | **PASS smoke** | JSON HTTP 200 |
| ACF Pro / Options Pages | **PASS runtime/storage/enqueue, 5.7.7** | real registration/lookup; raw storage and translated reads; QTX admin bundles emitted |
| ACF Group/Repeater/Flexible | **PASS runtime, 5.7.7** | multilingual leaves projected; technical values/layout keys unchanged |
| Woo order/payment/refund/emails/REST write | **NOT TESTED** | full transactional fixtures/gateways/mail sink absent |
| MySQL/MariaDB | **NOT TESTED** | server absent; unit matrix remains DB-independent |
| Classic Editor interactive UI | **NOT TESTED** | browser automation not executed |
| Upgrade from 3.16.1 fixture | **PARTIAL** | stored legacy inline values survived deactivate/reactivate; full historical DB fixture absent |

## Bugs found and fixed

1. The option marker scan used a manual SQL `ESCAPE` expression that the
   official SQLite driver could not translate. It now uses `$wpdb->esc_like()`
   and placeholders; option-list patterns are also parameterized.
2. ACF loaded from a theme was visible at runtime but module state depended on
   plugin basename. ACF state now accepts the existing runtime capability while
   preserving the manual admin enable flag.
3. Numeric extended date formats `%1..%4` were present in the table but excluded
   by the Intl formatter regex. WordPress format `c` emitted `%2`; it now emits
   ISO-8601.
4. Gutenberg had no stale revision requirement. H3 adds exact-route revision
   checks and verified HTTP 409 conflicts.
5. The ACF admin integration registered `qtranslate_admin_config` only at
   `acf/init`, after QTX selected page assets. Admin hooks now register during
   trusted module loading; the value adapter remains on official `acf/init`.

## Limitations

This installation materially improves confidence but does not satisfy the full
release gate. The supplied ACF Pro 5.7.7 runtime removes the ACF package
blocker. Interactive browser JavaScript switching was not executable in the
available browser surface, and no compatibility claim is made for newer ACF
Pro versions. Comprehensive WooCommerce orders/emails/REST/AJAX/cache scenarios
remain unexecuted.

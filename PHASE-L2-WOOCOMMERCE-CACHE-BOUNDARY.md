# Phase L2 — WooCommerce webhook cache boundary

## Change

Webhook raw-data preparation no longer calls site-wide `wp_cache_flush()`.
When the current WordPress object-cache API supports group flushing, qTranslate
invalidates only `posts`, `post_meta`, `terms`, `term_meta` and the
qTranslate language-specific post/term metadata groups. WooCommerce's existing
attribute transient deletion and taxonomy re-registration remain unchanged.

If an older/incompatible object-cache backend does not support group flush, the
plugin skips broad invalidation rather than deleting unrelated options, users,
sessions or cart caches. This path requires real legacy-backend testing; the
release target is current WordPress.

## Tests

The cache-group policy is deterministic, deduplicates languages and proves that
unrelated `options` and `users` groups are absent. PHP 8.1–8.4 each pass 279
tests and 7733 assertions. No `wp_cache_flush()` call remains in production.

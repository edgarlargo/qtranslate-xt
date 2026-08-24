# Phase B3 — RSS text adapters

## Goal

Move only the primary RSS title/content/excerpt hooks to named WordPress
adapters without changing their distinct legacy fallback policies.

## Changes

- `FrontendTranslationAdapter::translateRssExcerpt()` delegates the existing
  show-available excerpt policy.
- `translateRssText()` preserves ordinary current-language/first-available
  behavior for `the_title_rss` and `the_content_rss`.
- `src/hooks.php` retains hook names, priority 0 and one accepted argument.
- Other RSS hooks remain on legacy callbacks for later declarative migration.

Changed adapter, hooks and tests; added this document/status update.

## Tests and compatibility

Direct legacy output parity and exact hook registrations are asserted. PHP
8.1–8.4 each pass 193 tests and 7420 assertions with zero failures.
Storage, output markup, filters and database behavior are unchanged.

## Rollback and next phase

Rollback restores three callback names in `qtranxf_add_main_filters()`. B4 will
isolate object-level post/menu translation, which requires mutable object tests
and exact field-level parity rather than generic string callback tests.

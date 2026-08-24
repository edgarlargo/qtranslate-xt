# Phase B2 — Content and excerpt adapters

## Goal and files

Route only `the_content` and `the_excerpt` through named thin methods on
`FrontendTranslationAdapter`. Updated `src/hooks.php`, the adapter and its unit
tests; added this document and status update.

## Compatibility

- `the_content`: priority 100, accepted args 1.
- `the_excerpt`: priority 0, accepted args 1.
- Both retain current-language, show-available=true, show-empty=false behavior.
- `the_excerpt_rss` remains on the legacy callback for the next RSS batch.
- Hook names, filter ordering, return values, unavailable-language HTML and
  opaque content behavior are unchanged.

## Tests

Plain, translated, HTML and script-looking content match the legacy callback.
Registration callback, priority, accepted args and untouched RSS callback are
asserted. PHP 8.1–8.4 each pass 192 tests and 7416 assertions with zero failures.

## Rollback and next phase

Rollback replaces the two callback arrays in `qtranxf_add_main_filters()` with
the prior procedural name. B3 will address RSS and declarative frontend text
contexts without changing term/menu/post object adapters in one batch.

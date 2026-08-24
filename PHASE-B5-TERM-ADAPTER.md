# Phase B5 — declarative term adapter

## Goal

Move declaratively configured term filters to a named WordPress adapter while
preserving the legacy `qtranxf_useTermLib()` behavior and filter contracts.

## Changes

- Added `FrontendTranslationAdapter::translateTerm()` as a thin boundary around
  `qtranxf_term_use()` with the current configured language.
- Updated both `qtranxf_add_filters()` and `qtranxf_remove_filters()` to use the
  identical class callback for the `term` filter group.
- Added differential coverage for scalar term-name mappings, mutable term
  objects, arrays of terms, and exact add/remove priority contracts.
- Added `taxonomy.php` to the PHPUnit bootstrap to match the production load
  order already established by `src/init.php`.

## Compatibility

The legacy public function remains available. Term translation data,
`i18n_config` mutation, hook priorities, option names, database shape and inline
multilingual formats are unchanged. Third-party configurations using the
declarative `term` group receive the same values through a named callback.

## Tests

PHP 8.1, 8.2, 8.3 and 8.4 each pass 198 tests and 7429 assertions with zero
failures. The adapter is tested against the preserved legacy callback.

## Rollback and remaining work

Rollback restores `qtranxf_useTermLib` in the two declarative registration
loops. Complex navigation-menu translation remains on its legacy callback until
complete WordPress hierarchy and URL fixtures are available.

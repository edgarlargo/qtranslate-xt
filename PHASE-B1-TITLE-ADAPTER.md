# Phase B1 — Frontend title adapter

## Goal

Replace the generic `the_title` translation callback with a named thin
WordPress adapter while preserving declarative configuration and output.

## Files changed

- `src/Integration/WordPress/FrontendTranslationAdapter.php`
- `src/init.php`
- `src/utils.php`
- `tests/bootstrap.php`
- `tests/Unit/FrontendTranslationAdapterTest.php`
- `PHASE-B1-TITLE-ADAPTER.md`
- `MODERNISATION-STATUS.md`

## Design and compatibility

`FrontendTranslationAdapter::translateTitle()` reads the existing current
language at the WordPress boundary and delegates to the migrated translation
facade. The core remains global-free.

`qtranxf_add_filters()` maps only the exact `the_title` hook to this adapter;
all other configured text hooks retain the legacy callback. Removal uses the
same mapping. Hook name, configured priority 20, accepted argument count 1,
return type, HTML opacity and fallback output are unchanged.

## Tests

Adapter/legacy output parity covers plain, multilingual, HTML, missing language,
scalar and recursive array values. Registration/removal callback identity,
priority and accepted args are asserted. PHP 8.1–8.4 each pass 190 tests and
7405 assertions with zero failures. `git diff --check` passes.

## Rollback and next phase

Rollback is the single special-case mapping in `qtranxf_add_filters()` and
`qtranxf_remove_filters()`. Phase B2 migrates only `the_content` and
`the_excerpt`, retaining their distinct priorities and show-available policy.

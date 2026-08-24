# Phase I2 — PHP deprecation cleanup

## Scope

The last repository references to deprecated `FILTER_SANITIZE_STRING` and the
loosely typed `FILTER_SANITIZE_NUMBER_INT` were replaced in the dormant ACF
field-group context helper with WordPress-native normalization:

- taxonomy slugs use `sanitize_key( wp_unslash(...) )`;
- user IDs use `absint()`.

The helper remains commented legacy code, so this phase does not activate a new
ACF path or change production behavior. Updating it prevents the deprecated API
from returning if that helper is restored later.

Executable `strftime()` calls remain absent. The public deprecated
`qtranxf_strftime()` compatibility facade remains and delegates to the Intl
implementation, as required by the legacy policy.

## Compatibility

No hooks, functions, options, ACF lifecycle, database format or multilingual
storage changed. `sanitize_key` is intentionally stricter than the removed
generic string filter and matches WordPress taxonomy-key syntax. `absint`
produces the integer identity expected by ACF.

## Validation

PHP lint, PHP 8.1–8.4 PHPUnit, repository deprecation search and
`git diff --check` are release gates for this batch.

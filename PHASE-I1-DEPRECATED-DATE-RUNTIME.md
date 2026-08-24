# Phase I1 — Deprecated date runtime compatibility

## Scope

This batch removes the final executable calls to PHP's deprecated `strftime()`
API. It deliberately does not change the public qTranslate-XT date/time API,
stored date formats, WordPress settings, or the meaning of legacy QTX format
tokens.

## Files changed

- `src/deprecated.php`
- `tests/bootstrap.php`
- `tests/Unit/DateTimeCompatibilityTest.php`
- `MODERNISATION-STATUS.md`

## Runtime change

`qtranxf_strftime()` remains available as a deprecated compatibility function
and continues to emit the existing WordPress deprecation notification. Its
existing QTX-specific pre-processing is unchanged. The final locale-aware
formatting step now delegates to `qxtranxf_intl_strftime()`, the existing
Intl-based formatter used by the production date/time path. Day extraction for
the `%q` ordinal logic uses `date('d', $timestamp)` instead of `strftime('%d',
$timestamp)`.

No executable `strftime()` or `gmstrftime()` call remains in the repository.
References retained in documentation, setting labels and function names are
compatibility terminology rather than deprecated PHP API calls.

## Compatibility contract

- Empty formats still return `$default` without applying `$before`/`$after`.
- `$before` and `$after` still wrap formatted results.
- The public signature and deprecation notification are unchanged.
- QTX-specific `%q`, `%E`, `%f`, `%F`, `%i`, `%J`, `%k`, `%K`, `%l`, `%L`,
  `%N`, `%Q`, `%o`, `%O`, `%s`, `%v`, `%1`, `%2`, `%3`, and `%4` preprocessing
  remains ordered exactly as before.
- Literal `%%` remains supported.

The Intl formatter can differ slightly from platform libc locale formatting;
that difference already applies to qTranslate-XT's authoritative production
date path and is preferable to relying on an API deprecated since PHP 8.1.
`ext-intl` is already a declared Composer runtime requirement.

## Tests

Added regression coverage for the empty-format/default branch, deprecation
notification, before/after wrapping, standard formats, escaped percent signs,
and the complete QTX extended-token set.

PHPUnit matrix:

| PHP | Result |
| --- | --- |
| 8.1.29 | 259 tests, 7655 assertions, pass |
| 8.2.29 | 259 tests, 7655 assertions, pass |
| 8.3.29 | 259 tests, 7655 assertions, pass |
| 8.4.16 | 259 tests, 7655 assertions, pass |

All changed PHP files pass syntax checks. The repository module-loader security
test and `git diff --check` are also required at final verification.

## Deferred inventory

`FILTER_SANITIZE_STRING` occurs only inside an entirely commented-out legacy
ACF method and has no runtime effect. It was not edited in this focused batch.
Other PHP 8.x type, dynamic-property, and WordPress deprecation items remain for
subsequent Phase I batches.

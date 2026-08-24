# Phase O1 — Database value boundaries

## Scope

This focused batch removes manual SQL value interpolation without changing mass
operation behavior or database formats.

## Changes

- Legacy import/export option-prefix searches use `$wpdb->esc_like()` and a `%s`
  placeholder rather than interpolating function arguments into `LIKE`.
- Translated-slug page lookup uses placeholders for meta key, every `IN` value,
  post type and fallback post name. Manual `esc_sql()`, quoted list construction
  and deprecated `escape_by_ref()` are removed from this path.
- Slugs uninstall uses a placeholder per known language meta key and safely skips
  an empty list.

Table names remain WordPress `$wpdb` properties. Dynamic term field fragments
are selected by the existing internal `slug`/`name` branch; further SQL/mass
operation review remains for later O batches.

## Compatibility and tests

Queries retain their result columns, predicates and ordering. No conversion,
delete or migration command was executed. PHP lint, `git diff --check`, and the
PHP 8.1–8.4 matrix pass at 282 tests and 7742 assertions per version.

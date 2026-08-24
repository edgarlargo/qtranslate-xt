# Phase C6 — term-scoped translation repository

## Goal

Remove term-name translation data integrity from the global
`qtranslate_term_name` read/modify/write race while preserving the option and
its exact format for legacy consumers and rollback.

## Final data flow

New/edited term translations are stored under protected term metadata key
`_qtx_term_name_translations`, scoped by term ID. Reads use:

```text
term ID metadata → valid language/string map
                 → otherwise legacy qtranslate_term_name[name]
```

Writes update term metadata first, then dual-write the unchanged legacy option
shape. Deletes remove term metadata and matching legacy name entries.

`qtranxf_term_set_i18n_config()` and both slugs term consumers now read through
`TermTranslationRepository`. Admin create/edit/delete handlers use repository
store/delete operations. No eager or mandatory migration is performed: an
option-only installation continues to read normally and acquires term metadata
lazily when a term is edited.

## Race result

Concurrent edits of different term IDs retain both authoritative metadata
records even if their compatibility option writes originate from stale maps.
Object-based frontend/admin and slugs translation therefore no longer loses the
term translations. Name-only legacy search/sanitization helpers still consult
the aggregate option and are a documented compatibility limitation until they
can resolve an unambiguous term ID/taxonomy.

## Files changed

- Added `src/Integration/WordPress/TermTranslationRepository.php` and tests.
- Added the request-local repository facade to `src/integration_api.php` and
  bootstrap includes.
- Updated `src/taxonomy.php`, admin taxonomy persistence and slugs consumers.
- Added minimal WordPress term-meta/option test doubles.

## Compatibility and rollback

The legacy option name and array format are preserved and dual-written. Inline
multilingual content and database schemas are unchanged; WordPress's existing
termmeta table is used. Rolling back QTX code ignores the protected metadata and
continues from the preserved option. Same-name terms in different taxonomies
now have independent authoritative records once edited.

## Tests

PHP 8.1–8.4 each pass 221 tests and 7501 assertions with zero failures. Tests
cover meta-first reads, option-only fallback, independent writes from stale
legacy snapshots, dual-write compatibility and deletion. Changed PHP files pass
syntax checks and `git diff --check` is green.

## Remaining risks

- Bulk cleanup/import/export still rebuilds the legacy aggregate option and
  should gain explicit repository synchronization in a later migration batch.
- A WordPress integration suite must validate persistent object cache,
  multisite, term splitting and real hook ordering.
- Legacy code reading the option directly cannot see an entry lost from a
  concurrent compatibility write until that term is edited/exported again;
  repository consumers remain correct from term metadata.

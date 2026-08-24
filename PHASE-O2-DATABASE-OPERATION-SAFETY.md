# Phase O2 — database operation safety

## Root cause and scope

Legacy database maintenance was correctly behind the main `manage_options` and
settings-nonce flow, but accepted a loosely typed action, ran irreversible
in-place conversions after a single radio selection, and allowed the SQL-file
splitter to open an arbitrary administrator-supplied local path.

## Changes

- Conversion action is now scalar-normalized and checked against the exact
  `none`, `b_only`, `c_dual`, `db_split`, `db_clean_terms` allowlist.
- In-place database conversions require an explicit backup/irreversibility
  confirmation checkbox. A missing confirmation performs no write.
- `LocalSqlFilePolicy` accepts only a readable existing `.sql` regular file,
  rejects stream wrappers, canonicalizes with `realpath()` and requires the
  result to remain within approved roots.
- Default roots are `ABSPATH` and `WP_CONTENT_DIR`; trusted PHP may extend them
  with `qtranslate_database_file_roots`. Stored/request data cannot extend the
  roots.
- Requested language codes are lower-cased, format-validated and deduplicated;
  an empty/invalid list safely falls back to the configured default language.
- Splitter errors now update the actual configuration error collection rather
  than a detached copy.

The splitter streams input and writes derived `.sql` files in the approved
input directory. It never parses or executes SQL.

## Compatibility

Option names, database schema and multilingual formats are unchanged. Existing
in-place conversions need one additional human confirmation. SQL dumps outside
the WordPress tree are rejected by default; trusted site code can explicitly
add a canonical root through the new filter. This is an intentional filesystem
boundary with a controlled compatibility escape hatch.

Transactions were not added: WordPress installations may use non-transactional
tables, and the existing row-by-row helpers do not yet return sufficient error
state for a reliable cross-table rollback. The UI continues to warn that these
tools are irreversible and now requires backup confirmation. A future batch may
add preview counts and resumable bounded processing without changing upgrade
behavior; no conversion runs automatically during plugin upgrade.

## Tests

`LocalSqlFilePolicyTest` covers an approved canonical SQL file, wrong extension,
stream wrapper and a file outside the root. PHP 8.4 passed 285 tests / 7751
assertions at the implementation checkpoint; the full PHP matrix remains a
release gate.

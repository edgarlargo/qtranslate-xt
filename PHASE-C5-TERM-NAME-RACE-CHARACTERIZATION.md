# Phase C5 — `qtranslate_term_name` race characterization

## Confirmed data flow

`qtranxf_load_option_array('term_name')` loads the complete
`qtranslate_term_name` option into `$q_config['term_name']`. Admin taxonomy
handlers then mutate that request-local snapshot and write the entire map:

- `qtranxf_term_del_translation()` removes old name entries on `edit_term`;
- `qtranxf_term_set_translation()` adds/replaces one entry on `created_term` and
  `edited_term`;
- `qtranxf_term_delete()` removes an entry on `delete_term`;
- `qtranxf_db_clean_terms()` rebuilds and replaces the complete map.

The slugs module independently reads the same option in
`qtranxf_slugs_get_object_terms()` and `qtranxf_slugs_get_terms()`.

## Confirmed race

Two requests can load the same map, change different terms, and call
`update_option()` sequentially. The later full-map write discards the earlier
request's change. WordPress Options API does not provide a per-map-entry atomic
update contract. Reloading immediately before writing narrows but does not close
the race.

## Rejected quick fixes

- A process-local/static lock does not coordinate PHP workers.
- A best-effort object-cache lock is not portable when no persistent cache is
  configured.
- Direct SQL compare-and-swap would bypass normal Options API cache and update
  hooks unless a substantial compatibility layer reproduced them.
- Merely changing array merge order cannot resolve concurrent deletion/update.

## Safe replacement contract

Translations should become term-ID scoped data (WordPress term metadata or a
repository with equivalent atomic per-term semantics). Migration must be lazy
and non-mandatory:

1. read term-scoped data first;
2. fall back to the existing name-keyed option for legacy rows;
3. write term-scoped data on create/edit;
4. retain a bounded compatibility dual-write/export path for consumers of the
   old option;
5. update the slugs module to use the repository rather than `get_option()`;
6. delete naturally with the term and account for shared/legacy term IDs;
7. never rewrite inline multilingual post content.

The exact metadata key and public deprecation schedule should be introduced
through the Phase D trusted integration/storage API, not invented as an
undocumented one-off key in an admin handler.

## Compatibility risk

High. Existing code can inspect `qtranslate_term_name`, and the map is keyed by
default-language name rather than term ID. Immediate removal or silent migration
could break slug/admin behavior and ambiguous same-name terms across taxonomies.
Therefore this characterization batch intentionally changes no production
storage behavior.

## Acceptance tests required for implementation

- two different term edits based on the same legacy snapshot both survive;
- edit/rename/delete and taxonomy-specific same-name terms;
- legacy-option-only installation reads without migration;
- term-scoped write followed by legacy slugs/admin reads;
- object-cache enabled/disabled;
- multisite site boundaries;
- cleanup/import/export and plugin activation/deactivation;
- rollback continues reading the preserved legacy option.

## Result

The race is confirmed, its unsafe shortcuts are excluded, and the repository/
dual-read compatibility contract is established. No production file or database
format was changed in C5.

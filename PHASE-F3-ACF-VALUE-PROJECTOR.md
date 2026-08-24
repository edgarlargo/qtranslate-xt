# Phase F3 — schema-aware ACF value projector

## Goal

Provide the pure compound-value transformation required by a first-class ACF
adapter without changing active ACF load/save hooks yet.

## Architecture

`AcfValueProjector` receives the exact definitions produced by
`AcfFieldSchema` and an injected scalar translator. It:

- translates only registered string leaves;
- preserves group associative keys and repeater row order;
- selects flexible-content subfields by the technical `acf_fc_layout` value
  without modifying that value;
- leaves technical/unregistered keys, unknown layouts, arrays outside declared
  compounds and objects unchanged;
- refuses to translate serialized-looking strings;
- enforces a bounded recursion depth.

The injected translator is where the QTX TranslationService/context will be
connected by the later WordPress adapter. The projector itself reads no globals,
options, requests, database or filesystem and performs no HTML sanitation.

## Files and compatibility

Created `src/Integration/Acf/AcfValueProjector.php` and tests; updated both
bootstraps. Production ACF hooks, legacy extended fields, saved values, Options
Pages and JavaScript remain unchanged.

## Tests

PHP 8.1–8.4 each pass 233 tests and 7578 assertions with zero failures. Tests
cover nested group/repeater leaves, flexible layouts/unknown rows, technical
field preservation, unregistered values, objects and serialized-looking strings.
`git diff --check` passes.

## Next step

F4 should connect this projector on official ACF lifecycle hooks in an opt-in
adapter, construct explicit QTX language context, and characterize Free/Pro/
Options Page load/save behavior before replacing legacy formatting.

# Phase F2 — ACF stable field-key schema

## Goal

Define exactly which ACF values may become multilingual before wiring new
load/save hooks. This batch remains WordPress-independent and shadow-only.

## Schema

`AcfFieldSchema` discovers immutable ACF `field_*` keys and registers only:

| ACF type | QTX value type |
|---|---|
| `text` | text |
| `textarea` | text |
| `wysiwyg` | HTML |

Technical fields—including image, file, number, boolean, IDs, URL, email,
relationship, post object, color and coordinates—are ignored. Invalid/non-field
keys are ignored. Duplicate keys with conflicting types fail deterministically.

Compound discovery recursively walks `group`, `repeater` and
`flexible_content` subfields. Only whitelisted leaf definitions are returned;
layout names, row structure, keys and technical leaves are never translated.
Nesting is bounded to prevent malformed schemas from causing unbounded work.

## Files and compatibility

Created `AcfFieldDefinition`, `AcfFieldSchema` and tests; updated production/test
bootstraps. No ACF hook, stored value, field configuration, Options Page,
JavaScript or legacy extended field changed.

## Tests

PHP 8.1–8.4 each pass 230 tests and 7570 assertions with zero failures. Tests
cover the initial whitelist, technical exclusions, stable keys, group/repeater/
flexible nesting, conflicting keys and depth limits. `git diff --check` passes.

## Next step

F3 will add a schema-aware value projector in shadow mode and verify that it
translates only registered scalar leaves while preserving compound structure and
serialized/technical data byte-for-byte.

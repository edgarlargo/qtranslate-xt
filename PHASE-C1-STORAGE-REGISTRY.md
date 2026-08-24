# Phase C1 — explicit storage field registry

## Goal

Introduce the minimum schema-aware registry required to replace broad implicit
option/metadata translation gradually, without changing production filtering in
this shadow-mode batch.

## Architecture

- `FieldDefinition` identifies one trusted PHP declaration by storage scope and
  exact key. Supported scopes are `option`, `post`, `term` and `user`.
- Definitions declare a content value type (`text` or `html`); arbitrary objects
  are not a supported value type.
- `FieldRegistry` provides exact membership lookup and deterministic duplicate
  rejection. An option or metadata value cannot register itself.

The registry performs no WordPress reads, writes, hook registration,
serialization or deserialization. It is intentionally not wired to broad legacy
filters yet; compatibility mode remains authoritative.

## Files

Created `src/Core/Storage/FieldDefinition.php`,
`src/Core/Storage/FieldRegistry.php` and registry unit tests. Production and test
bootstraps load the new classes.

## Compatibility and rollback

No runtime callback, option name, metadata key, database format or public API
changed. Rollback removes the two class includes and files. This batch does not
address the legacy `qtranslate_term_name` race; it creates the explicit registry
needed for a safe replacement path.

## Tests

PHP 8.1–8.4 each pass 203 tests and 7443 assertions with zero failures. Tests
cover all four storage scopes, exact opt-in, value/key validation and duplicate
registration. Changed PHP files pass syntax checks and `git diff --check` is
green.

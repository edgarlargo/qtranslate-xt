# Phase D1 — integration registry core

## Goal

Create the trusted, WordPress-independent registry core before exposing public
registration facades or changing built-in integration loading.

## Architecture

- `IntegrationDefinition` validates a stable ID/version, optional runtime
  predicate and named object services.
- `IntegrationRegistry` owns integration descriptors, the Phase C field
  registry and named object value adapters.
- Duplicate integration, field and adapter IDs fail deterministically.
- Runtime predicates express capability/API availability without relying on
  plugin basenames or filesystem locations.
- Services and value adapters must be already-instantiated objects. Strings such
  as loader paths cannot be registered as executable services.

The registry does not read options, construct paths, include files or activate
modules. Module state remains under the Security Batch 2 allowlisted loader.

## Files and compatibility

Created `src/Core/Integration/IntegrationDefinition.php`,
`IntegrationRegistry.php` and unit tests; updated production/test bootstraps.
No hook, option, database format or existing integration behavior changed.

## Tests

PHP 8.1–8.4 each pass 215 tests and 7486 assertions with zero failures. Tests
cover availability predicates, services, fields, value adapters, invalid IDs,
non-object service rejection and duplicate diagnostics. `git diff --check`
passes.

## Next step

D2 will expose narrow procedural facades and fire one documented registration
hook before adapter/module boot. Rollback removes the new class includes/files;
no stored state is involved.

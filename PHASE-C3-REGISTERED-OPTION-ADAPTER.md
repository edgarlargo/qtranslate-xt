# Phase C3 — request-scoped registered option adapter

## Goal

Provide an opt-in WordPress boundary for exact option keys without replacing or
altering the legacy broad option compatibility filter.

## Architecture and behavior

`RegisteredOptionAdapter` receives a trusted field registry, scalar value
adapter and explicit language request/context. `register()` installs only
`option_{exact-key}` filters for definitions in the `option` scope, at legacy
priority 5 with one argument. It is idempotent per instance. `unregister()` uses
the same retained callback objects, so rollback within a request is exact.

No global singleton is created and core does not auto-register fields. Trusted
PHP must explicitly construct and register this adapter. Post, term and user
metadata are not intercepted in this batch. Broad legacy option filtering
remains authoritative for existing installations.

## Files and compatibility

Created `src/Integration/WordPress/RegisteredOptionAdapter.php`, updated both
bootstraps and added hook-contract/invocation tests. Option names, stored values,
database formats and existing hooks/functions are unchanged. The adapter never
deserializes values and delegates only exact registered scalar strings.

## Tests

PHP 8.1–8.4 each pass 208 tests and 7459 assertions with zero failures. Tests
verify scope isolation, exact hook name, priority/arity, translated output,
idempotent registration and symmetric removal. PHP syntax and
`git diff --check` pass.

## Rollback and next step

Rollback removes the new class include/file/tests; no data rollback is needed.
Metadata adapters require a separate batch because WordPress `get_*_metadata`
pre-filter semantics and cache invalidation differ materially from option
filters.

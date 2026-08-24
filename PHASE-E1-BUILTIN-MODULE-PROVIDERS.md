# Phase E1 — built-in module service providers

## Goal

Expose secured built-in modules as trusted integration services without adding a
second module list or allowing option values to define executable paths.

## Architecture

`QTX_Admin_Module::get_modules()` remains the sole authoritative built-in
registry. `QTX_Module_Loader::get_registered_module_loaders()` retains canonical
`realpath()` and module-directory boundary validation. For each resulting entry,
one `BuiltinModuleProvider` wraps the known ID and canonical loader.

The same provider objects are:

- exposed as `module-{id}` integration descriptors through
  `qtx_register_integrations`;
- used by `load_active_modules()` after the unchanged state-only option check.

The `qtranslate_modules_state` option can only activate a registered ID. It
cannot add a provider, service or path. Active loading still reads the state map
once and invokes only canonical providers selected by the secured loader.

## Compatibility

All nine built-in IDs, loaders, option keys/states and activation behavior are
unchanged. The provider layer does not add third-party filesystem registration.
Future trusted integrations can expose object services, but module executable
paths remain governed by the existing canonical allowlist.

## Files

- Added `src/Core/Integration/BuiltinModuleProvider.php` and unit tests.
- Updated `src/modules/module_loader.php` and both bootstraps.
- Updated the standalone module-security bootstrap for the new class
  dependencies and corrected its expected order to the actual authoritative
  `get_builtin_setup()` order.

## Tests

PHP 8.1–8.4 each pass 223 tests and 7557 assertions with zero failures. The
standalone QTX-SEC-005 traversal/corrupted-state suite passes. Tests prove exact
ID/loader/provider identity, nine canonical files and descriptor reuse. PHP lint
and `git diff --check` pass.

## Rollback and remaining work

Rollback restores direct `require_once` over the already validated active loader
map; no data migration is required. Individual modules remain legacy procedural
loaders internally and should be migrated/tested one at a time. Runtime
capability detection, especially ACF, is the next priority.

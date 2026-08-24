# Phase D2 — public integration registration API

## Goal

Expose the QTX 4 trusted registry through narrow procedural facades and a
documented, deterministic WordPress lifecycle hook.

## Public API

- `qtx_get_integration_registry()` returns the request-local canonical registry.
- `qtx_register_integration(IntegrationDefinition $definition)` registers one
  trusted integration descriptor.
- `qtx_register_multilingual_field(FieldDefinition $field)` registers one exact
  schema-aware storage field.
- `qtx_register_value_adapter(string $id, object $adapter)` registers an
  instantiated adapter service.
- `qtx_register_integrations` fires once with the registry as its argument.

The lifecycle fires immediately after `qtranxf_load_config()`: configured
languages are available, while common/frontend/admin adapters and built-in
modules have not booted. Third-party PHP can attach the hook while its plugin
file is loaded, before `plugins_loaded` priority 2.

## Security and compatibility

Registration requires executing trusted PHP. Options cannot define integration
services, adapters or filesystem paths. Duplicate IDs retain deterministic
exceptions from the core registry. Existing qTranslate hooks, module loading,
options and database formats are unchanged. The new lifecycle is idempotent.

## Files

Created `src/integration_api.php` and API tests. Updated `src/init.php` and the
test bootstrap. No built-in integration has been switched yet.

## Tests

PHP 8.1–8.4 each pass 218 tests and 7494 assertions with zero failures. Tests
cover shared registry identity, all three facades, one-shot lifecycle and
duplicate diagnostics. Changed PHP files pass syntax checks and
`git diff --check` is green.

## Rollback and next step

Rollback removes the lifecycle call/include and API file; no stored data is
affected. The next data-integrity priority is implementing the C5 term-scoped
repository behind this stable registration/storage boundary.

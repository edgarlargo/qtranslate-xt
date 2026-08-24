# Phase C4 — registered metadata hook adapter

## Goal

Provide an opt-in, schema-aware WordPress metadata boundary for post, term and
user metadata without changing the broad legacy compatibility filters.

## Architecture

- `MetadataValue` is an explicit provider result: either a supported scalar
  string or unsupported. Unsupported/compound/serialized values fall through.
- `RegisteredMetadataAdapter` installs a pre-filter only for storage scopes that
  contain trusted registered definitions. Exact key membership is checked
  before calling the injected raw reader.
- Only single-value reads are handled. Multi-value requests, unregistered keys
  and results already supplied by an earlier filter are returned unchanged.
- The raw reader is injected so the adapter never recursively calls
  `get_metadata()` and never assumes a cache/database implementation.
- Registered-key updates invoke an injected invalidator through current
  `updated_post_meta`, `updated_term_meta` and `updated_user_meta` hooks.

The adapter never deserializes, traverses arrays/objects or treats them as
multilingual strings.

## Compatibility and activation

No automatic instance is created. Existing post/user metadata callbacks remain
registered and authoritative. Trusted integration code may opt into the new
adapter only after supplying a raw scalar reader and matching invalidator. Hook
removal retains the exact closure identities. No option/meta key or stored data
format changed.

## Tests

PHP 8.1–8.4 each pass 212 tests and 7476 assertions with zero failures. Tests
cover exact-key translation, no recursion, fall-through behavior, single-value
scope, earlier-filter precedence, update invalidation, idempotent/symmetric hook
lifecycle, and all three metadata scopes. PHP lint and `git diff --check` pass.

## Remaining work

A production raw provider must be implemented against WordPress cache semantics
and integration-tested before enabling registered metadata automatically. The
legacy arbitrary deserialization path remains isolated to compatibility mode.

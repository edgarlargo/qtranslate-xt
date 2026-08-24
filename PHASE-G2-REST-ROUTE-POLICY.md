# Phase G2 — route-scoped REST policy adapter

## Goal

Ensure multilingual REST handling can only run for routes explicitly registered
by trusted PHP and only after an object-specific capability decision.

## Architecture

- `RestRouteDefinition` declares a stable ID, exact entity collection base in
  `/namespace/vN/collection` form, allowed methods and an object-capability
  resolver.
- Only a final positive numeric object ID may follow that base. Request input is
  never interpreted as regex or filesystem syntax.
- `RestRouteRegistry` rejects duplicate IDs and returns no match for unknown
  routes, unsupported methods, traversal fragments or malformed IDs.
- `RestRoutePolicyAdapter` invokes the capability resolver only after a route
  match and only for `context=edit`; public view does not perform or infer edit
  authorization. It then delegates language/raw decisions to G1 policy.

An unknown third-party route returns `null` (not handled). The mere presence of
`qtx_editor_lang` or a raw flag cannot activate processing.

## Files and compatibility

Created `RestRouteDefinition`, `RestRouteRegistry`, `RestRoutePolicyAdapter` and
tests; updated production/test bootstraps. Nothing is auto-registered and the
legacy Gutenberg interceptor remains unchanged in G2.

## Tests

PHP 8.1–8.4 each pass 247 tests and 7621 assertions with zero failures. Tests
cover exact registered routes, capability invocation, public view, raw edit,
unknown routes, wrong methods, zero/malformed IDs, traversal paths, invalid base
definitions and duplicate diagnostics. PHP lint and `git diff --check` pass.

## Next step

G3/H1 should define the structured editor representation and revision-token
merge contract. Only after route/controller integration tests may it replace the
global `rest_request_before_callbacks`/`after_callbacks` interception.

# Phase H2 — registered WordPress REST field adapter

## Goal

Provide an opt-in controller-level replacement for global Gutenberg request
interception, including privileged structured state and HTTP 409 optimistic
conflicts.

## WordPress integration

`RegisteredPostRestFieldAdapter` registers a `qtx` field only for explicitly
supplied REST post types through `register_rest_field()`. Its schema is limited
to `context=edit`. The original WordPress controller permission callback runs
before additional-field callbacks; the adapter also invokes its injected
object-specific edit capability for defense in depth.

Privileged reads expose title/content/excerpt raw values, translations, syntax,
diagnostics and per-field revision hashes. Unauthorized reads return 403.

Updates accept an exact language, registered field map and matching revisions.
All requested fields are read and merged before the single injected writer is
called. Therefore a conflict in any field performs no writes and returns
`WP_Error` status 409. Invalid payload/field/language returns 400, unsupported
malformed source returns 422 and writer failure returns 500.

## Security and storage boundaries

- Unknown post types are not registered.
- Only title, content and excerpt are accepted.
- Raw data is edit-only and capability-gated.
- No route is activated merely by `qtx_editor_lang`.
- The adapter does not sanitize HTML; the original post controller/write policy
  remains responsible for field sanitation.
- The writer is invoked once with the complete merged field set, allowing a
  production implementation to use one `wp_update_post()` call.

## Compatibility

The adapter is not automatically registered. Legacy
`QTX_Admin_Block_Editor` filters and current JavaScript remain authoritative
until real WordPress REST/autosave/revision tests pass. No stored content,
route response or public schema changes in existing installations.

## Tests

PHP 8.1–8.4 each pass 256 tests and 7651 assertions with zero failures. Tests
cover registration/schema, invalid post types, privileged raw reads, capability
denial, multi-field conflict with zero writes, and successful one-batch merge.
PHP lint and `git diff --check` pass.

## Next step

End-to-end WordPress tests are required before replacing the legacy interceptor.
The next repository-only phase is PHP 8.1–8.4/runtime deprecation modernization.

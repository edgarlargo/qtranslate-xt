# Phase F5.1 — ACF admin editing contract

## Scope

This batch introduces a WordPress-independent editing boundary for supported ACF
leaves. It does not replace or double-register the existing ACF admin UI. The
legacy JavaScript integration remains authoritative while the new contract is
prepared for a later official lifecycle/UI adapter.

## Architecture

`AcfAdminEditingService` combines the authoritative `AcfFieldSchema` whitelist
with `EditorFieldMergeService`. Only stable `field_*` text, textarea and WYSIWYG
leaves may be projected or merged. Technical fields, unstable keys, objects and
serialized-looking strings are rejected.

Projection returns the lossless raw value, ordered translations, syntax,
diagnostics and a SHA-256 revision token. Merge requires an explicit configured
language and matching revision, preserves the source syntax and other languages,
and returns a conflict for stale data. The service has no dependency on request
globals, post IDs or WordPress options, so post, nested and Options Page values
share the same contract.

## Compatibility

- Inline bracket, comment and curly storage remains unchanged.
- No database migration or normalization runs.
- Empty translations follow the characterized legacy builder contract: the
  empty block is omitted from serialized output and reloads as an empty language
  projection, preserving existing fallback behavior.
- Group/repeater/flexible layout and row data remain outside scalar merge and
  therefore cannot be rewritten by it.
- Existing ACF hooks, extended fields and JavaScript are unchanged in F5.1.

## Tests

Coverage includes lossless edit/reload, explicit empty translation, HTML as
opaque content, Options Page stale-write conflict, a nested stable leaf, layout
preservation, and rejection of technical/unstable/object/serialized values.

PHP 8.1, 8.2, 8.3 and 8.4 each pass the full suite: 264 tests and 7674
assertions, with zero failures.

## Remaining F5 work

The official ACF admin lifecycle/UI bridge, dynamic repeater/flexible-row browser
behavior, real ACF Free/Pro/theme-bundled/Options Page integration fixtures and
source/dist JavaScript validation remain required. F5 is not complete yet.

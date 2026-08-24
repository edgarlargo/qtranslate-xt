# Phase G3 / H1 — structured editor merge contract

## Goal

Prevent Gutenberg/REST single-language saves from silently overwriting changes
made after the editor loaded the multilingual raw field.

## Structured representation

`EditorFieldMergeService::project()` returns an `EditorFieldState` containing:

- the unchanged full raw string;
- parsed translations;
- detected legacy syntax;
- parser diagnostics;
- SHA-256 revision token derived from the exact raw bytes.

Projection is read-only and never normalizes storage.

## Optimistic merge

Merge requires the current raw value, the editor's expected revision, a
configured language and its new scalar value. If the revision differs, status is
`conflict` and current raw is returned unchanged; a REST controller will map this
to HTTP 409. Invalid languages are rejected.

Well-formed simple values rebuild using their original bracket/comment/curly
syntax and preserve other languages. Malformed sources or duplicate language
blocks are returned as `unsupported_source` instead of being destructively
normalized. Successful merge returns a new revision token.

## Security and compatibility

The service is WordPress-independent and has no capability assumptions; G2 must
authorize the route/object before invoking it. It performs no HTML sanitization,
deserialization or database access. No active REST/Gutenberg hook is changed in
this batch, so legacy behavior remains available until controller integration is
tested.

## Files

Created `EditorFieldState`, `EditorMergeResult`, `EditorFieldMergeService` and
unit tests; updated both bootstraps.

## Tests

PHP 8.1–8.4 each pass 251 tests and 7634 assertions with zero failures. Tests
cover lossless projection, comment-syntax merge, preservation of other
languages, new revision generation, stale-write conflict, invalid language,
malformed input and duplicate blocks. `git diff --check` passes.

## Next step

H2 should add an opt-in WordPress REST controller adapter for registered post
routes/fields, map conflict to 409 and preserve the original controller's
permission callback. Global request interception must remain until end-to-end
WordPress editor tests prove the replacement.

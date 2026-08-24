# Phase B4 — post collection adapter

## Goal

Move the `the_posts` collection callback behind a named WordPress adapter while
preserving the legacy field translation and navigation-menu bypass exactly.
Menu-object translation remains a separate later batch because it has a wider
WordPress object and URL contract.

## Changes

- Preserved the former implementation as `qtranxf_legacy_postsFilter()` for
  direct differential tests and rollback.
- Added `FrontendTranslationAdapter::translatePosts()` as the production
  callback. It preserves non-array input, bypasses `nav_menu_item` queries and
  delegates each ordinary post to `qtranxf_translate_post()` using the current
  configured language.
- Kept the `the_posts` priority at 5 and accepted-argument count at 2.
- Added mutable-object parity, bypass and hook-registration regression tests.

## Compatibility and security

Post fields, mutation semantics, hook timing, option names, storage formats and
inline multilingual syntax are unchanged. The adapter introduces no new input
source, authorization decision or output escaping boundary. It performs no
HTML sanitization and leaves that responsibility outside the parser/translation
core.

## Tests

PHP 8.1, 8.2, 8.3 and 8.4 each pass 196 tests and 7424 assertions with zero
failures. `git diff --check` is green.

## Rollback and next phase

Rollback consists of restoring `qtranxf_postsFilter` as the hook callback and
its preserved body. The next focused frontend batch should handle either menu
objects with complete hierarchy/URL fixtures or the lower-risk declarative term
callbacks; it must not conflate their contracts.

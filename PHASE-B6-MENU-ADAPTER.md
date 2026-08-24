# Phase B6 — navigation-menu hook adapter

## Goal

Place the complex `wp_get_nav_menu_items` integration behind a named WordPress
adapter without rewriting its hierarchy, title, URL or synthetic language-item
logic in the same batch.

## Changes

- Preserved the complete former implementation as
  `qtranxf_legacy_wp_get_nav_menu_items()`.
- Kept the public `qtranxf_wp_get_nav_menu_items()` function as a compatible
  wrapper.
- Registered `FrontendTranslationAdapter::translateMenuItems()` at the original
  priority 20 with three accepted arguments.
- Added differential mutable-object coverage for ordinary custom menu items and
  an exact hook-contract assertion.

## Compatibility

All execution still reaches the preserved legacy menu implementation. Existing
item mutation, removal of untranslated parents and descendants, language-menu
generation, custom URL handling and menu-count updates are therefore unchanged.
The public legacy callback name remains callable. Inline storage, options and
database formats are untouched.

## Tests and limitations

PHP 8.1, 8.2, 8.3 and 8.4 each pass 200 tests and 7432 assertions with zero
failures. The focused unit fixture covers the ordinary custom-item path. Full
language-switcher generation and WordPress cache/post/taxonomy URL behavior
still require integration tests against WordPress before internal extraction.

## Rollback

Restore `qtranxf_wp_get_nav_menu_items` as the registered callback and its
preserved body. No data rollback or migration is required.

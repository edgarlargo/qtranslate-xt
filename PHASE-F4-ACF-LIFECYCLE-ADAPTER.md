# Phase F4 — opt-in ACF lifecycle adapter

## Goal

Connect the F2/F3 schema pipeline to ACF's official value lifecycle without
switching existing installations away from the legacy ACF module prematurely.

## Architecture

`AcfLifecycleAdapter` registers `acf/format_value` at the legacy priority 5 with
the official three arguments: value, post/options identifier and field schema.
It builds exact field definitions from that schema and projects only registered
leaves. Registration/removal is symmetric and idempotent.

`AcfScalarTranslator` connects the adapter to `MultilingualParser` and
`TranslationService` using injected `LanguageRequest` and `LanguageContext`.
Neither class reads `$q_config`, request globals or options. The parser continues
to treat HTML as opaque content.

The adapter does not call `maybe_unserialize()`, does not add save/update hooks
and does not modify stored values. Post IDs and ACF Options Page identifiers are
passed through without assumptions.

## Compatibility and activation

The adapter is opt-in and is not automatically registered by the ACF loader.
The current `QTX_Module_Acf_Extended` remains authoritative, preventing duplicate
formatting. Existing custom qTranslate ACF fields, admin UI, AJAX and storage
remain unchanged. A later switch requires real WordPress/ACF integration parity.

## Files

Created `AcfLifecycleAdapter.php`, `AcfScalarTranslator.php` and tests; updated
production/test bootstraps.

## Tests

PHP 8.1–8.4 each pass 238 tests and 7591 assertions with zero failures. Tests
cover exact hook priority/arity, idempotent lifecycle, post and Options Page
identifiers, technical/unstable fields, compound rows and real Russian selection
through explicit QTX context. PHP lint and `git diff --check` pass.

## Remaining ACF validation

Automatic replacement is deferred until an integration environment can execute
ACF Free, Pro and theme-bundled versions, Options Pages, AJAX, nested save/load
and legacy extended-field parity. The new adapter is ready for that gate but is
not enabled speculatively.

# Phase A3 — Translation selection facade, Batch 3

## Goal

Route `qtranxf_use()`, `qtranxf_use_language()`, `qtranxf_use_block()` and
`qtranxf_use_content()` through the parsed core and a WordPress-independent
translation selection service while retaining exact fallback/presentation
behavior.

## Files changed

- `src/Core/Multilingual/TranslationResult.php`
- `src/Core/Multilingual/TranslationService.php`
- `src/init.php`
- `src/language_blocks.php`
- `tests/bootstrap.php`
- `tests/Characterization/LegacyUseFacadeDifferentialTest.php`
- `PHASE-A3-FACADE-BATCH-3.md`
- `MODERNISATION-STATUS.md`

## Design

`TranslationService::select()` receives translations, availability, requested
language, enabled-language order and `showEmpty` explicitly. It returns an
immutable `TranslationResult` with text, selected language, reason and ordered
availability. It does not read `$q_config`, requests, options or WordPress.

The procedural facade retains recursive array/object handling and owns legacy
presentation concerns: displayed-language prefix, unavailable-language links,
deprecated/current filters and `show_alternative_content`. Parser and service
do not sanitize or escape opaque content.

Preserved bodies are named `qtranxf_legacy_use*` and remain selectable through
`QTX_MULTILINGUAL_CORE_FACADE=false`.

## Compatibility

Exact, explicit-empty, first-enabled fallback, unavailable, prefix,
`show_available`, `show_empty`, alternative-content and recursive behavior are
unchanged. Public signatures, filter arguments/order, storage and database
behavior are unchanged.

## Tests

- 27 corpus cases across three languages and four policy combinations.
- 250 fixed-seed generated inputs across selection and show-empty paths.
- All presentation configuration combinations.
- Recursive array and object parity.
- PHP 8.1–8.4: 177 tests, 7355 assertions, zero failures on every runtime.

## Risks, rollback and next phase

Presentation remains procedural and coupled to existing WordPress helpers by
design; Phase B adapters can later isolate it per output context. Rollback uses
the existing constant. Next is A3.4 wrapper and `QTX_Translator` parser-facing
method verification, followed by completing the explicit A4 language model.

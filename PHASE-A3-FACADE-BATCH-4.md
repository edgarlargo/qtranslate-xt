# Phase A3 — Wrapper and translator verification, Batch 4

## Goal

Complete A3 by verifying the parser-facing procedural wrappers and
`QTX_Translator::translate_text()` against the migrated `qtranxf_use()` facade.

## Implementation

No production wrapper rewrite was necessary. The following already delegate to
the migrated API and remain unchanged:

- `qtranxf_useCurrentLanguageIfNotFoundShowEmpty()`;
- `qtranxf_useCurrentLanguageIfNotFoundShowAvailable()`;
- `qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage()`;
- `qtranxf_useDefaultLanguage()`;
- `QTX_Translator::translate_text()`.

The test bootstrap now loads the existing translator interface/class with a
minimal `add_filter()` stub. `LegacyWrapperTranslatorTest` verifies current and
default language mapping plus SHOW_AVAILABLE/SHOW_EMPTY bit flags.

## Files changed

- `tests/bootstrap.php`
- `tests/Characterization/LegacyWrapperTranslatorTest.php`
- `PHASE-A3-FACADE-BATCH-4.md`
- `MODERNISATION-STATUS.md`

No production file changed in this batch.

## Tests and compatibility

PHP 8.1–8.4 each pass 179 tests and 7364 assertions with zero failures. Public
signatures, translator constants, hook registration, storage and output remain
unchanged.

## Rollback and next phase

Wrappers follow the same `QTX_MULTILINGUAL_CORE_FACADE` switch through
`qtranxf_use()`. Phase A3 is complete. Phase A4 will formalize catalog, context,
request, fallback policy and resolver objects around the already proven
selection service without changing procedural results.

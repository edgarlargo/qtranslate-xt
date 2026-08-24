# Phase A5 — Core performance and robustness

## Goal

Centralize bounded request-local parsing cache in the parser, retain honest cold
benchmarks, and extend adversarial/lossless coverage before broad WordPress use.

## Files changed

- `src/Core/Multilingual/MultilingualParser.php`
- `src/language_blocks.php`
- `tests/Unit/MultilingualParserRobustnessTest.php`
- `tests/Benchmark/multilingual-core.php`
- `PHASE-A5-CORE-ROBUSTNESS.md`
- `MODERNISATION-STATUS.md`

## Design

Each configured parser owns separate raw/block caches with a default capacity of
64. A one-value fast path avoids hashing a repeatedly consumed large value.
Capacity zero disables caching for cold measurements; negative capacity is a
developer error. Procedural duplicate caches were removed, leaving one source
of cache behavior.

Cache lifetime is request-local through the facade parser registry. No
persistent cache, storage mutation or cross-configuration result reuse exists.

## Robustness coverage

- Cache identity, eviction and disabled mode.
- Explicit block-boundary preservation including empty blocks.
- NUL bytes, script/object-looking content, dense malformed marker storms.
- Large Unicode and 200 KiB bracket-only plain content.
- Exact original raw rebuild for every adversarial value.
- Existing 400-case parser and 250-case use/line generated suites remain green.

## Performance

PHP 8.4.16, 10,000 iterations over multilingual HTML:

- legacy split: 85.90 ms;
- core cold parse: 191.79 ms (2.23x legacy);
- core cached parse: 1.23 ms (~70x faster than legacy).

Facade six-operation workflow ratios after consolidation:

- plain short: 1.41x legacy;
- multilingual title: 0.56x;
- medium content: 0.30x;
- 64 KiB: 0.05x;
- malformed: 0.61x.

The cold object model cost is accepted because it retains entries/diagnostics;
normal repeated consumers share one bounded parse. No performance assertion is
placed in CI because wall-clock thresholds are environment-sensitive.

## Tests

PHP 8.1–8.4 each pass 188 tests and 7396 assertions with zero failures.
`git diff --check` passes. Parser remains structural and performs no sanitizing,
unserialization, code execution or content-derived filesystem access.

## Rollback and next phase

The facade constant retains legacy rollback. Cache capacity can be set to zero
when constructing an isolated parser. Phase B can now migrate title/content/
excerpt callbacks as thin context adapters in separate batches.

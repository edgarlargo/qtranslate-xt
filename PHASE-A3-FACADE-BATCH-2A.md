# Phase A3 — Join facade migration, Batch 2A

## Goal

Migrate the four pure join functions to `MultilingualBuilder` without changing
legacy normalization:

- `qtranxf_join_b()`;
- `qtranxf_join_c()`;
- `qtranxf_join_s()`;
- `qtranxf_join_b_no_closing()`.

Separator/line joins are intentionally isolated into Batch 2B because the
legacy separator implementation has a known non-terminating branch.

## Files changed

- `src/Core/Multilingual/MultilingualBuilder.php`
- `src/language_blocks.php`
- `tests/Characterization/LegacyJoinFacadeDifferentialTest.php`
- `PHASE-A3-FACADE-BATCH-2A.md`
- `MODERNISATION-STATUS.md`

## Design and compatibility

The old bodies remain as `qtranxf_legacy_join_*`. Public functions return plain
strings from the shared core builder. `buildTranslations()` gained an explicit
closing-marker flag for the legacy no-closing bracket variant.

The builder preserves PHP `empty()` behavior, first-nonempty/all-same collapse,
insertion ordering, original language key case, unknown keys, opaque HTML,
Unicode and exact bracket/comment/curly marker output. It performs no escaping
or normalization beyond the historical join behavior.

Public signatures and database/storage behavior are unchanged. Existing raw
comment or curly values are not rewritten merely by reading them.

## Tests

- Shared corpus: 27/27 exact legacy/facade parity.
- Fixed-seed generated translation maps: 400/400 parity.
- Edge maps: 8/8 parity.
- PHP 8.1–8.4: 141 tests, 5257 assertions, zero failures on each runtime.
- PHP lint and `git diff --check`: green.

## Rollback

Defining `QTX_MULTILINGUAL_CORE_FACADE` as `false` before bootstrap restores the
preserved join bodies together with Batch 1 parser bodies.

## Remaining risks and next phase

`qtranxf_join_byseparator()` and `qtranxf_join_byline()` remain. Batch 2B must
characterize them in timeout-isolated subprocesses before deciding how to
represent the legacy non-termination defect safely.

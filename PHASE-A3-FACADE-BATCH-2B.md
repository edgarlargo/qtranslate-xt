# Phase A3 — Structured join facade migration, Batch 2B

## Goal

Migrate `qtranxf_join_byline()` and `qtranxf_join_byseparator()` to the core
builder while isolating and resolving the legacy separator non-termination bug.

## Root cause and evidence

The legacy separator loop calls `next( $lang_lines )` inside `foreach` by value.
Each iteration advances a copied array, so the pointer never progresses in the
stored `$lines` array. Differing translations containing a matched separator
append indefinitely until resource exhaustion.

An isolated PHP 8.4.16 reproduction with `A, B` / `А, Б` did not return and
terminated with the 128 MiB memory limit exhausted in `language_blocks.php`.
Terminating legacy branches are all-same input and differing input without a
matched separator.

## Files changed

- `src/Core/Multilingual/MultilingualBuilder.php`
- `src/language_blocks.php`
- `tests/Characterization/LegacyStructuredJoinFacadeTest.php`
- `PHASE-A3-FACADE-BATCH-2B.md`
- `MODERNISATION-STATUS.md`

## Design decision

`MultilingualBuilder::buildByLine()` reproduces the legacy line-index algorithm.
`buildBySeparator()` preserves the historical pointer start, delimiter capture,
join normalization and suffix placement but stores indexes explicitly so they
advance and terminate.

This creates one intentional robustness difference: input that previously
exhausted time/memory now returns the finite output implied by the existing
algorithm. No previously returned string changes:

- terminating separator branches retain 100% parity;
- line joins retain 100% parity;
- ordinary parsing/storage reads remain untouched.

The change prevents denial of service and cannot silently rewrite stored data;
joins only return a value when explicitly called.

## Tests

- All four terminating separator branches: exact legacy parity.
- Two differing separator/newline cases: deterministic finite output.
- Fixed-seed generated line maps: 250/250 legacy parity.
- Full suite before matrix: 147 tests, 5513 assertions, zero failures.

## Compatibility and security

Signatures and return types are unchanged. HTML remains opaque. The builder does
not sanitize, unserialize, execute or access files. The only compatibility
deviation replaces non-termination/resource exhaustion with finite output.

## Rollback

`QTX_MULTILINGUAL_CORE_FACADE=false` restores the exact old implementation,
including its non-termination defect. Operational rollback should therefore be
used cautiously for separator inputs.

## Next phase

Proceed to A3.3: translation selection and fallback facades. Preserve parser/
sanitizer separation and characterize every `show_available`, `show_empty` and
enabled-language-order branch.

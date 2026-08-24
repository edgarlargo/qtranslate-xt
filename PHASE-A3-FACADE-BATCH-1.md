# Phase A3 — Legacy parser facade migration, Batch 1

## Scope and outcome

Six low-risk parser functions now route through the QTX 4 core while preserving
their public signatures and legacy return shapes:

- `qtranxf_isMultilingual()`;
- `qtranxf_get_language_blocks()`;
- `qtranxf_split()`;
- `qtranxf_split_blocks()`;
- `qtranxf_split_languages()`;
- `qtranxf_getAvailableLanguages()`.

The old bodies remain available under matching internal `qtranxf_legacy_*`
names for rollback and differential verification. They were moved, not
semantically rewritten.

The following remain outside this batch: `qtranxf_use*`, all joins and fallback
logic, frontend filters, REST, ACF, Gutenberg, the admin editor, language
detection, database/options and modules.

## Files changed

- `src/init.php` loads the five core class files before the facade file;
- `src/language_blocks.php` contains the thin public facades, preserved legacy
  bodies, bounded request cache and rollback switch;
- `src/Core/Multilingual/MultilingualParser.php` adds `parseBlocks()` so caller-
  supplied block boundaries retain exact legacy semantics;
- `tests/Characterization/LegacyFacadeDifferentialTest.php` adds corpus,
  generated and arbitrary-block differential coverage;
- `tests/Benchmark/legacy-facade-batch-1.php` provides the required performance
  scenarios;
- `PHASE-A3-FACADE-BATCH-1.md` documents this batch.

No unrelated subsystem file was changed.

## Facade mapping and conversion

| Legacy facade | Core operation | Compatibility conversion |
|---|---|---|
| `qtranxf_isMultilingual` | `MultilingualDetector::isMultilingual` | native bool |
| `qtranxf_get_language_blocks` | `MultilingualParser::parse` | ordered entries mapped to exact raw tokens |
| `qtranxf_split` | `MultilingualParser::parse` | `translations()` associative array |
| `qtranxf_split_blocks` | `MultilingualParser::parseBlocks` | translations plus encoded keys merged into caller's by-reference `found` map |
| `qtranxf_split_languages` | `MultilingualParser::parseBlocks` | encoded-only associative array |
| `qtranxf_getAvailableLanguages` | `MultilingualParser::parse` | one-or-fewer entries maps to legacy `false`; otherwise ordered language array |

`parseBlocks()` classifies each supplied array item directly. It deliberately
does not join and re-tokenize first, because a third-party caller may supply a
block such as `[:lv]not-a-token`, which legacy code treats as neutral text.

Core diagnostics remain internal and no object crosses the public procedural
API boundary. No new exception is introduced for characterized inputs.

## Escape hatch and rollback

`QTX_MULTILINGUAL_CORE_FACADE` defaults to `true`. Defining it as `false` before
the plugin bootstrap routes all six public functions to their preserved legacy
bodies:

```php
define( 'QTX_MULTILINGUAL_CORE_FACADE', false );
```

This is an internal development/operational switch, not an admin option. It
does not change the database. It should be removed only after all parser/use/
builder facade batches have completed, production soak is successful and the
differential suite remains green without relying on the old path.

## Request-local cache

Repeated public calls over the same value initially showed a 3–4x regression.
The facade therefore retains up to 64 parsed values and four parser
configurations per request. Keys include enabled-language order, current
language and language-code pattern. A one-entry fast path avoids hashing the
same large value repeatedly. The cache is non-persistent, performs no database
or filesystem access and cannot change stored values.

## Compatibility and parity

The shared corpus was compared directly between `qtranxf_legacy_*` and the new
public facades for all six functions, including the by-reference `found` map.

- shared corpus: **100% (27/27)**;
- deterministic generated inputs: **100% (400/400)**;
- arbitrary caller-supplied block arrays: **100% (8/8)**;
- expectation files were not changed.

Generated inputs use a fixed local LCG seed (`0x51A3`) and combine known and
unknown/case-variant markers, all three syntaxes, plain/neutral text, Latvian,
Cyrillic, emoji, whitespace, adjacent and duplicate markers, orphan/malformed
closings and HTML-looking text.

Preserved quirks include case-insensitive matching with case-preserving keys,
`{:}` versus `{::}`, adjacent empty markers, duplicate concatenation, neutral
text copying, trimming, unknown languages, missing closings and the orphan-
closing availability fallback.

## Performance

The benchmark invokes all six legacy bodies or all six facades per iteration.
Representative median ratios from three PHP 8.4.16 runs after the bounded-cache
optimization are:

| Scenario | Iterations | Facade / legacy | Approximate delta |
|---|---:|---:|---:|
| plain short string | 5,000 | 1.56x | +56% (~3–4 microseconds per six-call group) |
| multilingual title | 5,000 | 0.37x | -63% |
| medium content | 1,000 | 0.27x | -73% |
| 64 KiB content | 100 | 0.04x | -96% |
| malformed content | 5,000 | 0.46x | -54% |

Plain strings retain a small absolute overhead from object/config facade calls.
Further plain-only branching was not added because it would duplicate grammar
decisions and increase compatibility risk. Repeated multilingual parsing gains
from one lossless parse shared by the selected facade operations.

## Validation

The CI-compatible matrix is green:

| Runtime | Result |
|---|---|
| PHP 8.1.29 / PHPUnit 10.5.64 | 105 tests, 3517 assertions, 0 failures |
| PHP 8.2.29 / PHPUnit 11.5.56 | 105 tests, 3517 assertions, 0 failures |
| PHP 8.3.29 / PHPUnit 11.5.56 | 105 tests, 3517 assertions, 0 failures |
| PHP 8.4.16 / PHPUnit 11.5.56 | 105 tests, 3517 assertions, 0 failures |

`composer test` passed on PHP 8.4.16 with the same result. PHP syntax checks
passed on PHP 8.1–8.4 and `git diff --check` passed.

## Security boundary

The migrated path remains structural only. It preserves HTML and script-looking
text verbatim and does not sanitize, escape, unserialize, evaluate or execute
content. Parsed input cannot influence file paths, includes or filesystem
access. Output-context escaping remains outside the parser.

## Risks before Batch 2

- The cache retains up to 64 complete lossless values per request; unusually
  large unique values can increase peak request memory despite the entry bound.
- Plain short input has a small measurable facade overhead.
- `qtranxf_use*` now consumes the migrated token/split functions indirectly but
  its selection/fallback implementation is unchanged and fully covered by the
  Phase A1 suite.
- Rollback is constant-based and must be defined before plugin bootstrap.
- Join/build and fallback migration must remain separate batches with their own
  differential and output-context tests.

No public signature, database representation, output value, hook or option was
changed. Batch 2 is safe to begin with the escape hatch retained.

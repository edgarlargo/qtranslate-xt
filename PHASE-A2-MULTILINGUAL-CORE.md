# Phase A2 — Multilingual core in shadow mode

## Outcome

The QTX 4 multilingual core now exists beside the legacy parser. It is not
loaded by the plugin bootstrap and no existing `qtranxf_*` function delegates
to it. The legacy runtime remains authoritative.

Before A2 implementation, the Phase A1 gate was run with portable official PHP
builds. PHP 8.1.29, 8.2.29, 8.3.29 and 8.4.16 each passed 39 tests and 265
assertions. Two A1 harness defects were corrected before A2 began: the bootstrap
now assigns configuration through `$GLOBALS`, and the known non-terminating
`qtranxf_join_byseparator()` differing-translation branch is documented rather
than invoked by the suite.

## Files created

- `src/Core/Multilingual/MultilingualEntry.php`
- `src/Core/Multilingual/MultilingualValue.php`
- `src/Core/Multilingual/MultilingualDetector.php`
- `src/Core/Multilingual/MultilingualParser.php`
- `src/Core/Multilingual/MultilingualBuilder.php`
- `tests/Unit/MultilingualCoreTest.php`
- `tests/Benchmark/multilingual-core.php`
- `PHASE-A2-MULTILINGUAL-CORE.md`

Test infrastructure and Composer metadata were updated in `tests/bootstrap.php`
and `composer.json`. No pre-existing production PHP or JavaScript file was
modified. Composer exposes the new namespace through `QTX\\Core\\`, but the
WordPress plugin bootstrap does not load or instantiate it.

## Core responsibilities

### MultilingualEntry

An immutable token-level value describing an opening marker, closing marker or
opaque text fragment. It retains exact raw bytes, original language-code case
and detected marker syntax. Token-level entries preserve duplicates, adjacent
markers, neutral text and malformed fragments without reducing the model to a
language map.

### MultilingualValue

An immutable parse result containing the original raw string, detected syntax,
ordered entries, compatibility translations, encoded-only translations,
available-language observations and diagnostics. Arrays are returned by value
and entries expose no mutators. The `changed` flag is false for parsed storage.

### MultilingualDetector

A stateless cheap marker check equivalent to legacy detection. It does not
instantiate a parsed model and accepts an injected language-code pattern.

### MultilingualParser

A WordPress-independent compatibility parser. Enabled languages, current
language and the language-code pattern are explicit constructor dependencies.
It reads no globals, request variables, options, users or URLs. It tokenizes
once, builds ordered entries, reproduces legacy split/availability behavior and
adds non-destructive diagnostics for adjacent markers, missing/orphan closings
and mixed syntax.

### MultilingualBuilder

Returns the exact original raw string for an unchanged value. Explicit
canonical builds support bracket, comment and curly output and match the legacy
join helpers. Canonicalization is opt-in; comment and curly storage are never
silently converted to bracket storage.

## Parser and security strategy

The grammar intentionally mirrors the actual legacy regular expressions,
including case-insensitive marker recognition and case-preserving captures.
Content is opaque text. The core does not sanitize, escape, unserialize,
evaluate, include files, access the filesystem or execute content. A parsed
`<script>` fragment is returned unchanged; output-context security remains the
responsibility of a later adapter layer.

The raw string plus ordered token entries provide lossless rebuild information.
Derived translation maps exist for compatibility queries but are not the source
of truth for rebuilding unchanged storage.

## Legacy quirks reproduced

- marker matching is case-insensitive while captured language case is retained;
- `{:}` is the curly closing marker and `{::}` remains ordinary content;
- adjacent opening markers overwrite the pending empty language observation;
- duplicate language blocks concatenate in compatibility translations while
  remaining separate ordered entries;
- neutral fragments are copied to each enabled compatibility translation;
- PHP-style outer trimming is retained;
- missing closings still produce translations and a lossless value;
- an orphan recognized closing can trigger the legacy availability fallback to
  the explicitly supplied current language;
- HTML, serialized-looking strings and script-looking input remain opaque.

## Test and parity results

The complete suite contains 69 tests and 504 assertions:

- legacy characterization: 39 tests, 265 assertions;
- new core: 30 tests, 239 assertions;
- result on PHP 8.1.29: pass;
- result on PHP 8.2.29: pass;
- result on PHP 8.3.29: pass;
- result on PHP 8.4.16: pass.

All 27 shared corpus cases matched for detector result, ordered token stream,
compatibility split, encoded-only split, availability and unchanged rebuild.
Applicable canonical bracket/comment/curly output also matched the frozen
expectations. Corpus parity is **100% (27/27 cases)** with no known corpus
difference.

`composer test` passed on PHP 8.4.16 with 69 tests and 504 assertions. PHP syntax
checks passed for every new/test PHP file on all four PHP versions.
`git diff --check` passed.

## Performance comparison

The non-gating benchmark used 10,000 iterations over the same multilingual HTML
sample. Times are wall-clock milliseconds and should be treated as directional:

| PHP | Legacy detect | Core detect | Legacy split | Core object parse | Parse ratio |
|---|---:|---:|---:|---:|---:|
| 8.1.29 | 5.21 | 5.67 | 85.63 | 243.36 | 2.84x |
| 8.2.29 | 5.24 | 6.53 | 86.41 | 226.09 | 2.62x |
| 8.3.29 | 3.80 | 4.08 | 102.68 | 242.00 | 2.36x |
| 8.4.16 | 4.33 | 4.81 | 87.07 | 255.67 | 2.94x |

The cheap detector is close to legacy detection. Full parsing is slower because
it additionally allocates immutable entries, preserves lossless structure and
collects diagnostics. No cache is introduced in shadow mode; request-local
bounded caching remains a later optimization once integration call patterns are
known.

## Known differences and limits

- The new value exposes diagnostics that legacy functions do not expose; they
  do not change parsing output.
- `availableLanguages()` represents legacy `false` for plain input as an empty
  array because the object API has a stable array return type.
- Fallback selection and `show_available` HTML are intentionally not part of
  these five A2 components; legacy `qtranxf_use*` remains authoritative.
- JavaScript is not switched to or coupled with this PHP core.
- The new core is measurably slower for a full parse and is not ready to replace
  hot legacy paths without integration profiling/caching.

## Runtime and A3 readiness

Existing public runtime behavior is unchanged: none of the listed
`qtranxf_isMultilingual`, split, use or join functions was edited or redirected.
Storage, database schema, options, ACF, REST, Gutenberg, language detection,
admin/frontend filters and modules are untouched by A2.

It is safe to proceed to Phase A3 as a shadow/integration phase with the legacy
fallback retained. It is not yet safe to make the new parser authoritative on
hot production paths without explicit performance policy, request-local cache
design and rollback coverage.

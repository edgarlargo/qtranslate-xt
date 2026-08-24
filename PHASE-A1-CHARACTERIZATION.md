# Phase A1 — Legacy parser characterization foundation

## Scope and outcome

Phase A1 adds a minimal PHPUnit foundation and a shared multilingual corpus
without changing production parser behavior. The tests execute the public
legacy functions from `src/language_blocks.php` and the public wrappers in
`src/hooks.php` directly.

No parser replacement, parser refactor, production seam or WordPress database
fixture was introduced. The inline format remains unchanged, including:

`[:lv]Latviešu[:ru]Русский[:en]English[:]`

## Test architecture

- `composer.json` adds PHPUnit as a development-only dependency and exposes
  `composer test`.
- `phpunit.xml.dist` loads `tests/bootstrap.php` and discovers characterization
  and future unit tests.
- `tests/bootstrap.php` supplies the smallest configuration and WordPress hook/
  URL stubs required to load the real parser and wrapper functions.
- `tests/Fixtures/multilingual-corpus.json` is the shared PHP/JavaScript input
  corpus. PHP expectations are explicit; the same raw cases are reusable by a
  future JavaScript parity runner.
- `tests/Characterization/LegacyMultilingualParserTest.php` verifies detection,
  tokenization, splitting, available-language detection, selection/fallback,
  joins, recursive public behavior and wrapper functions.
- `.github/workflows/php-tests.yml` runs the suite on PHP 8.1, 8.2, 8.3 and 8.4.

## Pure versus WordPress-dependent behavior

The following functions are covered as pure characterization tests with only
the legacy global language configuration present:

- `qtranxf_isMultilingual()`;
- `qtranxf_get_language_blocks()`;
- `qtranxf_split()` and `qtranxf_split_blocks()`;
- `qtranxf_split_languages()`;
- `qtranxf_getAvailableLanguages()`;
- `qtranxf_join_b()`, `qtranxf_join_c()` and `qtranxf_join_s()`;
- `qtranxf_allthesame()`, `qtranxf_join_b_no_closing()`,
  `qtranxf_join_byseparator()` and `qtranxf_join_byline()`;
- `qtranxf_use()`, `qtranxf_use_language()` and fallback selection.

The `show_available` branch and the wrappers from `src/hooks.php` normally rely
on WordPress/configuration helpers. They are characterized with deterministic
stubs for `apply_filters_deprecated()`, `qtranxf_getLanguageName()` and
`qtranxf_convertURL()`. Full URL generation, real filters, option loading,
locale setup and integration with posts/editor fields remain WordPress-
dependent integration-test work and are not simulated here.

## Corpus coverage

The corpus contains 27 cases. It includes all three supported opening syntaxes,
the actually recognized curly closing marker, malformed and absent closing
markers, empty and unknown languages, duplicate/case-variant markers, neutral
text, whitespace/newlines, Unicode, HTML and attribute quoting, opaque JSON and
serialized-looking strings, script-looking input, and generated 64 KiB content.

Round trips are classified as `LOSSLESS`, `NORMALIZED` or `LEGACY-QUIRK`. The
classification is metadata backed by exact expected output for each applicable
join function; it is not an assertion that arbitrary malformed text can be
reconstructed byte-for-byte.

## Frozen legacy quirks

The suite intentionally records these behaviors rather than correcting them:

1. Language matching is case-insensitive, while the captured key preserves its
   original case. `[:LV]` therefore creates an `LV` key distinct from `lv`.
2. The curly closing marker recognized by both implementations is `{:}`;
   `{::}` remains content attached to the preceding language.
3. A language marker immediately followed by another marker does not record an
   explicit empty translation. Normal fallback can therefore select another
   enabled language; `show_empty=true` suppresses that fallback.
4. Duplicate language blocks concatenate and are normalized into one block on
   join.
5. Neutral text is copied into every enabled language and outer whitespace is
   trimmed by the PHP splitter.
6. A closing marker without a valid opening marker can make the block count
   appear multilingual to `qtranxf_getAvailableLanguages()`, which then returns
   the current language even though `qtranxf_isMultilingual()` is false.
7. Missing requested translations fall back to the first available language in
   configured enabled-language order.
8. Parser functions preserve HTML, including script-looking content. This is
   deliberate characterization: parsing and output sanitization are separate
   security boundaries.
9. `qtranxf_join_byseparator()` does not terminate for differing translations
   because its internal array pointer is advanced on a copied loop value. The
   executable characterization covers its safe all-translations-equal branch;
   the non-terminating branch is documented rather than run in the test suite.

## PHP and JavaScript parity

Code inspection confirms that both implementations use the same marker forms,
language-code pattern and one-content-block-after-marker state model. The
shared corpus is tagged for both runtimes, but Phase A1 only adds an executable
PHP harness because the JavaScript parser imports browser configuration and the
repository currently has no JavaScript test runner.

Known differences that Phase A2 must preserve or deliberately reconcile:

- PHP `preg_split(..., PREG_SPLIT_NO_EMPTY)` removes empty tokens; JavaScript
  `String.prototype.split()` retains leading, trailing and adjacent empty
  tokens, although `parseTokens()` skips them.
- PHP trims every split translation; JavaScript `parseTokens()` does not trim.
- PHP availability/fallback/join helpers have no complete JavaScript equivalent
  in `js/core/multi-lang/parser.js`.
- Both preserve the original case of a captured language code and both treat
  HTML/script-looking content as opaque parser data.

These are characterization results from the current source, not claims of
sanitization or browser-output safety.

## Execution and verification

The workspace did not contain an executable `php`, `composer` or configured WSL
PHP runtime. Consequently local PHPUnit execution and `php -l` syntax checks
could not run in this environment. The attempted commands fail before loading
project code because the executables are absent; this is an environment block,
not a test failure.

Available local structural checks completed successfully:

- `composer.json` parsed successfully as JSON;
- the shared corpus parsed successfully as JSON and contains 27 cases;
- `git diff --check` reported no whitespace errors;
- Node.js 18.14.1 was available for JSON validation.

The repository's pre-existing `npm test` script was also executed. It exits 1
with `Error: no test specified`; this is the existing package-script placeholder
and not a failure produced by the new PHP test foundation. No JavaScript package
script was changed in Phase A1.

The discovered PHPUnit suite contains 39 data-set executions: 27 corpus cases,
8 fallback cases and 4 standalone helper/recursive/wrapper tests. Their pass/fail
result is **not locally established**. The added CI workflow is the executable
verification path for PHP 8.1–8.4.

Commands to run in a PHP-enabled checkout:

```text
composer install
composer test
php -l tests/bootstrap.php
php -l tests/Characterization/LegacyMultilingualParserTest.php
```

## Compatibility and readiness for Phase A2

Runtime Composer requirements, database formats, option names and production
PHP/JavaScript files are unchanged. PHPUnit is development-only. The CI file is
additive.

The foundation is structurally suitable for Phase A2 because it freezes the
public PHP contract, malformed-input behavior, fallback order, normalization,
opaque HTML handling and known PHP/JavaScript differences. Phase A2 should not
begin until the new matrix has produced a green PHPUnit run; any expectation
that differs under real PHP must be corrected as a characterization fact before
using the suite as a refactoring gate.

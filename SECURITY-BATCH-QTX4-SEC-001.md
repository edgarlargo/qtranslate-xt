# Security Batch — QTX4-SEC-001 configuration textarea output

Date: 2026-09-02

Status: **RESOLVED**

## Original finding

`SECURITY-REAUDIT.md` reported a Medium authenticated-administrator reflected
XSS in the Configuration Files textarea. The reported source and sink were:

```php
$_POST['json_config_files']
    -> <textarea ...><?php echo $_POST['json_config_files'] ?? ... ?></textarea>
```

A raw value such as `"></textarea><svg onload=alert(1)>` is executable when it
is placed directly into that HTML textarea sink because the closing tag leaves
the element and the following SVG becomes markup. The proof used only inert
string assertions; no destructive payload or production site was used.

## Exact request and authorization path

- request method: `POST`;
- page: `/wp-admin/options-general.php?page=qtranslate-xt`;
- form field: `json_config_files`;
- sibling field reviewed and fixed: `json_custom_i18n_config`;
- menu/page capability: `manage_options` in `qtranxf_admin_menu()`;
- update capability: `manage_options` in `qtranxf_edit_config()`;
- nonce field: `_wpnonce`;
- nonce action: `qtranslate-x_configuration_form`;
- state-changing handler: `qtranxf_edit_config()` in
  `src/admin/admin_options_update.php`;
- renderer: `QTX_Admin_Settings::add_integration_section()` in
  `src/admin/admin_settings.php`;
- output context: HTML textarea text/RCDATA.

`qtranxf_verify_nonce()` delegates to WordPress `check_admin_referer()` with an
explicit action. WordPress terminates the request when that nonce is missing or
invalid. The renderer repeats the same verification before displaying the
settings form. The earlier audit statement that an invalid nonce could continue
to the vulnerable renderer was therefore incorrect.

## Complete data flow

For a valid administrator settings submission, the Configuration Files field
followed this path before the fix:

1. PHP populates `$_POST['json_config_files']`.
2. `qtranxf_edit_config()` verifies `manage_options` and the settings nonce.
3. `stripslashes()` removes request slashes.
4. `sanitize_text_field()` checks UTF-8, removes tags and encoded octets, and
   normalizes whitespace.
5. `preg_split()` accepts whitespace/comma-separated paths and `implode()`
   rebuilds a newline-separated scalar.
6. `qtranxf_load_config_files()` validates/loads the paths. On validation error
   the normalized value remains in `$_POST`; on success it is removed and the
   stored configuration becomes the renderer fallback.
7. The renderer echoed the selected value directly inside `<textarea>`.

The direct sink lacked contextual output escaping, but the exact current POST
path did not carry the supplied closing-tag payload through step 4. An invalid
nonce also terminated rather than rendered. Consequently, the originally
reported direct reflected exploit could not be reproduced end to end on the
audited path. The raw sink was still incorrect and unsafe under changed helper
behavior, filter-modified sanitization or a non-request stored fallback.

## Severity validation

- original audit classification: **Medium**;
- validated pre-fix reachability: **Low / defense-in-depth output flaw**, because
  exploitation of the exact request path still required an authenticated
  `manage_options` user, a valid settings nonce, and bypass of the existing
  tag-stripping transformation;
- post-fix status: **Resolved; no executable textarea breakout remains at the
  two configuration sinks**.

The issue remained release-blocking until the late escaping and regression
coverage were complete despite the reduced exploitability.

## Root cause

The renderer relied on earlier input sanitization instead of escaping for the
actual output context. Input validation and output escaping are independent
controls. `sanitize_text_field()` is not a substitute for textarea escaping,
and the stored fallback had no output encoding at the sink.

The structurally identical Custom Configuration textarea also mixed a
sanitized posted value or `json_encode()` fallback directly into textarea text.
It was included in this batch. Other textareas in the settings renderer already
used `esc_textarea()` or contained only fixed placeholders.

## Fix

- `json_config_files` now selects only a scalar posted value or the stored
  fallback and passes the final text through `esc_textarea()` at the sink.
- `json_custom_i18n_config` now builds its display scalar first and passes the
  final text through `esc_textarea()` at the sink.
- array-shaped/repeated-parameter forms for both fields are discarded before
  `stripslashes()` and cannot reach rendering or trigger PHP 8 type errors.
- storage, parser and multilingual marker behavior are unchanged.

`esc_textarea()` encodes `&`, `<`, `>`, single quotes and double quotes for the
textarea context. It is intentionally applied only during output; stored path,
JSON and multilingual values are not pre-escaped.

## Files changed

- `src/admin/admin_settings.php`;
- `src/admin/admin_options_update.php`;
- `tests/Unit/ConfigurationFilesTextareaXssTest.php`;
- `SECURITY-BATCH-QTX4-SEC-001.md`;
- `SECURITY-REAUDIT.md`;
- `SECURITY.md`;
- `RELEASE-READINESS.md`;
- `MODERNISATION-STATUS.md`;
- `SECURITY-FILESYSTEM-INCIDENT.md` (status cross-reference only).

## Regression coverage

`ConfigurationFilesTextareaXssTest` connects the production source contract to
WordPress's textarea escaping semantics and covers:

- normal configuration paths;
- ampersands, angle brackets and greater-than signs;
- single and double quotes;
- a harmless closing-textarea/SVG payload;
- a script-like payload;
- Unicode;
- qTranslate multilingual markers;
- URL-encoded payload text;
- array-shaped/repeated-parameter guards for both fields.

The tests prove that dangerous strings no longer contain executable closing
textarea, SVG or script markup after escaping, while entity decoding returns
the exact original text.

## Validation

- focused security regression, PHP 8.4.16: **9 tests / 37 assertions, PASS**;
- full PHPUnit on PHP 8.1.29, 8.2.29, 8.3.29, 8.4.16 and 8.5.9:
  **332 tests / 7948 assertions per runtime, PASS**;
- `composer test` with PHP 8.4.16 / PHPUnit 11.5.56:
  **332 tests / 7948 assertions, PASS**;
- PHP lint on 179 PHP files: **PASS**;
- JavaScript parser/security tests: **PASS**;
- `git diff --check`: **PASS** (line-ending notices only).

## Compatibility impact

Legitimate paths, JSON text, Unicode, quotes, HTML-special characters and
multilingual markers remain editable and round-trip as text. The browser sees
HTML entities only in source markup and displays the original characters in the
textarea. No option names, public hooks, stored values, parser behavior or
database formats changed.

## Residual risk and final status

This batch does not claim that every legacy administrator template or optional
third-party integration has been exhaustively browser-tested. It closes the two
structurally identical configuration textarea sinks. Other privileged output
and JavaScript DOM locations remain the separately documented hardening scope.

**QTX4-SEC-001 is no longer exploitable through either configuration textarea
and is resolved.** The release remains blocked by the independent WooCommerce,
platform, supply-chain and production-forensics gates documented elsewhere.

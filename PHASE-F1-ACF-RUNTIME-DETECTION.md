# Phase F1 — ACF runtime-capability detection

## Goal

Make ACF activation independent of plugin basenames and filesystem placement so
ACF Free, Pro and theme-bundled runtimes follow the same supported lifecycle.

## Architecture

`AcfRuntimeDetector` checks the runtime API, obtains version information in this
order:

1. official `acf_get_setting('version')` when available;
2. the live `acf()` instance settings;
3. `ACF_VERSION` compatibility constant.

It returns an immutable `AcfRuntime` result containing availability, version and
Pro capability. The existing minimum version 5.6.0 is preserved. No
`is_plugin_active()`, plugin basename or `wp-content/plugins` path is consulted.

The ACF module loader retains immediate initialization plus its
`after_setup_theme` retry, which is required when a theme bundles ACF after the
plugin file loads. A present but unsupported runtime is rejected deterministically.

## Files and compatibility

Added `src/Integration/Acf/AcfRuntime.php`, `AcfRuntimeDetector.php` and tests;
updated the ACF loader and bootstraps. Existing extended fields, settings,
admin UI, hooks and minimum ACF version remain unchanged.

## Tests

PHP 8.1–8.4 each pass 226 tests and 7564 assertions with zero failures. Tests
cover official runtime settings, object fallback representing theme-bundled ACF,
missing/invalid runtime and versions below 5.6.0. PHP lint and
`git diff --check` pass.

## Next step

F2 will establish the explicit ACF field-key schema and safe leaf whitelist
(`text`, `textarea`, `wysiwyg`) in shadow mode before changing save/load hooks.

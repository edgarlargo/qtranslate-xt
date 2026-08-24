# Test installation build report

## Build identity

- Purpose: manual test installation; this is **not a production release**.
- Source branch: `modernisation`
- Source commit: `9492b0a083ec28d69d00d240a8cf04fe01cdc80a`
- Build date: 2026-08-24 15:19:32 +03:00 (Europe/Riga)
- Plugin directory in ZIP: `qtranslate-xt/`
- Plugin header/version: existing `3.16.1`; `QTX_VERSION` was not changed.
- Artifact: `build/qtranslate-xt-test-modernisation.zip`
- ZIP size: 2,285,169 bytes
- SHA-256: `d73019f2073eae7b8d9a10150b0adfdc30af002b9861f045b73722671a54b0cb`

The source tree contains intentional tracked and untracked modernisation
changes beyond the source commit. The package represents the complete current
working-tree runtime state, not a clean checkout of the named commit.

## Runtime dependency decision

Production code does not load `vendor/autoload.php` or Composer-installed
runtime classes. PHP code is loaded from `src/`; therefore `vendor/` is not
required. Browser runtime code is loaded from compiled `dist/` assets, so the
source-only `js/` tree and Node toolchain are excluded.

## Included

- `qtranslate.php`
- `src/`, `dist/`, `css/`, `flags/`, `img/`, `lang/`, `i18n-config/`
- `i18n-config.json`
- `README.md`, `readme.txt`, license and changelog files

## Excluded

- `.git/`, `.github/`, `tests/`, `dev/`
- PHPUnit configuration/cache, `node_modules/`, `vendor/`
- source-only `js/` and Node/Composer build metadata
- architecture/security/phase audit documents
- `BUILD-TEST-REPORT.md`, `MODERNISATION-STATUS.md`, `BLOCKER.md`
- IDE metadata, temporary files, local configuration and build caches

## Validation results

- Latest PHP matrix: PHP 8.1–8.5, **317 tests / 7890 assertions**, PASS on
  every version.
- PHP lint: **173 files, 0 failures**.
- JavaScript shared parser tests: **PASS**.
- Webpack production build: **PASS**.
- `git diff --check`: **PASS** (line-ending notices only).
- Archive inspection: **PASS**, 1,275 entries.
- Required main/runtime files: **PASS**.
- Stable single `qtranslate-xt/` root: **PASS**; no nested plugin root.
- Forbidden development paths and secret-like/local-config filenames: none.
- Plugin header: qTranslate-XT 3.16.1, valid.

## Known incomplete areas

- This is a development test snapshot, not an official release or RC.
- Native ACF Free 6.8.8 and supplied ACF Pro 5.7.7 runtime matrices pass;
  newer ACF Pro versions are not covered by that result.
- The comprehensive WooCommerce transaction/order/email/authenticated REST/
  persistent-cache matrix remains release-blocking.
- MySQL/MariaDB, Classic Editor interactive UI and full historical 3.16.1
  database-upgrade fixtures remain incomplete.

## Installation checklist

1. Back up the target test site and database; do not use production.
2. Upload `qtranslate-xt-test-modernisation.zip` in WordPress Plugins.
3. Confirm replacement/installation targets `wp-content/plugins/qtranslate-xt/`.
4. Activate and inspect PHP/error logs for warnings or fatal errors.
5. Verify existing languages, options and inline multilingual values.
6. Test Classic Editor/Gutenberg, frontend, REST, menus, terms and RSS.
7. With ACF installed, test Options Pages and nested fields in all languages.
8. Test enabled built-in modules and activation/deactivation persistence.
9. Deactivate/reactivate and confirm settings and multilingual storage remain
   unchanged.

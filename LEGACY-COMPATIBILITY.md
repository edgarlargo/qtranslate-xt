# qTranslate-XT / QTX 4 legacy compatibility policy

## Scope and principle

This document is the Phase P inventory and migration policy for the public and de facto-public surface inherited by QTX 4. It is descriptive: no legacy symbol, hook, option or storage format is removed by this phase.

The `qtranxf_*` namespace historically contains both documented helpers and WordPress callbacks. A symbol is therefore not considered private merely because it has no formal API annotation. QTX 4 follows a compatibility-first rule: preserve observable behavior, route implementation through typed services where practical, deprecate explicitly, and remove only in a later major release after a documented replacement and migration window.

Status terms:

- **KEEP** — stable compatibility contract; name, signature and observable behavior remain available.
- **WRAP** — retain the legacy entry point while delegating to a new core/service.
- **DEPRECATE** — retain and emit WordPress deprecation metadata; document a replacement.
- **REMOVE LATER** — eligible only in a future major version after the deprecation conditions below are met.

## PHP API inventory

### Multilingual content facade — KEEP / WRAP

Files: `src/language_blocks.php`, `src/language_config.php`, `src/Core/Multilingual/`.

The following are compatibility-critical and must remain callable:

- detection and splitting: `qtranxf_isMultilingual`, `qtranxf_get_language_blocks`, `qtranxf_split`, `qtranxf_split_blocks`, `qtranxf_split_languages`, `qtranxf_getAvailableLanguages`;
- selection: `qtranxf_use`, `qtranxf_use_language`, `qtranxf_use_block`, `qtranxf_use_content`, `qtranxf_showAllSeparated`;
- building: `qtranxf_join_b`, `qtranxf_join_b_no_closing`, `qtranxf_join_c`, `qtranxf_join_s`, `qtranxf_join_byseparator`, `qtranxf_join_byline`;
- language state: `qtranxf_getLanguage`, `qtranxf_getLanguageDefault`, `qtranxf_getLanguageName`, `qtranxf_getLanguageNameNative`, `qtranxf_isEnabled`, `qtranxf_getSortedLanguages`.

Policy: KEEP the procedural facade and WRAP the QTX 4 multilingual core. Compatibility mode must preserve the Phase A1 corpus, including malformed input, duplicate blocks, marker case and neutral fragments. The `qtranxf_legacy_*` implementations are an internal rollback/parity oracle; they are REMOVE LATER candidates only after the facade has run exclusively on the new core for a full major-release cycle with a green shared corpus.

### URL, detection, date and template facades — KEEP / WRAP

Files: `src/url.php`, `src/language_detect.php`, `src/date_time.php`, `src/widget.php`.

`qtranxf_convertURL`, `qtranxf_convertURLs`, `qtranxf_get_url_for_language`, `qtranxf_parseURL`, `qtranxf_buildURL`, `qtranxf_detect_language`, the snake-case date/time functions and `qtranxf_generateLanguageSelectCode` are de facto integration APIs. KEEP signatures and observable behavior. Security policies and typed services may be introduced behind them. Historical camelCase/date aliases remain DEPRECATE wrappers.

### Integration registry — KEEP

File: `src/integration_api.php`; types under `src/Core/Integration/` and `src/Core/Storage/`.

Preferred QTX 4 extension boundary:

- `qtx_get_integration_registry()`;
- `qtx_register_integration()`;
- `qtx_register_multilingual_field()`;
- `qtx_register_value_adapter()`;
- `qtx_get_term_translation_repository()`;
- action `qtx_register_integrations`.

Registration is trusted PHP configuration, never an option-to-path mechanism. Definitions and adapters remain typed and side-effect free until registry boot.

### Module-prefixed functions — KEEP AS DE FACTO API

Files: `src/modules/*`.

`qtranxf_acf_*`, `qtranxf_wc_*`, `qtranxf_slugs_*`, `qtranxf_wpseo_*`, `qtranxf_aioseop_*` and `qtranxf_eme_*` are primarily callbacks, but third-party code can call or remove them by name. Preserve them through QTX 4. Internal service extraction should use WRAP. Removal requires a module-specific migration note and a major version.

## Explicitly deprecated PHP API — DEPRECATE

File: `src/deprecated.php`.

The existing wrappers and no-op compatibility probes remain loaded and continue using `_deprecated_function()`:

- presence/initialization: `qtranxf_init`, `qtranxf_is_multilingual_deep`;
- camelCase configuration aliases: `qtranxf_admin_loadConfig`, `qtranxf_getLanguageEdit`, `qtranxf_editConfig`, `qtranxf_resetConfig`, `qtranxf_saveConfig`, `qtranxf_reloadConfig`, `qtranxf_updateSetting`, `qtranxf_updateSettingFlagLocation`, `qtranxf_updateSettingIgnoreFileTypes`, `qtranxf_updateSettings`, `qtranxf_loadConfig`;
- gettext/JSON/config aliases: `qtranxf_updateGettextDatabases`, `qtranxf_updateGettextDatabasesEx`, `qtranxf_json_encode`, `qtranxf_config_add_form`, `qtranxf_add_admin_css`, `qtranxf_admin_head`, `qtranxf_fetch_file_selection`;
- date aliases: `qtranxf_convertDateFormatToStrftimeFormat`, `qtranxf_convertFormat`, `qtranxf_convertDateFormat`, `qtranxf_convertTimeFormat`, `qtranxf_strftime`, `qtranxf_validateBool`.

These are REMOVE LATER candidates, not removal approvals. Before removal each symbol needs a documented replacement (or explicit no-op rationale), ecosystem search, replacement tests, and at least one released major line carrying the deprecation.

## WordPress hooks

### KEEP

The extension contract retains names, timing and argument shapes for:

- configuration: `qtranslate_option_config`, `qtranslate_option_config_admin`, `qtranslate_admin_config`, `qtranslate_front_config`, `qtranslate_configuration`, `qtranslate_update_settings`, `qtranslate_save_config`;
- lifecycle: `qtranslate_activation_hook`, `qtranslate_deactivation_hook`, `qtranslate_admin_load_config`, `qtranslate_init_language`;
- language/URL: `qtranslate_detect_language`, `qtranslate_convert_url`;
- QTX 4 integration: `qtx_register_integrations`;
- file policy: `qtranslate_i18n_config_roots`, `qtranslate_i18n_config_maximum_bytes`.

Security filters may broaden trust only when invoked by trusted PHP. Stored data must never register executable paths or service definitions.

### DEPRECATE

`qtranslate_admin_loadConfig` is retained via `do_action_deprecated()` with `qtranslate_admin_load_config` as replacement. Other camelCase aliases must follow the same pattern rather than disappearing silently.

## Options, metadata, cookies and globals

### Options — KEEP names and serialized shapes

The configured option family in `src/options.php` and `src/admin/admin_options.php` is authoritative. Compatibility-critical names include:

- language data: `qtranslate_default_language`, `qtranslate_enabled_languages`, `qtranslate_language_names`, `qtranslate_locales`, `qtranslate_locales_html`, `qtranslate_na_messages`, `qtranslate_date_formats`, `qtranslate_time_formats`, `qtranslate_flags`;
- existing `qtranslate_*` frontend/admin settings derived by `qtranxf_load_option*` and `qtranxf_update_option*`;
- modules: `qtranslate_modules_state`, `qtranslate_module_acf`, `qtranslate_module_slugs`;
- i18n configuration: `qtranslate_config_files`, `qtranslate_custom_i18n_config`, `qtranslate_admin_config`, `qtranslate_front_config`, `qtranslate_config_errors`;
- compatibility/lifecycle: `qtranslate_qtrans_compatibility`, `qtranslate_next_thanks` and existing notice/settings options.

QTX 4 does not rename these keys or migrate database formats implicitly. Reads may normalize into typed in-memory objects; writes preserve the established schema. Unknown module-state IDs remain inert and cannot become filesystem paths. Legacy import aliases such as `qtranslate_modules` remain migration inputs, not new storage APIs.

### Metadata — KEEP

- term translations and their legacy option fallback;
- slug metadata prefix `qtranslate_slug_` plus language code;
- WooCommerce order/user language metadata currently consumed by the module, including `_user_language`;
- existing post/user metadata containing inline multilingual strings.

No format migration is permitted merely to adopt QTX 4 services. Technical WooCommerce metadata remains opaque and untranslated.

### Cookies and globals

Cookie names `qtrans_front_language` and `qtrans_admin_language` are KEEP. The global `$q_config` and `$qtranslate_options` remain populated for compatibility, but new code should consume explicit configuration/services. Direct third-party reads are retained through QTX 4 and become DEPRECATE only after equivalent accessors exist; direct writes are unsupported.

## Storage syntax — KEEP for QTX 4

All formats remain readable and losslessly rebuildable:

- bracket: `[:lv]Latviešu[:ru]Русский[:en]English[:]`;
- comment: `<!--:lv-->Latviešu<!--:--><!--:en-->English<!--:-->`;
- curly: `{:lv}Latviešu{:ru}Русский{:}`.

The parser treats payloads as opaque text. Parsing, HTML sanitization and output escaping are separate concerns. QTX 4 does not silently normalize syntax, discard malformed fragments, collapse duplicates, change captured language-code case, or migrate stored values.

## JavaScript compatibility

Files: `js/core/`, runtime artifacts in `dist/`.

The global `qTranx` namespace, its public editor/language helpers and `qTranx.hooks` are KEEP through QTX 4. Existing aliases in `js/core/hooks/deprecated.js` are DEPRECATE wrappers. New scoped modules may be used internally, but bundles retain globals required by classic editor, admin configuration, ACF and third-party scripts. Source is authoritative and `dist/` is the runtime artifact; changes require JS tests and a reproducible rebuild.

## Constants and classes

- `QTX_VERSION`, `QTRANSLATE_DIR`, `QTRANSLATE_FILE` and public URL/path constants are KEEP.
- option, URL-mode, editor-mode, cookie and format constants in `src/options.php` are KEEP through QTX 4.
- `QTX_Translator` remains a compatibility facade; implementation belongs behind it.
- QTX 4 namespaced types used by the integration API are KEEP; constructor changes require a compatibility factory or major-version migration.

## Removal gates

No DEPRECATE or REMOVE LATER item may be removed until:

1. a replacement and migration example are published;
2. runtime deprecation has shipped for at least one supported major line;
3. repository and known-integration searches show no unhandled callers;
4. characterization, PHP 8.1–8.4, JavaScript and relevant real-WordPress tests remain green;
5. release notes identify the exact removal and compatibility impact;
6. option keys and stored multilingual values remain readable unless a separately approved, reversible migration exists.

## Phase P result

Phase P changes documentation only. No PHP/JavaScript runtime behavior, hook timing, database schema, option name, module activation rule or multilingual format changed. QTX 4 keeps procedural/global surfaces as compatibility facades while typed core and integration services become the implementation boundary.

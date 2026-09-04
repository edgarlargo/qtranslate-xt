# ACF compatibility

Date: 2026-09-04
Branch: `modernisation`

## Native architecture

qTranslate-XT now registers the ACF module before themes load when the module
is enabled. The trusted loader does not load ACF itself and does not inspect an
ACF filesystem path. `AcfRuntimeBootstrap` waits for the official `acf/init`
lifecycle, retries at `after_setup_theme`/`init` for late embedded runtimes, and
initializes the adapter exactly once. An already-fired `acf/init` is detected
through `did_action()`.

Module availability uses the generic runtime-provider predicate
`qtx_module_runtime_available_{module}`. The stored module state remains an
enable/disable preference; it cannot supply code paths. ACF plugin basenames
are fallback information for the legacy settings screen, not the authoritative
runtime signal. qTranslate-XT never adds fake ACF entries to `active_plugins`.

## Value contract

- Storage remains one unchanged inline multilingual value per leaf.
- Admin/editor context is `raw`; frontend context is `translated`.
- The mode is derived from QTX's resolved request context, not from
  `is_admin()`, and can be narrowly overridden by trusted PHP through
  `qtx_acf_value_context`.
- Translation is registered only on official field-specific ACF hooks for
  `text`, `textarea` and `wysiwyg` (plus their deprecated QTX equivalents).
- Options values are supported for both `option` and `options` post IDs.
- ACF stable `field_*` references prevent global QTX option/metadata filters
  from translating values before ACF has supplied the field type.
- Group, Repeater and Flexible Content use ACF's own recursive formatting;
  only whitelisted child leaves receive QTX's field-specific formatter.
- image, file, number, boolean, IDs, URL, email, relationship, post object,
  color, coordinates, layouts, objects and serialized technical values are not
  translated by the ACF adapter.
- The parser remains an opaque-text parser; it performs no HTML sanitation.

The existing qTranslate ACF admin JavaScript attaches the normal QTX content
hook to standard Text/Textarea/WYSIWYG fields. Official ACF `new_field/type=*`
actions cover dynamically appended Group/Repeater/Flexible leaves. It does not
create `name_lv`/`name_ru`/`name_en` fields and requires no external bridge.

## Executed compatibility matrix

| Scenario | Result | Evidence |
|---|---|---|
| ACF Free plugin runtime | **PASS**, 6.8.8 | clean WordPress 7.1 lab |
| Theme-bundled/custom-path ACF | **PASS**, 6.8.8 | plugin deactivated; theme `inc/acf/acf.php`; no fake active entry |
| ACF loaded after QTX bootstrap | **PASS** | real theme load plus bootstrap unit test |
| Exactly-once initialization | **PASS** | lifecycle unit tests |
| ACF Pro runtime capability detection | **PASS unit contract** | injectable Pro runtime predicate; no basename/path dependency |
| ACF Pro runtime | **PASS, 5.7.7** | supplied package; WordPress 7.1 / PHP 8.4 disposable lab |
| Options storage via `option` / `options` | **PASS with ACF Free core API** | real `update_field`/`get_field`; raw DB values retained |
| Text/Textarea/WYSIWYG frontend LV/RU/EN | **PASS**, ACF Free 6.8.8 | four observed production fixtures |
| Admin raw/edit/reload UI | **PASS server lifecycle/render; interactive JS NOT EXECUTED** | raw markers rendered; QTX core/ACF bundles present in real Options Page HTML |
| Group | **PASS, real ACF Pro runtime** | multilingual and technical child leaves |
| Repeater/Flexible Content | **PASS, real ACF Pro runtime** | multilingual leaves projected; technical/layout values retained |
| ACF Pro Options Page | **PASS runtime/storage/enqueue** | registration/lookup, raw storage, translated reads, required admin assets |
| Built-in Options language tabs | **PASS local and CI; interactive browser click NOT EXECUTED** | initial/dynamic field contract, text-only DOM, production bundle reproducibility |
| Technical fields unchanged | **PASS unit and storage-boundary tests** | field whitelist and stable reference boundary |

Real fixture values included:

```text
[:lv]Sazināties ar mums[:ru]Связаться с нами[:en]Contact us[:]
[:lv]Nosūti mums ziņu![:ru]Отправьте нам сообщение![:en]Send us a message![:]
[:lv]Vārds Uzvārds[:ru]Имя Фамилия[:en]Name Surname[:]
[:lv]Sūtīt[:ru]Отправить[:en]Send[:]
```

All normal ACF Text/Textarea/WYSIWYG frontend reads returned only the selected
language. The corresponding WordPress options remained byte-for-byte inline
multilingual strings after LV/RU/EN reads.

## Built-in ACF Options Bridge Safe

The complete editing behavior of `qTranslate-XT ACF Options Bridge Safe 0.4.0`
is native. The value adapter registers its type-specific format filters before
the late ACF runtime bootstrap. For ordinary Text/Textarea fields, the ACF admin
bundle creates one local edit panel per enabled language and keeps the original
named ACF input as the single serialized submission field. This works on
Options Pages and other ACF forms without depending on a global language switch.
Initial fields and dynamically appended Group/Repeater/Flexible Content leaves
are handled through current `new_field/type=*` and ACF 5.x `append` actions.

Plain legacy values are assigned only to the configured default language. The
native parser reads bracket, comment and curly formats, while serialization uses
the canonical bracket format and preserves content from currently disabled
languages. Tab labels and editors are created with DOM properties and
`textContent`; the bridge does not write HTML, register an endpoint, install
another ACF copy or mutate `active_plugins`. If the standalone 0.4 bridge is
still active, its marker is detected so a second editor is not attached.
Existing explicit ACF-module preferences are respected. On a new configuration
the tabs are enabled by default and can be disabled in the ACF integration
settings without disabling value translation.

Post-audit GitHub Actions run
[`33869856763`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856763)
passed the bridge contract in the 349-test / 8064-assertion PHP 8.1–8.5 matrix,
six JavaScript tests and the reproducible production build. Run
[`33869856719`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33869856719)
installed and retested the exact archive containing `dist/modules/acf.js`.
The independently downloaded archive matched SHA-256
`449209b7a6856a63426389dbe6d43f3df773fbf2fc26942d786f6e7d908b0047`.
That archive was subsequently withdrawn after a real theme-embedded/legacy
Options path exposed its complete marker string on the frontend. The cause was
an architectural difference from the standalone plugin: the native value
adapter still depended on legacy ACF module state and modern field metadata.

The replacement adds a core, type-specific priority-99 fallback for ACF Text,
Textarea and WYSIWYG values. It is registered after language detection without
checking `active_plugins`, the legacy module-state option or field metadata;
normal wp-admin editing remains raw, while frontend and admin AJAX use the
selected language exactly like Safe Bridge 0.4.0. A regression reproduces the
reported EN/LV/RU/FI/SV `Location / Year` value. Run `33871964457` passed PHP
7.4/8.0 syntax, 353 tests / 8078 assertions on PHP 8.1–8.5 and six JavaScript
tests; Woo run `33871964443` passed 176/176 assertions. The delta security audit
passed with no confirmed findings. Only the final post-audit exact-ZIP gate is
pending; interactive verification on the reported live theme remains unexecuted.

## ACF Pro validation result

The supplied ACF Pro 5.7.7 package was exercised in a disposable WordPress 7.1 /
PHP 8.4 installation. Native Options Page registration/lookup, scalar raw
storage and LV/RU/EN projection, Group, Repeater and Flexible Content all
passed. Technical values and Flexible layout keys remained unchanged, and the
runner removed its fixture values.

The test exposed and fixed an admin lifecycle ordering defect: the ACF admin
object was previously created only at `acf/init`, after QTX had already applied
`qtranslate_admin_config`. Admin hook registration now occurs when the trusted
module loader runs, while value adapters still initialize on `acf/init`.
The real Options Page HTML now contains both `dist/core.js` and
`dist/modules/acf.js`. The available browser surface did not execute page
JavaScript, so mouse/keyboard language-tab interaction is not claimed as an
executed browser test. No ACF-specific external-resource blocker remains for
the tested 5.7.7 package; compatibility with newer ACF Pro versions is not
inferred.

The disposable vendor-runtime runner is
`tests/Integration/acf-native-runtime-smoke.php`. In a dedicated installation
with QTX, ACF and LV/RU/EN enabled, execute it once per requested language:

```text
wp eval-file tests/Integration/acf-native-runtime-smoke.php
```

It tests scalar Options storage plus Group and, when the real Pro field types
exist, Repeater/Flexible Content and Options Page capability. It refuses an
existing fixture namespace and removes all fixture options in `finally`.

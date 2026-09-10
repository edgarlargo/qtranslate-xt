# Elementor compatibility

Date: 2026-09-10

## Data model

Elementor remains the sole owner of `_elementor_data`, its JSON schema,
responsive settings, layout identifiers and generated CSS. The legacy
qTranslate post-meta projector explicitly passes this key through to the normal
WordPress metadata reader, so JSON syntax is not translated before Elementor
decodes it. The boundary neither rewrites nor updates the document and all
other Elementor metadata keeps its normal WordPress path.

Supported text controls store the established qTranslate-XT scalar format:

```text
[:lv]Par mums[:ru]О нас[:en]About[:]
```

One Elementor page or template therefore contains all translations. Separate
pages per language are not required.

## Editor workflow

The built-in editor bridge is loaded through Elementor's official
`elementor/editor/after_enqueue_scripts` action. When the settings panel opens,
it adds isolated LV/RU/EN-style language panels to standard controls of these
types:

- Text;
- Textarea;
- WYSIWYG (edited as HTML text in the language panels).

Changing a language panel serializes the complete scalar value back through
the original Elementor control's native `input` and `change` events. This lets
Elementor keep responsibility for the active widget, nested panel context,
history and document save.

Plain existing content is assigned to the configured default language when it
is first edited. Values belonging to a disabled historical language are kept
when the field is saved.

## Frontend behavior

PHP projection runs at Elementor's final rendered-content boundary,
`elementor/frontend/the_content`. It intentionally does not translate at the
individual widget boundary: Elementor may cache that intermediate HTML, which
would otherwise allow the first rendered language to leak into later requests.

The selected qTranslate language is applied to the finished HTML without an
“available only in” notice. A small DOM observer also covers content rendered
or replaced dynamically after page load. It changes text nodes and only the
safe visible attributes `alt`, `aria-label`, `placeholder` and `title`.

## Intentional boundaries

The bridge does not translate or rewrite:

- URLs (`href`, `src`) or Elementor link controls;
- element IDs, CSS classes, custom CSS, HTML tag choices or responsive values;
- media IDs, galleries, queries, taxonomy selectors or other structured data;
- scripts, styles, code/preformatted blocks or editable editor surfaces;
- arbitrary Elementor post metadata or serialized JSON.

Third-party widgets work when they expose normal Elementor Text, Textarea or
WYSIWYG controls. Proprietary editors and structured/dynamic data sources need
a consumer-specific adapter. ACF and WooCommerce values remain the
responsibility of their dedicated qTranslate-XT integrations.

## Validation status

Pre-audit run `34489034016` is **PASS** with real Elementor 3.35.9 and 4.2.4,
WordPress 7.1, PHP 8.4 and MySQL 8.4. Both jobs installed the exact candidate
archive and verified:

- valid, unchanged Elementor JSON plus stable element ID and technical URL;
- LV/RU/EN heading, body and button output on separate public routes;
- no raw marker or cross-language cache leakage;
- editor/frontend production assets are registered from the archive.

PHP run `34489033988` passed 366 tests / 8165 assertions per PHP 8.1-8.5
runtime, PHP 7.4/8.0 syntax, seven JavaScript tests, npm audit and reproducible
bundles. Woo regression run `34489034009` also remained green at 176/176.

The editor bridge has executable parser/serialization and DOM-sink contracts,
but an interactive browser session clicking language tabs and saving a page was
not automated. Custom widgets are covered only when they use the supported
standard control types; templates and proprietary controls require separate
validation.

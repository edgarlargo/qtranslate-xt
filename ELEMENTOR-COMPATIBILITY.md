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

PHP projection runs at the rendered widget/content boundary:

- `elementor/widget/render_content`;
- `elementor/frontend/the_content`.

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

The source has unit and JavaScript contracts for hook registration, language
round trips, disabled-language preservation, output projection and forbidden
DOM/metadata sinks. Interactive execution against a pinned Elementor release
must pass in CI before a version range or final release claim is made.

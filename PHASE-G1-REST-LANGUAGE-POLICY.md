# Phase G1 — REST language and representation policy

## Goal

Define the security boundary for translated and raw REST representations before
replacing the legacy global Gutenberg request interceptor.

## Policy

`RestLanguagePolicy` consumes an explicit QTX `LanguageContext` and resolves a
request into `RestTranslationContext`:

- only `view` and `edit` contexts are accepted;
- an explicit language must belong to the configured `LanguageCatalog`;
- absent language uses the explicit current context language;
- public `view` is translated;
- `edit` requires an object/controller capability decision supplied by the
  WordPress adapter;
- raw representation requires both `context=edit` and that capability;
- invalid language/context and forbidden edit/raw requests return distinct
  errors and never silently downgrade to raw.

The policy reads no REST request object, route, user, global configuration,
database or URL. It therefore cannot mistake `is_admin()` for authorization.

## Files and compatibility

Created `RestTranslationContext`, `RestLanguagePolicy` and tests; updated both
bootstraps. No existing REST/Gutenberg hook or response is changed in G1. The
legacy interceptor remains authoritative pending route-scoped replacement.

## Tests

PHP 8.1–8.4 each pass 242 tests and 7608 assertions with zero failures. Tests
cover public defaults, configured languages, hostile/unknown language values,
invalid contexts, edit capability and raw disclosure rules. PHP lint and
`git diff --check` pass.

## Next step

G2 will add an opt-in route/object adapter that supplies real route scope and
capability results to this policy. It must not activate merely because a global
`qtx_editor_lang` parameter exists.

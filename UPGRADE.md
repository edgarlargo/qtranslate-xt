# Upgrade and rollback guide

## Status

The current modernisation branch is a development candidate, not an official
qTranslate-XT release. Do not deploy it to production without a full backup and
site-specific staging test.

## Compatibility contract

- Existing `qtranslate_*` options and metadata names are retained.
- Existing bracket, comment and curly multilingual values remain readable.
- No activation-time content migration or normalization is performed.
- The canonical inline format remains
  `[:lv]Latviešu[:ru]Русский[:en]English[:]`.
- Legacy public `qtranxf_*`, compatibility facades, hooks and JavaScript globals
  remain available as described in `LEGACY-COMPATIBILITY.md`.

## Staged upgrade procedure

1. Back up the database and `wp-content` completely.
2. Clone production into staging; do not test first on the live site.
3. Record current qTranslate-XT, WordPress, PHP, ACF/ACF Pro, WooCommerce and
   cache/plugin versions.
4. Replace the plugin directory with a package whose internal folder is exactly
   `qtranslate-xt/`; do not run two qTranslate forks simultaneously.
5. Activate and verify configured/default languages and URL mode.
6. Verify legacy posts/pages/options/terms in every enabled language without
   saving them first.
7. Test Classic Editor and Gutenberg save/autosave/revision/conflict behavior.
8. Test all ACF field groups and Options Pages, especially nested Pro fields.
9. Test WooCommerce product, cart, checkout, order emails, REST/AJAX and payment
   staging flows without rewriting historical orders.
10. Review logs and only then schedule production deployment with rollback
    access.

Do not run the irreversible database conversion tools as part of a normal
upgrade. They are manual recovery/migration utilities and now require explicit
backup confirmation.

## Rollback

1. Deactivate the development build.
2. Restore the prior `qtranslate-xt/` plugin directory.
3. Reactivate it and flush permalinks if URL behavior requires it.
4. If any settings or content were edited after upgrade, restore the database
   backup before assuming byte-for-byte rollback.

Normal deactivate/reactivate does not delete qTranslate data. Uninstall/data
retention behavior must still be reviewed for the specific deployment.

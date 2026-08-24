# Phase F5.2 — Dynamic ACF admin fields

## Scope

The existing ACF admin integration now attaches qTranslate content hooks to text
and textarea fields appended after initial page load by Group, Repeater and
Flexible Content controls.

## Implementation

The initial field scan remains for compatibility with ACF versions whose
`new_field` event runs before `qtranx.load`. In addition, the integration uses
ACF's `new_field/type=text` and `new_field/type=textarea` JavaScript actions for
dynamically appended fields. The common attachment path accepts either an ACF
field model or a DOM element and retains the existing post-type and module
whitelist checks. Newly attached fields synchronize to the current explicit QTX
language in LSB mode.

WYSIWYG fields retain the existing `wysiwyg_tinymce_settings` lifecycle because
ACF replaces their textarea DOM during editor initialization. Existing field
types, stored values, ACF Options Page handling and extended-field behavior are
unchanged.

## Source/dist synchronization

- Source: `js/acf/load.js`
- Runtime bundle: `dist/modules/acf.js`

`npm ci --ignore-scripts` completed. It reported an engine warning because the
local Node 18.14.1 is below babel-loader's recommended 18.20 minimum, plus ten
development dependency audit findings. The actual Webpack production build
completed successfully with webpack 5.102.0. No automatic dependency upgrade or
lockfile mutation was performed.

## Validation and remaining work

The PHP 8.1–8.4 suite remains green at 264 tests and 7674 assertions. JavaScript
source/dist build synchronization passes. Browser-level ACF Free/Pro,
theme-bundled ACF, Options Page, dynamic row removal and WYSIWYG lifecycle tests
remain required; F5 is therefore not yet marked complete.

# Built-in integrations compatibility status

Date: 2026-09-10

This document records Phase L2 source validation. It does not claim runtime
compatibility with third-party versions that were not installed and executed.

| Integration | Built-in module | Integration boundary | Automated local status | Real plugin status |
|---|---|---|---|---|
| Yoast SEO | `wp-seo` | Yoast metadata/canonical/schema/breadcrumb filters; QTX admin config | Loader registry/security PASS | **NOT TESTED** |
| All in One SEO | `all-in-one-seo-pack` | legacy AIOSEO frontend filters and admin selectors | Loader registry/security PASS | **NOT TESTED** |
| Gravity Forms | `gravity-forms` | render/submission/confirmation/email hooks | Loader registry/security PASS | **NOT TESTED** |
| Jetpack | `jetpack` | related-post result projection | Loader registry/security PASS | **NOT TESTED** |
| Google Site Kit | `google-site-kit` | canonical home URL isolation | Loader registry/security PASS | **NOT TESTED** |
| Events Made Easy | `events-made-easy` plus packaged i18n config | QTX language lifecycle/admin field configuration | Loader and packaged JSON policy PASS | **NOT TESTED** |
| Slugs | `slugs` | WordPress post/term rewrite, metadata and admin lifecycle | Unit/security/SQL source tests PASS | **NOT TESTED in real WP** |

ACF, WooCommerce and Elementor are tracked separately because their storage or
rendering boundaries require dedicated compatibility evidence.

## Elementor

The built-in bridge keeps Elementor's private JSON under Elementor ownership,
adds editor language panels only to standard Text/Textarea/WYSIWYG controls and
projects translations at finished page HTML plus safe visible attributes.
Unit/JavaScript/source validation and real frontend execution are **PASS** for
Elementor 3.35.9 and 4.2.4 on WordPress 7.1. The exact archive passed isolated
LV/RU/EN heading, body and button routes without marker or cache leakage.
Interactive editor click/save automation, templates and proprietary controls
remain outside that version claim. See `ELEMENTOR-COMPATIBILITY.md`.

## Loading and trust boundary

All modules are declared by `QTX_Admin_Module::get_modules()`. The authoritative
module registry resolves their built-in `loader.php` files, applies canonical
module-directory containment and uses `qtranslate_modules_state` only to
activate a known ID. Third-party options cannot supply code paths. Missing or
inactive upstream plugins leave modules inactive through the existing module
manager state/detection contract.

## Source observations

### Yoast SEO

The module uses named public Yoast filters for canonical/Open Graph URL,
metadata, schema pieces, breadcrumbs and previous/next links. It deliberately
disables Yoast indexable persistence because a single language-neutral
indexable representation is incompatible with request-language projection.
Hook availability and data shapes are version-sensitive. No supported Yoast
range is claimed until current versions are exercised.

### All in One SEO

The implementation targets historical AIOSEO hook/admin identifiers. Modern
AIOSEO architecture may no longer expose all of them. Preserve the module as a
legacy compatibility path, but mark modern compatibility **NOT TESTED** rather
than treating loader activation as proof.

### Gravity Forms

Presentation text, choices, conditional-rule values, buttons, confirmations
and outgoing email subject/message are projected through the current QTX
language. IDs and submission values are not intentionally rewritten. Upstream
field object shapes and frontend/AJAX forms require real tests. The module also
retains `qtrans_*` fallback calls for the legacy compatibility mode.

### Jetpack and Google Site Kit

Jetpack translation is limited to related-post titles/excerpts. Site Kit uses
the raw WordPress home option for its canonical connection identity so QTX URL
filters do not disconnect the service. Neither module changes credentials,
analytics identifiers or remote-request payloads.

### Events Made Easy

The PHP module adds admin configuration on the QTX language lifecycle; its
packaged JSON is loaded through the hardened i18n policy. Event save/display
behavior and upstream field IDs remain **NOT TESTED**.

### Slugs

Slug metadata remains language-keyed with the existing option/meta formats.
O1 parameterized dynamic SQL values and O2 protects mass migration entry
points. Rewrite/canonical behavior, multisite and WooCommerce permalink
interaction still require a real WordPress matrix.

## Release policy

Optional integrations do not block the entire release when their external
environment cannot be reproduced. Their current paths remain available and
their limitations are explicit. No third-party version range is claimed here.
Any future claim requires recorded plugin/WordPress versions and executed admin,
frontend, save and relevant REST/AJAX scenarios.

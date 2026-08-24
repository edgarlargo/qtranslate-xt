# Phase A4 — Language resolver and translation service

## Goal

Make language catalog, request context and fallback policy explicit core values,
and move selection decisions out of parsing without changing legacy output.

## Files created

- `src/Core/Multilingual/LanguageCatalog.php`
- `src/Core/Multilingual/LanguageContext.php`
- `src/Core/Multilingual/LanguageRequest.php`
- `src/Core/Multilingual/FallbackPolicy.php`
- `src/Core/Multilingual/LanguageResolver.php`
- `tests/Unit/LanguageResolverTest.php`
- `PHASE-A4-LANGUAGE-RESOLVER.md`

Updated: `TranslationService.php`, `src/init.php`, `tests/bootstrap.php` and
`MODERNISATION-STATUS.md`.

## Design

`LanguageCatalog` owns deterministic configured order and default language.
`LanguageContext` owns current/default values. `LanguageRequest` combines an
explicit requested language with immutable `FallbackPolicy`. Invalid catalog or
context configuration throws a developer-facing configuration exception; no
content parse throws.

`LanguageResolver` consumes translations and availability without reading
WordPress. It returns `TranslationResult` with selected text/language, ordered
availability and a reason: `exact`, `default`, `first-available`, `empty` or
`unavailable`.

`TranslationService::get()` accepts `MultilingualValue`, request and context and
adds `plain` handling. The legacy-shaped `select()` method now constructs an
explicit compatibility policy and delegates the resolver, preserving the A3
procedural facade.

Unknown marker languages remain lossless and can still be selected exactly by
the compatibility API. External request adapters must validate requested
languages against the catalog before constructing public REST/frontend context.

## Compatibility and security

Fallback order, explicit-empty handling and procedural presentation remain
unchanged. No parser reads `$q_config`; only the procedural adapter converts
legacy configuration to explicit arguments. No storage, hooks, options or
database behavior changed.

Core content remains opaque: no HTML sanitization, escaping, deserialization,
execution or filesystem access occurs.

## Tests

- Catalog ordering/default membership and invalid configuration.
- Context validation.
- Exact/default/first/empty/unavailable result semantics.
- Parsed multilingual and plain value service API.
- All prior corpus/generated/use differential tests remain green.
- PHP 8.1–8.4: 184 tests, 7382 assertions, zero failures on every runtime.
- `git diff --check`: green.

## Rollback and risks

The A3 constant restores preserved procedural selection bodies. A4 classes are
otherwise additive. Developer exceptions must be caught/translated by future
request adapters; they must never be exposed as raw REST errors.

## Next phase

Phase A5 consolidates bounded caching, adversarial/fuzz coverage and benchmark
budgets before WordPress frontend adapters consume the service broadly.

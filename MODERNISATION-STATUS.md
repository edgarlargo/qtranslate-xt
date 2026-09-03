# qTranslate-XT modernisation status

## Current phase

Phase H REST/Gutenberg controller foundation is complete through H2. Phase I1
has removed the final executable `strftime()` calls while preserving the public
legacy date wrapper. F5.1 now defines the lossless ACF admin edit/merge contract;
F5.2 now covers fields dynamically appended through official ACF JavaScript
actions. J1 now enforces canonical/configured hosts through WordPress safe
redirect validation. K1 now enforces approved canonical roots and schema/size
limits for i18n-config. L1 now protects WooCommerce technical/order data while
retaining existing presentation paths; L2 replaces global webhook cache flushing
with narrow presentation groups. M1 adds executable shared-corpus JavaScript
parity and synchronized runtime bundles. N1 prohibits PHP object hydration at
qTranslate-owned sinks. O1 replaces manual SQL value interpolation in migration,
slug lookup and uninstall paths. Phase P records the KEEP / WRAP / DEPRECATE /
REMOVE LATER compatibility policy for legacy APIs, hooks, storage and globals.
N2 finds no confirmed open Critical/High and closes QTX-SEC-002 with an
authorized, nonced admin-notice AJAX flow. Integration fixtures remain in
progress. O2 now allowlists database maintenance actions, requires explicit
backup confirmation, and confines SQL splitting to trusted canonical roots.
Optional built-in integrations have a source-validated, explicitly NOT TESTED
real-plugin matrix in `BUILTIN-INTEGRATIONS-COMPATIBILITY.md`. Phase Q local
matrix is green at 317 tests / 7890 assertions on each PHP 8.1–8.5; production
sources also lint cleanly on PHP 7.4 and 8.0; required
real testing now covers WordPress 7.1, ACF Free/theme-bundled 6.8.8,
WooCommerce 11.0.1 smoke paths and Gutenberg save/autosave/409 conflicts. ACF
Pro 5.7.7 Options Page/Group/Repeater/Flexible runtime tests now pass;
the comprehensive Woo transactional gate now passes. Native ACF Free,
Pro and theme-bundled runtime/value tests are documented in
`ACF-COMPATIBILITY.md`.

The comprehensive Woo matrix passed in disposable GitHub Actions run
[`33754558280`](https://github.com/edgarlargo/qtranslate-xt/actions/runs/33754558280).
Its source review and failed-run remediation fixed HPOS order-language storage,
frontend lifecycle fidelity and canonical term fixtures. The final result is
173/173 assertions on WordPress 7.1, WooCommerce 11.0.1, PHP 8.4, MySQL 8.4.11
and Redis 7.4.11. Local PHP 8.4 is green at 335 tests / 7979 assertions.

Historical 2026-09-02 attempt: the release-gate retry passed `actionlint`
1.7.12 and reconfirmed the
workflow's pinned disposable WordPress 7.1, WooCommerce 11.0.1, PHP 8.4, MySQL
8.4 and Redis 7.4 environment. The local `modernisation` branch remains absent
from `origin`; GitHub CLI, a signed-in browser session and stored push
credentials are all unavailable. A Windows fallback started a separate MySQL
8.0.31 datadir successfully, but could not extract the WordPress 7.1 archive
because of a trailing-dot filename. That lab was shut down and removed. No
MySQL/Redis Woo result was available at that checkpoint and the gate was NOT
RUN then.

That review found and fixed a workflow provisioning defect before dispatch:
the old WP-CLI `releases/latest` asset URL returned 404. The job now obtains the
official WP-CLI 2.12.0 build, validates its fixed SHA-256 and pins Redis Object
Cache 2.8.0. Three workflow contract tests add 15 assertions; the resulting
full PHP 8.1-8.5 matrix is green at 335 tests / 7963 assertions per runtime.

The exact `4.0.0-rc1` development ZIP has additionally passed fresh WordPress
7.1 installation, activation, LV/RU/EN frontend, ACF Pro 5.7.7 and
deactivate/reactivate checks. Its local Woo transaction attempt confirmed the
documented MySQL requirement when SQLite rejected WooCommerce stock-reservation
locking SQL.

Security Batch QTX4-SEC-001 now applies late `esc_textarea()` output encoding
to both configuration textareas and rejects non-scalar request shapes before
string processing. Exact validation corrected the earlier nonce-free reflection
claim: WordPress terminates an invalid settings nonce and the current update
path strips tags, but the raw output sinks were still fixed and regression
tested. The full PHP 8.1-8.5 matrix is green at 332 tests / 7948 assertions.

## Completed work

- Security Batch QTX4-SEC-001: configuration textarea sinks remediated with
  contextual escaping, scalar guards and adversarial regression coverage.
- Architecture and security audits completed.
- Phase N2: current-tree security re-audit in `SECURITY-REAUDIT.md`;
  QTX-SEC-002 is capability/nonce protected and no confirmed open
  Critical/High finding remains.
- Phase I2: remaining deprecated filter sanitizers removed from the dormant ACF
  context helper; public compatibility facades remain intact.
- Phase O2: destructive conversion confirmation, strict action/language input
  and canonical `.sql` input roots documented in
  `PHASE-O2-DATABASE-OPERATION-SAFETY.md`.
- Phase L2 optional integrations: loader/trust boundaries and per-plugin limits
  documented without unsupported version claims.
- Phase Q: explicit PASS/NOT TESTED/BLOCKED matrix in `FULL-TEST-MATRIX.md`;
  all executable local gates pass, but the RC quality gate is not green.
- Phase H3/R: route-scoped editor revisions and real WordPress 7.1 validation
  documented in `PHASE-H3-GUTENBERG-REVISION-PROTOCOL.md` and
  `REAL-WORDPRESS-TEST.md`.
- Phase P: legacy compatibility inventory and removal gates documented in
  `LEGACY-COMPATIBILITY.md`; no runtime behavior changed.
- Security Batch 1: QTX-SEC-001 admin CSRF remediation.
- Security Batch 2: QTX-SEC-005 trusted module registry/path boundary.
- Phase A1: characterization foundation and shared 27-case corpus.
- Phase A2: lossless multilingual core in shadow mode.
- Phase A3.1: detector/token/split/availability facade migration.
- Phase A3.2a: pure bracket/comment/curly join facade migration.
- Phase A3.2b: line/separator join facades; legacy non-termination replaced by
  bounded deterministic progression.
- Phase A3.3: use/fallback selection facades and core `TranslationService`.
- Phase A3.4: current/default wrappers and `QTX_Translator` compatibility
  verified without unnecessary production changes.
- Phase A4: explicit language catalog/context/request/policy/resolver and object
  translation service.
- Phase A5: centralized bounded parser cache, adversarial coverage and cold/
  cached benchmarks.
- Phase B1: declarative `the_title` callback mapped to a thin WordPress adapter.
- Phase B2: `the_content` and `the_excerpt` named adapters with preserved hook
  contracts.
- Phase B3: primary RSS title/content/excerpt named adapters.
- Phase B4: `the_posts` collection moved to a named adapter with exact mutable
  object and navigation-menu bypass parity.
- Phase B5: declarative term filters moved to a named adapter with scalar,
  object and collection parity.
- Phase B6: navigation-menu hook moved behind a named adapter while retaining
  the complete legacy implementation and public wrapper.
- Phase C1: exact, schema-aware field registry for option/post/term/user storage
  added in shadow mode without runtime filter changes.
- Phase C2: registered scalar adapter added with explicit language context and
  no array/object traversal or deserialization.
- Phase C3: request-scoped exact `option_{key}` adapter added with symmetric
  registration/removal and no automatic production activation.
- Phase C4: registered post/term/user metadata pre-filter adapter added with
  explicit raw-provider/fall-through and cache invalidation contracts.
- Phase C5: `qtranslate_term_name` lost-update race fully traced; unsafe quick
  fixes rejected and a backward-compatible term-scoped repository path defined.
- Phase D1: trusted integration descriptors/registry added with runtime
  predicates, object services and deterministic duplicate diagnostics.
- Phase D2: public integration/field/value-adapter facades and one-shot
  `qtx_register_integrations` lifecycle hook added before adapter/module boot.
- Phase C6: term-ID repository introduced with meta-first reads, lazy legacy
  fallback and dual-write compatibility; object/slugs consumers migrated.
- Phase E1: all nine secured built-in loaders exposed as trusted provider
  services from the existing authoritative module registry.
- Phase F1: ACF detection moved to runtime APIs with Free/Pro/theme-bundled
  support and preserved version/lifecycle behavior.
- Phase F2: bounded ACF schema discovery added for stable text/textarea/wysiwyg
  leaf keys across group/repeater/flexible structures.
- Phase F3: pure schema-aware ACF projector added with compound shape,
  technical-field and serialization boundaries preserved.
- Phase F4 native runtime: generic late-dependency discovery, official
  `acf/init` exactly-once bootstrap and field-type-specific `acf/format_value`
  adapters now run in production with explicit raw/translated QTX context.
  Stable ACF references defer generic option/metadata translation until the
  field-aware pipeline; ACF Free and theme-bundled 6.8.8 pass a real lab.
- Phase G1: explicit REST view/edit/raw policy added with configured-language
  validation and capability-gated raw representation.
- Phase G2: trusted exact entity-route registry added; unknown routes and mere
  language parameters cannot activate multilingual mutation.
- Phase G3/H1: lossless editor projection and revision-token merge added;
  stale writes conflict and malformed/duplicate sources are not rebuilt.
- Phase H2: registered edit-only REST post field adapter added with explicit
  capability checks, batch merge and HTTP 409 conflict mapping.
- Phase I1: deprecated `qtranxf_strftime()` compatibility wrapper migrated from
  PHP `strftime()` to the existing Intl formatter without changing its public
  signature or QTX-specific token preprocessing.
- Phase F5.1: stable whitelisted ACF leaves now have an explicit lossless
  projection/revision/merge contract shared by posts, nested values and Options
  Pages; legacy ACF admin hooks remain authoritative pending the UI bridge.
- Phase F5.2: dynamically appended Group/Repeater/Flexible text and textarea
  fields are attached through official ACF `new_field/type=*` actions, with the
  source and production bundle rebuilt together.
- Phase J1: language redirects use `wp_safe_redirect()` with a scoped
  home/site/network/configured-domain allowlist, closing QTX-SEC-007 at the
  plugin sink.
- Phase K1: i18n-config paths are restricted to canonical plugin/content roots,
  `.json`, size and minimal versioned schema boundaries; external roots require
  trusted PHP registration, closing QTX-SEC-003 hardening.
- Phase L1: WooCommerce technical metadata, cart hashing and explicit order
  language have a tested policy; real WooCommerce behavior remains NOT TESTED.
- Phase L2: WooCommerce webhook raw-mode invalidation is group-scoped and no
  production `wp_cache_flush()` remains.
- Phase M1: the actual JavaScript parser passes all 27 shared corpus cases with
  PHP semantics; Webpack source/dist synchronization passes.
- Phase N1: all qTranslate-owned deserialization uses `allowed_classes=false`;
  QTX-SEC-006 hardening is remediated without claiming historical exploitability.
- Phase O1: option-prefix, translated-slug lookup and uninstall SQL values now
  use LIKE escaping and placeholders; no mass operation was executed.

## Current test status

- PHP 7.4 and 8.0 production lint: zero syntax errors.
- PHP 8.1–8.5: 335 tests, 7963 assertions per version, zero failures/errors.
- Shared JS/PHP corpus parity: 100% (27/27); generated parser parity 400/400.
- PHP lint (180 files), Webpack build, JS tests and
  `git diff --check`: green.
- Real WordPress 7.1/PHP 8.4: activation/deactivation, EN/DE frontend, public
  REST, ACF Free 6.8.8 Text/Textarea/WYSIWYG option reads and theme-bundled
  runtime without fake plugin state, WooCommerce 11.0.1
  product/cart/checkout-load/fragments smoke, and Gutenberg save/autosave/409
  conflict pass. See `REAL-WORDPRESS-TEST.md`.

## Known blockers

The ACF Pro 5.7.7 and WooCommerce MySQL/Redis blockers are resolved. Release
candidate work is now blocked by the mandatory final security re-audit and any
release-blocking remediation it identifies. Details are in `BLOCKER.md` and
`RELEASE-READINESS.md`.

## Security status

- QTX4-SEC-001 is resolved: both configuration textareas escape at the output
  sink and reject array-shaped values; focused and full-matrix tests pass.
- QTX-SEC-001 and QTX-SEC-005 remediated in focused batches.
- QTX-SEC-007 has been remediated with safe redirect/host allowlisting.
- QTX-SEC-003 is remediated with the K1 file policy.
- QTX-SEC-006 hardening is remediated with class-restricted deserialization.
- Parser/core remains structural and never sanitizes, executes, unserializes or
  accesses files based on parsed content.

## Compatibility status

- Inline bracket and all supported legacy formats remain readable.
- No mandatory migration or silent normalization exists.
- Six low-risk legacy parser functions use QTX 4 facades with a tested rollback
  constant and 100% differential parity.
- Public signatures, hooks, options and database formats remain unchanged.

## Next planned phase

Perform the final security re-audit, fix every release-blocking finding, then
build and validate the final release-candidate ZIP. Do not reorder these gates.

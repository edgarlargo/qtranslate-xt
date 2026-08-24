# QTX 4: эволюционная архитектура qTranslate-XT

Дата: 2026-08-21
Статус: целевой дизайн, без реализации
Основание: `ARCHITECTURE-AUDIT.md`, `SECURITY-AUDIT.md`, `SECURITY-VALIDATION.md` и проверка текущих parser/filter/REST/ACF/module paths.

## Executive summary

qTranslate-XT может эволюционировать без big-bang rewrite и без обязательной миграции данных. Главный сохраняемый контракт — inline storage:

```text
[:lv]Latviešu[:ru]Русский[:en]English[:]
```

QTX 4 должен сделать этот формат реализационной деталью Storage Codec, а не неявным протоколом между каждым filter, editor и интеграцией. Центральное ядро предлагается как гибрид:

- immutable `MultilingualValue` хранит разобранное значение и диагностику;
- stateless `MultilingualParser` и `MultilingualBuilder` отвечают только за структуру;
- `LanguageResolver` определяет язык и fallback plan;
- `TranslationService` выбирает значение, не sanitizing/escaping его;
- adapters связывают API с WordPress, REST, ACF и Gutenberg;
- процедурный facade сохраняет прежние функции и hooks.

Первая реализационная фаза — characterization tests существующего parser-а и новый Value API за старыми функциями. Изменение storage, REST или редакторов на этом этапе не требуется.

## 1. Core design principles

### A. Storage

Storage — точные bytes/string, сохранённые WordPress, например `[:lv]Sveiki[:ru]Привет[:en]Hello[:]`. Слой знает, где значение находится (`post_content`, option, meta), но не выбирает язык и не решает, безопасен ли HTML.

### B. Parsing

Parsing преобразует raw string в структурированное значение. Он распознаёт bracket, legacy comment и curly syntax, фиксирует malformed fragments и сохраняет оригинал. Он не обращается к WordPress globals.

### C. Language selection

Resolver получает requested/current/default language, enabled languages и fallback policy. Он не читает БД и не разбирает markers.

### D. Presentation

Adapters возвращают raw либо selected content конкретному consumer: theme, REST, editor, ACF, feed. Они выбирают output context, но не дублируют parser.

### E. Security

Validation относится к identifiers/config/request parameters. Sanitization относится к недоверенному вводу согласно типу поля. Escaping выполняется непосредственно у HTML/attribute/URL/JSON/JS sink.

Эти слои независимы, потому что один raw value может быть допустимым HTML post content, plain-text title, JSON string или editor payload. Sanitizing внутри parser-а уничтожил бы legitimate HTML и markers, а escaping внутри parser-а вызвал бы double escaping. **Multilingual parser не является HTML sanitizer.**

## 2. Multilingual Value API

### Рекомендованная форма: hybrid

Core использует value objects/classes; compatibility и простой integration surface — процедурные функции. Классы дают типы, diagnostics и тестируемость. Facade снижает migration cost для procedural WordPress ecosystem.

### Канонические компоненты

#### `MultilingualDetector::isMultilingual(?string $raw): bool`

- Input: nullable string.
- Output: true только при распознанной multilingual structure согласно compatibility grammar.
- Errors: не throws для content; null/empty/plain text → false.
- Malformed: isolated marker без валидной структуры по умолчанию false либо true-with-diagnostics только в explicit tolerant inspection API; legacy behavior фиксируется tests.
- Unicode/HTML: opaque bytes/UTF-8 text, без преобразования.
- Performance: быстрый marker pre-check, без полного parse.

Procedural facade: концептуальный `qtx_is_multilingual()`; legacy `qtranxf_isMultilingual()` вызывает его.

#### `MultilingualParser::parse(string $raw, ParseOptions $options): MultilingualValue`

- Input: raw string и mode `COMPATIBILITY` (default) либо `STRICT_DIAGNOSTIC`.
- Output: immutable value с original raw, ordered entries, syntax, prefix/suffix fragments и diagnostics.
- Errors: content errors не бросают exceptions; infrastructure/programmer errors могут. Malformed input возвращает lossless value + diagnostics.
- Empty translations сохраняются как explicit entries.
- Unknown syntactically valid language codes сохраняются и помечаются unknown относительно переданного catalog; parser не решает, enabled ли язык.
- Unicode не normalizes автоматически; bytes сохраняются. Language IDs валидируются отдельно как ASCII identifiers.
- HTML, JSON, serialized-looking strings — opaque content.
- Performance: request-local cache по raw+options fingerprint.

Procedural facade: `qtx_parse_multilingual()` может возвращать array DTO; object API остаётся canonical.

#### `TranslationService::get(MultilingualValue|string $value, LanguageRequest $request): TranslationResult`

- Input: parsed/raw value, requested language и immutable fallback policy.
- Output: `TranslationResult` с selected text, selected language, reason (`exact`, `default`, `first-available`, `empty`, `plain`) и availability.
- Fallback: явно задан policy; default compatibility policy повторяет текущие `show_available/show_empty/use_default` варианты.
- Empty: explicit empty отличается от missing. Policy решает, считать ли empty окончательным результатом.
- Malformed: compatibility mode использует legacy selection; strict consumers могут получить raw/unavailable, но не silently discard fragments.
- HTML остаётся неизменным.

Procedural facade: `qtx_get_translation()` и wrappers старого семейства `qtranxf_use*()`.

#### `MultilingualValue::withTranslation(string $lang, string $text, UpdatePolicy $policy): MultilingualValue`

- Input: validated language ID, exact new text, update policy.
- Output: новый value object; исходный не мутируется.
- Errors: invalid ID → typed error/exception на developer API; public request adapter конвертирует в `WP_Error`/400.
- Empty string — explicit translation, не delete. Удаление — отдельный `withoutTranslation()`.
- Order: existing entry сохраняет позицию; new entry добавляется по catalog order либо в конец согласно policy.
- HTML/Unicode сохраняются точно.

Procedural facade: `qtx_set_translation()` возвращает rebuilt string либо structured result по documented signature.

#### `MultilingualBuilder::build(MultilingualValue|array $value, BuildOptions $options): string`

- Default output: bracket syntax, но compatibility round-trip для untouched value возвращает original raw.
- Не выполняет silent normalization. Canonicalization доступна только explicit command/options.
- Duplicate/malformed entries не теряются в lossless mode.
- Empty entries строятся как `[:lv][:ru]...`.
- HTML/JSON не меняются.
- Performance: value хранит lazy rebuilt string; unchanged object возвращает original.

Procedural facade: `qtx_build_multilingual()`; legacy `qtranxf_join_b/c/s()` остаются explicit codecs.

#### Language catalog/context API

- `LanguageCatalog::codes(): list<string>` — configured/known codes, deterministic order.
- `LanguageContext::current(): string` — request language после resolver.
- `LanguageContext::default(): string` — site default.
- Facade: `qtx_get_languages()`, `qtx_get_current_language()`, `qtx_get_default_language()`.
- До полной DI-миграции facade адаптирует `$q_config`; core objects не читают global напрямую.
- Отсутствующий/invalid context даёт typed configuration error; frontend adapter может безопасно использовать default.

## 3. Internal data model

Концептуальная модель:

```json
{
  "original": "[:lv]Sveiki[:ru]Привет[:en]Hello[:]",
  "syntax": "bracket",
  "entries": [
    {"language": "lv", "content": "Sveiki", "ordinal": 0},
    {"language": "ru", "content": "Привет", "ordinal": 1},
    {"language": "en", "content": "Hello", "ordinal": 2}
  ],
  "diagnostics": [],
  "dirty": false
}
```

Нормализованный lookup (`language => selected entry`) является derived index, а не единственной моделью: простой map потерял бы order, duplicates и malformed fragments.

- Language order важен для byte-compatible rebuild, UI и legacy first-available fallback.
- Duplicate markers сохраняются как entries. Compatibility selector воспроизводит legacy winner; diagnostics сообщает duplicate. Explicit normalization policy может выбрать first/last, но не автоматически.
- Unknown languages сохраняются losslessly. Catalog validation выполняется resolver/editor adapter.
- Empty entry хранится как present+empty; missing — отсутствие entry.
- Fallback metadata не записывается в raw value; `TranslationResult` содержит runtime decision.
- Malformed content хранит diagnostics с offsets и исходными fragments. Untouched build возвращает original.
- Никакой Unicode NFC/NFD normalization по умолчанию: она меняет bytes и может ломать signatures/search.

## 4. Parser architecture

### Текущее состояние

PHP logic распределена по `src/language_blocks.php`: `qtranxf_isMultilingual`, `qtranxf_get_language_blocks`, `qtranxf_split`, `qtranxf_split_blocks`, `qtranxf_split_languages`, `qtranxf_use*`, `qtranxf_join_b/c/s`. JS имеет отдельный parser в `js/core/multi-lang/parser.js`. Regex/state logic распознаёт три синтаксиса, unknown codes и текст вне блоков; PHP/JS semantics могут расходиться.

Legacy behavior, которое сначала фиксируется characterization tests:

- bracket `[:xx]...[:]`, comment `<!--:xx-->...<!--:-->`, curly `{:xx}...{::}`;
- plain text и prefix/suffix;
- fallback/current/default behavior;
- order и empty blocks;
- uppercase/unknown legacy codes;
- damaged/unclosed/duplicate/nested-looking markers;
- join variants и отсутствие closing marker в отдельных legacy paths.

### Центральный parser service

Parser — deterministic scanner/tokenizer с offsets, а не набор consumer-specific regex. Regex допустим только для распознавания marker token, но управление state централизовано. PHP implementation является authoritative server grammar; JS получает общий corpus/spec и parity tests. На первом этапе JS не переписывается — он проверяется против fixtures.

### Обязательное поведение corpus

| Input | Результат |
|---|---|
| `[:lv]Hello[:ru]Привет[:]` | entries lv/ru; exact selection |
| `[:lv][:ru]Привет[:]` | lv present+empty; ru present+non-empty |
| plain text | non-multilingual value; selection возвращает original |
| duplicate language | оба entries сохраняются; compatibility winner + diagnostic |
| unclosed/malformed marker | lossless original + diagnostic; no invented data |
| unknown valid code | entry сохраняется; resolver решает availability |
| nested-looking marker | marker grammar определяет token transition; deterministic diagnostic, никакого recursion/code execution |
| HTML attributes | content opaque; quotes/tags не меняются |
| JSON text | opaque; parser реагирует только на genuine QTX tokens |
| serialized-looking string | opaque; parser никогда не вызывает `unserialize()` |

Parser никогда не выполняет PHP/JS, не включает files, не sanitizes и не escapes HTML.

## 5. Storage compatibility

### Общие правила

- Нет обязательной миграции.
- Чтение поддерживает все legacy syntaxes.
- Untouched value не переписывается.
- Save конкретного translation меняет только необходимое значение и сохраняет прочие languages/fragments максимально точно.
- Canonical rewrite — отдельная admin/CLI операция с dry-run, backup, capability, nonce и audit log.
- Старые qTranslate-XT должны читать новые записи: default builder продолжает bracket syntax и не добавляет новый DB envelope/JSON.

### WordPress locations

| Location | Default mode | Policy |
|---|---|---|
| `post_title` | raw при edit/save, translated при display | plain-text escaping у consumer |
| `post_content` | raw при edit/save, translated frontend | сохранять legitimate HTML/KSES semantics WordPress |
| `post_excerpt` | как content | fallback policy может отличаться явно |
| `wp_options` | opt-in adapter only | не фильтровать все options глобально в target state |
| `postmeta`, `termmeta`, `usermeta` | opt-in registered keys/schema | не интерпретировать objects/serialized data без adapter |
| ACF | field-schema whitelist | recursive compound adapter для supported content fields |

Автоматическое normalization/rewrite запрещено по умолчанию, включая обычное чтение, cache warmup и plugin upgrade.

## 6. WordPress filter architecture

Текущие parsing consumers находятся в `src/hooks.php`, `src/frontend.php`, taxonomy/date/url/admin modules и declarative `i18n-config`. Основные broad hooks включают `the_title`, `the_content`, `the_excerpt`, `the_posts`, term/menu/RSS/gettext/options/metadata и URL hooks. Parsing/selection сейчас вызываются непосредственно из callbacks.

Целевая схема callback-а:

```text
WordPress callback arguments
  → ContextPolicy (RAW/TRANSLATED, language, field type)
  → ValueAdapter::read(raw)
  → TranslationService
  → consumer-specific output (без глобального escaping)
```

Filters становятся thin adapters: type guards, context, service call, return. Регистрация filters сосредоточена в adapter registry, а не разбросана по bootstrap.

Migration:

1. characterization tests каждого hook;
2. старый callback вызывает facade нового service;
3. сохраняются priorities, accepted args, hook names и qTranslate filters;
4. новые typed hooks добавляются параллельно;
5. broad metadata/options filters постепенно заменяются registered adapters;
6. legacy hooks deprecate только после telemetry и двух release cycles, но не удаляются в первых modernization releases.

## 7. RAW vs TRANSLATED context

```php
enum ValueMode { case RAW; case TRANSLATED; }
```

- `RAW`: точное storage representation, например `[:lv]Sveiki[:ru]Привет[:]`.
- `TRANSLATED`: selected content и metadata результата, например `Sveiki`.

Policy defaults:

| Consumer | Mode |
|---|---|
| Classic/Gutenberg/ACF editor | RAW transport либо explicit per-language projection с full raw concurrency token |
| frontend/theme/feed | TRANSLATED |
| REST `context=edit` с permission | explicit RAW/per-language editor representation |
| public REST `context=view` | TRANSLATED |
| DB migration/export | RAW |
| search indexing | explicit site policy; никогда context guess |
| WP-CLI | required `--raw`/`--language`, безопасный documented default |

API не определяет mode через `is_admin()`. `TranslationContext` передаётся явно. Для legacy callbacks context adapter временно выводит mode из конкретного hook, не из глобальной эвристики.

## 8. REST API architecture

### Language selection

Приоритет должен быть детерминирован и route-specific: validated explicit query (`lang`) или namespaced header → route/default context → site default. URL prefix может заполнять тот же `LanguageRequest`, но не переписывать глобально `REQUEST_URI`. Language должен быть в configured allowlist.

### Permission model

- Public `context=view`: только translated representation, если route schema не объявляет иначе.
- `context=edit`: raw/per-language editor data только после исходного controller permission callback и object-specific capability (`edit_post`, etc.).
- Отдельный raw flag не повышает privilege; при отсутствии permission — 403.
- Public raw может быть opt-in только для явно зарегистрированного field/route без sensitive languages; default — запрещён.

### Schema

```json
{
  "title": "Sveiki",
  "qtx": {"language": "lv", "fallback": false}
}
```

Privileged edit response:

```json
{
  "title": {
    "raw": "[:lv]Sveiki[:ru]Привет[:]",
    "translations": {"lv": "Sveiki", "ru": "Привет"},
    "revision": "content-hash-or-post-modified-token"
  }
}
```

Не следует глобально перехватывать любой POST/PUT с `qtx_editor_lang`, как делает текущий `QTX_Admin_Block_Editor`. Используется registered REST field/controller adapter с schema, route/object checks и optimistic concurrency.

Cache key обязан включать language, mode, fallback policy и permission-sensitive context; response headers (`Vary`) добавляются только для реально используемого language header. Raw privileged responses — private/no-store по необходимости.

## 9. ACF architecture

ACF adapter загружается на `acf/init` и через runtime API detection (`function_exists('acf')`, `class_exists`/official hooks), поэтому поддерживает ACF, ACF Pro и ACF bundled in theme. `is_plugin_active()` не является единственным detector.

### Default whitelist

Multilingual по умолчанию: `text`, `textarea`, `wysiwyg`. Опционально content-like `message` только если он хранится/редактируется как content. Technical types (`image`, `file`, `number`, `boolean`, IDs, relationship, post object, URL, email) не переводятся автоматически.

### Compound fields

- Group: рекурсивно только registered multilingual subfields.
- Repeater: каждая row сохраняет структуру, переводятся только whitelisted leaf fields.
- Flexible Content: layout/name/keys технические; переводятся whitelisted leaves выбранного layout.
- Options Pages: тот же field-key registry, object scope `option`, capability конкретной page.

### Configuration API

```php
qtx_register_multilingual_field([
  'provider' => 'acf',
  'field_key' => 'field_...',
  'value_type' => 'html|text',
  'storage' => 'inline',
  'fallback' => 'site-default'
]);
```

Регистрация по immutable ACF field key предпочтительнее CSS class/name. Admin JS получает schema, но server повторно validates. WYSIWYG сохраняет WordPress/ACF sanitization policy; QTX parser её не заменяет.

## 10. Gutenberg architecture

Текущий code в `src/admin/block_editor.php` глобально перехватывает REST POST/PUT с `qtx_editor_lang`, читает текущий post из DB, split/join title/content/excerpt и затем подменяет response. Это создаёт route ambiguity и lost-update risk.

### Подход A: inline encoding в selected block fields

Плюсы: granular translation и меньше скрытых whole-content merges. Минусы: markers внутри `post_content` block serialization/HTML attributes могут нарушать block validation; нужны schemas каждого block и third-party block; dynamic/reusable blocks усложняют модель.

### Подход B: editor-level language projection при сохранении inline storage

Editor получает all-language structured state/raw + revision token, показывает один язык, а server atomic merge выполняет только для registered post fields. Block markup выбранного языка остаётся обычным Gutenberg content; inline markers существуют между целыми language versions underlying `post_content`, а не внедряются в произвольные block attributes.

### Рекомендация

Для эволюции безопаснее B. Он совместим с текущим whole-field storage и не требует понимать каждую block schema. Условия:

- editor store держит translations всех языков либо получает их при switch;
- save несёт base revision/hash; conflict → 409, не silent overwrite;
- autosave и revisions сохраняют полный raw multilingual value;
- reusable blocks/patterns являются отдельными entities со своими capabilities/adapters;
- block attributes переводятся только через future explicit block-field registry, не generic string traversal;
- REST controller scope ограничен posts/revisions/autosaves конкретного editor flow.

Подход A можно позже добавить opt-in для schema-aware blocks, не как default.

## 11. Integration API

Стабильный registry API должен регистрировать declarations/services из trusted PHP:

- integration descriptor: ID, version, runtime predicate, services;
- multilingual field: provider/object/key/value type/fallback;
- value adapter: read/write semantics для конкретного storage type;
- storage adapter: capabilities и atomic update contract.

Названия `qtx_register_integration()`, `qtx_register_multilingual_field()`, `qtx_register_value_adapter()` разумны как facade, но финализируются после prototype и naming review. Canonical OO registry получает typed descriptors.

Registration выполняется на documented hook `qtx_register_integrations`, до adapter boot. Duplicate ID — deterministic error/diagnostic. Third-party code не редактирует core, не зависит только от plugin basename и не передаёт executable paths через options.

## 12. Module architecture

Урок `QTX-SEC-005` закрепляется как invariant:

```text
trusted PHP registry
  → known module ID
  → canonical loader/service factory
  → option state for known ID only
  → instantiate service
```

`qtranslate_modules_state` хранит только state. Unknown IDs игнорируются. Paths проходят canonical boundary; предпочтительно Composer/service factories вообще устраняют runtime include construction.

Migration от текущего loader:

1. сохранить существующий `QTX_Admin_Module::get_modules()` как source of truth;
2. использовать уже внедрённый allowlisted/canonical loader boundary;
3. добавить typed descriptor/factory без изменения option;
4. перенести built-ins по одному на service providers;
5. future third-party modules регистрируются выполняющимся trusted PHP, но option не регистрирует loader.

## 13. Security model

| Boundary | Trust rule |
|---|---|
| DB raw content | данные, не код; HTML trust зависит от field/capability history |
| authenticated admin input | не доверять автоматически; capability + nonce + validation + field sanitation |
| frontend visitor | недоверенный request; strict language/URL validation |
| REST client | route permission + object capability + schema validation |
| third-party plugin PHP | trusted executing code, но declarations валидируются и conflicts диагностируются |
| filesystem/module registry | только trusted registry/canonical files; options не задают paths |

Rules:

- validate identifiers, enums, shapes и bounds до processing;
- sanitize при write согласно field schema, не parser grammar;
- escape у sink: `esc_html`, `esc_attr`, `esc_url`, `wp_kses`, safe JSON/JS encoding по контексту;
- state change: exact capability, POST, nonce; `is_admin()` не authorization;
- REST: обязательный `permission_callback`, object capabilities, schema `validate_callback`/`sanitize_callback`;
- raw mode никогда не означает trusted output;
- parser не вызывает `unserialize()` и не рекурсирует по arbitrary objects.

## 14. Performance

- Request-local bounded cache: key = hash/raw identity + parser version + options. Слабые ссылки/size cap предотвращают неограниченную память.
- `MultilingualValue` lazy-строит language index и rebuilt string.
- Один parse перед несколькими selections; adapters передают object дальше.
- Persistent cache не вводится сначала: invalidation для arbitrary options/meta сложнее потенциальной выгоды. Позже возможен cache parsed DTO по object revision и parser version, но raw остаётся source of truth.
- Не parse plain strings после дешёвого marker pre-check.
- Не unserialize/re-serialize values ради перевода; storage adapter обязан знать тип.
- Memory tradeoff: offsets/diagnostics дороже map. Для hot frontend compatibility mode допускает compact tokens; diagnostic form создаётся по запросу.
- Benchmark corpus: short titles, large posts, repeated metadata, malformed adversarial input; measure hit rate, time и peak memory до optimization.

## 15. Backward-compatibility layer

| Категория | API |
|---|---|
| KEEP | inline syntaxes, option/meta names, documented hooks, current language/default semantics |
| WRAP | `qtranxf_isMultilingual`, `qtranxf_split`, `qtranxf_use*`, `qtranxf_join_*`, `QTX_Translator`, primary frontend callbacks |
| DEPRECATE | duplicated camelCase aliases, direct global-state helpers, consumer-specific parser paths, executable/deprecated i18n config |
| REMOVE ONLY IN FUTURE MAJOR | aliases после telemetry/docs/two-cycle notice; legacy syntax readers только после отдельной long-term policy (рекомендуется сохранять indefinitely) |

Первые modernization releases ничего не удаляют. Wrapper сохраняет signature, return types, hooks и emits deprecation только для уже deprecated API. Новый API versioned/documented; deprecation содержит replacement, release и migration example. Removal требует usage evidence и отдельного major release.

## 16. Test architecture

### Unit

- detector/parser/token offsets для всех syntaxes;
- build/parse lossless round-trip и explicit canonicalization;
- selection/fallback/empty/missing/unknown/duplicates;
- Unicode, HTML, JSON, serialized-looking, nested/malformed/large inputs;
- PHP↔JS shared corpus parity.

### WordPress integration

- posts/title/content/excerpt, revisions/autosaves;
- options и registered metadata без object hydration;
- term/user meta;
- ACF Free/Pro/theme-bundled, Options Pages, group/repeater/flexible;
- REST view/edit permissions, schemas, cache keys, conflicts;
- module registry/lifecycle;
- Classic Editor/Gutenberg matrix.

### Security regression

- CSRF actions: method/nonce/capability;
- module traversal/corrupted option;
- parser preserves script text while field policy blocks/escapes it appropriately;
- malformed/oversized input bounded;
- raw REST disclosure permissions;
- hostile Host/redirect policy;
- no unsafe deserialization in Value API.

Fixtures должны включать реальные legacy values из qTranslate/qTranslate-X/XТ, mixed syntaxes, missing closing tags, HTML blocks и multilingual serialized containers (для adapter tests, не parser deserialization).

## 17. Incremental migration plan

| Phase | Goal | Files/subsystems | Risk/compatibility | Required tests | Rollback |
|---|---|---|---|---|---|
| A | Value object/parser facade за legacy API | `language_blocks.php`, new Core files, `class_translator.php` | parser semantic drift — высокий | characterization, corpus, parity, benchmarks | feature flag возвращает legacy implementation |
| B | Frontend filters как adapters | `hooks.php`, `frontend.php`, taxonomy/date | hook order/return type | per-hook WP integration snapshots | callback-level legacy flag |
| C | Options/meta adapters | `frontend.php`, new Storage registry | broad plugin compatibility, serialization | registered/unregistered meta/options, cache | retain existing filters per key/site flag |
| D | Integration registry | new Integration API, i18n-config bridge | duplicate IDs/config behavior | registry lifecycle/conflicts | declarations disabled, legacy config active |
| E | Module service providers | `src/modules/*`, loader/manager | third-party assumptions | all built-ins, activation/deactivation/reset | canonical loader remains fallback |
| F | First-class ACF adapter | ACF module/JS | ACF versions/compound fields | Free/Pro/theme/options/compound matrix | legacy ACF module toggle |
| G | Modern REST | `rest_api.php`, REST adapters | public shape/cache/permissions | route/context/permission/cache tests | old REST behavior behind compatibility flag |
| H | Gutenberg editor state | `block_editor.php`, JS, REST | data loss/concurrency — высокий | autosave/revision/conflict/block fixtures | Classic/legacy single-language path |
| I | Deprecate duplicates | deprecated facade/JS parser paths | ecosystem callers | telemetry + compatibility suite | postpone removal; wrappers remain |

### Phase details

**A:** сначала freeze legacy behavior, затем implement parser without changing storage/output. Это recommended first phase.
**B:** мигрировать по одному hook, начиная с title/content, не менять priorities.
**C:** отказаться от «перевести всё» в пользу explicit registrations; rollout opt-in.
**D:** bridge читает старый i18n-config, но преобразует его в non-executable descriptors.
**E:** security boundary уже может существовать до service-provider refactor.
**F:** content fields сначала, compound recursive adapters после leaf tests.
**G:** version/feature negotiation при изменении response schema.
**H:** optimistic concurrency обязательна до write interception replacement.
**I:** удаление не является целью QTX 4.0; только измеряемая deprecation.

Каждая phase должна быть releasable отдельно, с site-level opt-out и без DB migration.

## 18. Target architecture diagram

```text
                    INPUT SECURITY BOUNDARY
        capability / nonce / REST permission / validation
                              │
                              ▼
┌──────────────────────────────────────────────────────────┐
│ DATABASE / RAW STORAGE                                  │
│ posts · options · postmeta · termmeta · usermeta · ACF  │
│ [:lv]Sveiki[:ru]Привет[:en]Hello[:]                     │
└─────────────────────────────┬────────────────────────────┘
                              │ exact raw string
                              ▼
                    Storage / Value Adapter
                              │
                              ▼
                    Multilingual Parser
                              │
                              ▼
                  immutable MultilingualValue
                              │
                     Language Resolver
                              │ fallback plan
                              ▼
                    Translation Service
                              │ RAW or TRANSLATED result
             ┌────────────────┼─────────────────┐
             ▼                ▼                 ▼
       WordPress          REST adapter      Editor adapters
       frontend           view/edit         Admin/Gutenberg/ACF
             │                │                 │
             ├──────── WooCommerce / modules ──┤
             └──────── Third-party integrations┘
                              │
                              ▼
                    OUTPUT SECURITY BOUNDARY
          context-specific HTML/attr/URL/JSON/JS escaping
```

Sanitization policy окружает write adapters; она не находится внутри parser/selection pipeline.

## 19. Architecture Decision Records

### ADR-001 — Keep inline storage

- **Context:** миллионы возможных legacy values и ecosystem expectations.
- **Decision:** bracket inline format остаётся default persistent representation; все legacy syntaxes читаются.
- **Consequences:** нет migration outage и older-reader compatibility; whole-field concurrency и search limitations остаются и решаются adapters/index policy.

### ADR-002 — No mandatory migration

- **Context:** автоматическая массовая rewrite несёт data-loss/rollback risk.
- **Decision:** upgrades не переписывают content; normalization только explicit/dry-run/backup.
- **Consequences:** core должен поддерживать heterogeneous legacy data; deployment остаётся обратимым.

### ADR-003 — Central parser API

- **Context:** PHP/JS и consumers дублируют grammar.
- **Decision:** один server parser/value contract и shared corpus; legacy functions — wrappers.
- **Consequences:** единая semantics/test surface; initial characterization cost высокий.

### ADR-004 — Explicit RAW/TRANSLATED modes

- **Context:** admin/REST/frontend требуют разные representations, а implicit context вызывает утечки/повреждение.
- **Decision:** mode является обязательной частью `TranslationContext` на новых APIs.
- **Consequences:** меньше magic; adapters становятся чуть более verbose.

### ADR-005 — Runtime integration detection

- **Context:** ACF и другие libraries могут быть Pro, renamed или bundled in theme.
- **Decision:** official hooks/API/class/function capability detection; basename только дополнительный signal.
- **Consequences:** выше compatibility; нужны versioned adapter tests.

### ADR-006 — Trusted module registry

- **Context:** option-derived include создал QTX-SEC-005.
- **Decision:** trusted PHP registers ID→canonical service/loader; options хранят только state; unknown IDs ignored.
- **Consequences:** option poisoning не даёт LFI; неофициальные path-by-option extensions запрещены.

### ADR-007 — Parser and sanitization separation

- **Context:** content может содержать legitimate HTML, а parser не знает output context.
- **Decision:** parser только структурирует; sanitation/escaping выполняют field/output policies.
- **Consequences:** HTML не повреждается; каждый adapter обязан объявить trust/output contract.

### ADR-008 — Incremental modernization

- **Context:** глобальные hooks и third-party integrations делают rewrite слишком рискованным.
- **Decision:** strangler pattern: new core behind old facade, затем adapters по одному.
- **Consequences:** временно существуют legacy/new paths и feature flags; зато каждый релиз обратим.

## 20. Final recommendation

Кодовая база реалистично эволюционирует инкрементально. Storage и значительная часть WordPress integration contracts не требуют замены.

### Можно обернуть

- `qtranxf_isMultilingual`, `split`, `use`, `join` и `QTX_Translator`;
- frontend title/content/excerpt hooks;
- существующие option names, language configuration и public hooks;
- built-in module metadata;
- Classic Editor behavior на переходный период.

### Следует рефакторить

- `$q_config` в typed configuration/language context facade;
- global filters в thin adapters;
- options/meta translation в explicit registry;
- ACF в schema-aware recursive adapter;
- module loaders в service providers поверх trusted registry;
- i18n-config в validated non-executable declarations.

### Следует со временем заменить

- duplicated PHP/JS parser paths без shared specification;
- глобальный REST POST/PUT interceptor Gutenberg;
- DOM-dependent editor mutation как primary integration model;
- broad metadata/options/gettext interception там, где возможна explicit registration;
- executable/deprecated JavaScript config и implicit context heuristics.

Рекомендуемый первый implementation phase — **Phase A: characterization corpus + immutable MultilingualValue/central parser behind unchanged legacy functions**. Это создаёт тестируемое ядро с минимальным blast radius, не меняет БД и даёт основу всем последующим adapters.

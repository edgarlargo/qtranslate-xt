# Security Batch 2 — QTX-SEC-005

Дата: 2026-08-21
Область изменений: только dynamic module include / module loader path traversal (`QTX-SEC-005`). Другие findings не исправлялись.

## 1. Root cause

`QTX_Module_Loader::load_active_modules()` обходил keys option `qtranslate_modules_state` и напрямую добавлял каждый active key к filesystem path:

`qtranslate_modules_state`
→ attacker/corrupted `$module_id`
→ `QTRANSLATE_DIR . '/src/modules/' . $module_id . '/loader.php'`
→ `require_once`.

Option должен был хранить только state, но фактически также управлял path segment. Runtime не проверял membership в registry, не применял `realpath()` и не ограничивал итоговый path каталогом modules.

## 2. Original attack surface

При отдельной возможности записать произвольный key в `qtranslate_modules_state` исходный loader принимал `../` и на Windows `..\`. Абсолютные paths и stream wrappers не становились самостоятельным scheme/root из-за префикса, а suffix `/loader.php` ограничивал target, однако traversal до подходящего файла оставался возможен.

Штатного unauthenticated/low-privileged writer option в репозитории не найдено, поэтому исходный finding был условно эксплуатируемым, а не доказанной RCE. Тем не менее option poisoning не должен превращаться в filesystem include.

## 3. Final architecture

Новый flow:

`QTX_Admin_Module::get_modules()` — authoritative built-in registry
→ зарегистрированный ID
→ внутренне построенный `<QTRANSLATE_DIR>/src/modules/<registered-id>/loader.php`
→ `realpath()` + `is_file()` + canonical directory boundary
→ map `known ID => canonical loader`
→ `qtranslate_modules_state` может только выбрать active state известного ID
→ `require_once` canonical allowlisted loader.

Loader больше не обходит option keys при построении paths. Он обходит registry и только проверяет, имеет ли соответствующий известный ID строгое integer state `QTX_MODULE_STATE_ACTIVE`.

Не-array/corrupted option возвращает пустой набор loaders. Unknown/malicious keys игнорируются без include и без fatal.

## 4. Files changed

- `src/modules/module_loader.php` — authoritative registry consumption, canonical path validation, state-only selection.
- `tests/security/test-module-loader.php` — standalone regression suite для registry, states и malicious IDs.
- `SECURITY-VALIDATION.md` — текущий статус `QTX-SEC-005` помечен resolved; историческая цепочка и severity сохранены.
- `SECURITY-BATCH-2.md` — настоящий отчёт.

`SECURITY-AUDIT.md` не изменялся: он сохраняет исторические факты исходного аудита.

## 5. Module registry used

Повторно используется существующий registry `QTX_Admin_Module::get_builtin_setup()` через публичный `QTX_Admin_Module::get_modules()`. Второй независимый список production IDs не создавался.

Зарегистрированы и проверены девять built-in modules:

| Module ID | Loader |
|---|---|
| `acf` | `src/modules/acf/loader.php` |
| `all-in-one-seo-pack` | `src/modules/all-in-one-seo-pack/loader.php` |
| `events-made-easy` | `src/modules/events-made-easy/loader.php` |
| `google-site-kit` | `src/modules/google-site-kit/loader.php` |
| `gravity-forms` | `src/modules/gravity-forms/loader.php` |
| `jetpack` | `src/modules/jetpack/loader.php` |
| `slugs` | `src/modules/slugs/loader.php` |
| `woo-commerce` | `src/modules/woo-commerce/loader.php` |
| `wp-seo` | `src/modules/wp-seo/loader.php` |

У каждого loader существует. Сохранённые option keys и IDs не переименованы.

### Third-party extension investigation

Репозиторий не содержит filter, action или public registration method для добавления third-party module definitions/loaders. `src/modules/README.md` называет external custom modules только возможным будущим расширением. Текущая архитектура и admin manager всегда строят state из `QTX_Admin_Module::get_modules()`, который возвращает только hard-coded built-ins.

Следовательно, поддерживаемой third-party module compatibility нет и Batch 2 её не ломает. PHP-код, который неофициально подменял `qtranslate_modules_state` unknown key, никогда не был зарегистрированным extension и теперь намеренно игнорируется. Если extension API появится в будущем, trusted PHP registration должно явно добавлять `ID => canonical loader`; option не должен регистрировать paths.

## 6. Path validation used

1. Canonical module root получается через `realpath(QTRANSLATE_DIR . '/src/modules')`.
2. Candidate строится только из ID существующего registry, не из option key.
3. Loader канонизируется через `realpath()` и должен пройти `is_file()`.
4. Canonical loader должен начинаться с canonical module-root prefix с завершающим `DIRECTORY_SEPARATOR`.
5. Prefix comparison case-insensitive на Windows и case-sensitive на POSIX.
6. Registry entry с отсутствующим loader, broken link или symlink за пределы module root безопасно пропускается.

Allowlist membership является основной authorization boundary. `realpath()` — дополнительная filesystem boundary, а не замена registry.

Payloads `../`, `../../plugin`, `..\plugin`, `....\plugin`, `/path`, `C:\path`, `php://`, `phar://`, `unknown-module`, `acf-malicious`, `acf/../jetpack` не могут участвовать в path construction.

## 7. Compatibility impact

- **Existing `qtranslate_modules_state`:** штатные built-in keys и integer states сохраняют поведение. Unknown keys игнорируются; option не мигрируется и не переписывается loader-ом.
- **Activation qTranslate-XT:** `QTX_Admin_Module_Manager::update_modules_state()` по-прежнему пересобирает states всех built-ins из того же registry.
- **Settings reset:** прежнее удаление option сохраняется; пустой/отсутствующий option безопасно не загружает modules.
- **Plugin activation/deactivation:** существующие hooks продолжают пересчитывать built-in states и вызывать loader; canonical registry не меняет plugin detection.
- **Built-in detection:** plugin dependencies/incompatible plugins и admin enable flags не изменены.
- **`is_module_active()`:** теперь возвращает true только для зарегистрированного module с существующим canonical loader и active state. Все существующие callers используют built-in IDs (`slugs`, `woo-commerce`) и сохраняют поведение.
- **Database/schema:** option name `qtranslate_modules_state`, state constants и структура массива не изменены.
- **Parser/REST/ACF/Gutenberg/language detection/UI:** не изменялись. Формат `[:lv]Latviešu[:ru]Русский[:en]English[:]` не затронут.

## 8. Tests added

Добавлен `tests/security/test-module-loader.php`, не требующий WordPress bootstrap. Он stubs только `get_option()` и проверяет:

- точный набор девяти registered loaders;
- canonical path и boundary каждого loader;
- active registered module;
- inactive registered module;
- unknown module;
- `../` и `../../plugin`;
- `..\` и `....\plugin`;
- absolute POSIX-like path;
- Windows-like path;
- `php://` и `phar://`;
- имя, похожее на valid module;
- смешанный valid/malicious state;
- corrupted scalar/null option;
- empty option;
- вызов `load_active_modules()` с каждым malicious-only state без include/fatal.

Тест не создаёт и не исполняет malicious PHP files. Он доказывает, что loader selection для unknown IDs пуст и include loop не получает target.

## 9. Tests executed

| Проверка | Результат |
|---|---|
| `git diff --check` | **PASS** |
| Статическая сверка registry IDs с существующими loader-файлами | **PASS**, все 9 совпали |
| `php -l src/modules/module_loader.php` | **NOT RUN** — PHP CLI отсутствует |
| `php -l tests/security/test-module-loader.php` | **NOT RUN** — PHP CLI отсутствует |
| `php tests/security/test-module-loader.php` | **NOT RUN** — PHP CLI отсутствует |
| `npm test` | **FAIL (pre-existing infrastructure)** — script намеренно выводит `Error: no test specified` и exit 1 |
| Existing relevant module/PHP tests | **NOT AVAILABLE** — PHPUnit/WordPress test configuration отсутствует |

PHP commands были фактически вызваны, но shell сообщил, что `php` не распознан. Перед merge/release новый test и lint двух PHP-файлов должны быть запущены в PHP 7.4+ окружении.

## 10. Remaining risks

- Отсутствие PHP runtime в текущем workspace не позволило динамически выполнить regression suite.
- Установка, вручную модифицировавшая built-in module directory или заменившая loader symlink-ом наружу, теперь безопасно не загрузит такой module; это намеренная boundary enforcement.
- Registry по-прежнему предполагает доверие к production PHP-файлу `admin_module.php`. Изменивший этот файл атакующий уже обладает filesystem/code-write authority и находится вне threat model option poisoning.
- Будущий third-party extension API должен регистрировать canonical paths из trusted PHP до загрузки modules и повторно применять boundary/allowlist policy. Нельзя возвращать управление paths option-у.
- Диагностический admin notice для отброшенных unknown keys не добавлялся: безопасное игнорирование не раскрывает payload и не создаёт option-write side effects. Это соответствует требованию «option содержит state» и минимизирует compatibility impact.

## Resolution

`QTX-SEC-005` считается **resolved**: даже полностью подменённый `qtranslate_modules_state` больше не может направить `require_once` за пределы canonical loaders зарегистрированных built-in modules.

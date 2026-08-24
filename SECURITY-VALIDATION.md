# Валидация эксплуатируемости qTranslate-XT

Дата: 2026-08-21
Версия: 3.16.1
Ветка: `modernisation`
Метод: повторная трассировка первичных источников, преобразований, проверок и опасных sinks для `QTX-SEC-001`, `QTX-SEC-003`, `QTX-SEC-005`, `QTX-SEC-006` и `QTX-SEC-007`. Учтены выводы `ARCHITECTURE-AUDIT.md`, `SECURITY-AUDIT.md` и текущее состояние кода после Security Batch 1. В рамках этой валидации производственный код и данные не изменялись.

## Итог валидации

- **QTX-SEC-001:** исходная версия — **CONFIRMED EXPLOITABLE / HIGH**; текущий код после Security Batch 1 — **NOT EXPLOITABLE / INFORMATIONAL (RESOLVED)**. Write-actions теперь требуют POST, `manage_options`, nonce и валидированный параметр.
- **QTX-SEC-005:** исходная версия — **CONDITIONALLY EXPLOITABLE / MEDIUM**; текущий код после Security Batch 2 — **NOT EXPLOITABLE / INFORMATIONAL (RESOLVED)**. Option выбирает только state зарегистрированных IDs и больше не участвует в построении path.
- **QTX-SEC-006:** **HARDENING ONLY**, severity снижена с **HIGH** до **LOW**. Небезопасная десериализация есть, однако не доказаны одновременно контролируемый источник и правдоподобная gadget chain; часть поведения дублирует стандартную семантику WordPress Options/Metadata API.
- **QTX-SEC-003:** **HARDENING ONLY**, severity снижена с **MEDIUM** до **LOW**. Произвольный путь к локальному файлу технически принимается, но штатный инициатор должен иметь `manage_options` и nonce, а браузеру возвращается только распознанная конфигурационная часть валидного JSON, не сырой файл.
- **QTX-SEC-007:** **CONDITIONALLY EXPLOITABLE**, severity снижена с **MEDIUM** до **LOW**. Host сохраняется в target в query/path modes, но обычный внешний web-атакующий не может заставить браузер обратиться к victim origin с другим `Host`; нужна permissive/misconfigured proxy или виртуальный хост.

## QTX-SEC-001 — Admin CSRF

**Вердикт для текущего кода:** **NOT EXPLOITABLE (RESOLVED)**
**Исходная / валидированная текущая severity:** HIGH / **INFORMATIONAL**
**CWE:** CWE-352

### Текущая цепочка после Security Batch 1

`cross-site GET с action parameter`
→ `/wp-admin/options-general.php?page=qtranslate-xt&delete=lv`
→ `qtranxf_admin_init()` допускает только пользователя с `manage_options`
→ `qtranxf_edit_config()` повторно проверяет `current_user_can('manage_options')`
→ dispatcher ищет изменяющие actions только в `$_POST`
→ GET action игнорируется
→ dangerous sink **не достигается**.

Штатный write path теперь выглядит так:

`аутентифицированный POST из nonced admin form`
→ явная проверка `manage_options`
→ `qtranxf_verify_nonce('qtranslate-x_configuration_form')` / `check_admin_referer()`
→ допускается ровно один action
→ scalar value проходит `wp_unslash()`, `sanitize_text_field()` и validation language code/action value
→ прежний SQL либо `qtranxf_save_config()`.

- **Текущие affected files/functions:** `src/admin/admin_options_update.php::qtranxf_edit_config()`; POST controls в `src/admin/admin_settings.php`, `src/admin/admin_settings_language_list.php`, `src/admin/import_export.php`; внешний routing в `src/admin/admin.php::qtranxf_admin_init()`.
- **Attack entry point:** admin settings page. Внешний GET больше не является write entry point.
- **Authentication/capability:** действующая WordPress session и `manage_options`.
- **Nonce:** обязателен для POST и проверяется server-side.
- **Реалистичность внешнего контроля:** внешний сайт может инициировать GET, но не знает nonce и не может выполнить accepted POST при обычной same-origin/cookie модели.
- **Текущий impact:** отсутствует для исходного CSRF vector.
- **Recommended fix:** уже реализован; сохранить regression tests на GET rejection, nonce, capability и single-action dispatch.
- **Backward compatibility:** старые bookmarks/сторонние callers изменяющих GET URL намеренно перестали работать; DB/options/inline multilingual format не изменены.

### Исходная цепочка данных до Security Batch 1

`внешняя top-level GET-навигация`
→ `/wp-admin/options-general.php?page=qtranslate-xt&<action>=<value>`
→ WordPress аутентифицирует admin cookie
→ `qtranxf_admin_init()` проверяет `current_user_can('manage_options')`
→ подключает `admin_options_update.php` и вызывает `qtranxf_edit_config()`
→ `qtranxf_verify_nonce()` возвращает `true`, потому что `$_POST` пуст
→ ветка по `$_GET['convert'|'markdefault'|'delete'|'enable'|'disable'|'moveup'|'movedown']`
→ прямые SQL updates либо изменение `$q_config` и `qtranxf_save_config()`.

### Исходные endpoint, метод и авторизация

- **Endpoint:** WordPress admin page `/wp-admin/options-general.php?page=qtranslate-xt`.
- **Метод:** GET для всех перечисленных изменяющих действий. Формального `admin_post_*` action нет.
- **Аутентификация:** обязательна действующая WordPress admin-сессия.
- **Capability:** `manage_options`, проверяется в `src/admin/admin.php:139-149`. Это обычно Administrator на single-site, но capability, а не имя роли, является фактической границей.
- **Nonce:** отсутствует в GET. `src/admin/admin_utils.php:410-411` реализует `empty($_POST) || check_admin_referer(...)`; пустой POST делает любую GET-навигацию прошедшей проверку.

### Фактические изменения, которые выполнял исходный код

- `convert` (`src/admin/admin_options_update.php:144-160`) выполняет два `UPDATE wp_posts` для каждого включённого языка и заменяет legacy markers в `post_title` и `post_content`. Обновляться могут многие строки.
- `markdefault` (`:161-200`) выбирает все опубликованные posts/pages без markers, затем отдельно обновляет `post_content`, `post_title`, `post_excerpt` каждой подходящей записи. Это подтверждённое массовое изменение.
- `delete`, `enable`, `disable`, `moveup`, `movedown` (`:224-290`) меняют языковую конфигурацию; при отсутствии ошибок `:296-299` вызывает `qtranxf_save_config()`, записывающий options.
- `edit` является read-only и в уязвимость изменения состояния не входит.

`convert` и `markdefault` не проходят через `$everything_fine`, но это не защищает данные: SQL выполняется до этой проверки. Действия можно повторять, потому что nonce/одноразовый token отсутствует. После первой конверсии часть повторов станет логическим no-op, но запрос и массовое сканирование всё равно повторяются; language actions также повторяемы, пока состояние допускает изменение.

### Возможность cross-site trigger и SameSite в исходном коде

Внешняя страница может инициировать top-level GET через ссылку, форму или `window.location`. Современный browser с cookie policy `SameSite=Lax` обычно **отправляет cookie при top-level safe navigation GET**, поэтому Lax не устраняет этот CSRF. Он обычно блокирует cookie у cross-site subresource (`img`) и у cross-site POST, поэтому пример с `<img>` из исходного аудита не универсален. WordPress/server/browser versions могут явно задавать иную cookie policy; `SameSite=Strict` снижает эксплуатируемость, но не является проверкой плагина и не может считаться исправлением.

### Исторический недеструктивный PoC

Следующий HTML демонстрировал исходный vector без изменения production data. На текущем коде он больше не достигает ветки `delete` и служит regression case. PoC не выполнялся на production.

```html
<form method="get"
      action="https://TARGET.EXAMPLE/wp-admin/options-general.php"
      target="_blank">
  <input type="hidden" name="page" value="qtranslate-xt">
  <input type="hidden" name="delete" value="qtx-validation-invalid-language">
  <button type="submit">Open validation request</button>
</form>
```

Для доказательства воздействия **не нужно** заменять invalid value реальным языком: кодовая трасса уже показывает, что валидный enabled language достигнет `qtranxf_deleteLanguage()` и последующего сохранения. Использование реального значения было бы разрушительным и намеренно не выполнялось.

### Вывод по QTX-SEC-001

Исходная CSRF была самостоятельно эксплуатируемой и могла массово менять БД. В текущем коде complete attacker-controlled chain разорвана на method/nonce boundary; finding считается исправленным и классифицируется как **NOT EXPLOITABLE**.

## QTX-SEC-005 — Dynamic module include

**Вердикт для текущего кода:** **NOT EXPLOITABLE (RESOLVED)**
**Исходная / валидированная текущая severity:** HIGH / **INFORMATIONAL**
**CWE:** CWE-22, CWE-98

### Текущая цепочка после Security Batch 2

`qtranslate_modules_state`
→ corrupted/unknown keys остаются только данными option
→ loader обходит authoritative `QTX_Admin_Module::get_modules()`
→ только зарегистрированный ID сопоставляется с внутренним `src/modules/<id>/loader.php`
→ `realpath()` + `is_file()` + canonical prefix boundary `src/modules/`
→ state option может только включить canonical loader известного ID
→ `require_once` получает canonical allowlisted path.

Unknown IDs, traversal, absolute paths, Windows paths и stream wrappers никогда не участвуют в path construction и не достигают include sink. Не-array option безопасно трактуется как пустое состояние.

- **Affected file/function:** `src/modules/module_loader.php::QTX_Module_Loader::load_active_modules()`; writers в `src/modules/admin_module_manager.php::update_modules_state()`, activation/reset paths.
- **Attack entry point:** чтение option при bootstrap; прямого HTTP entry point для произвольного module id нет.
- **Authentication/capability:** sink исполняется без session; штатная генерация option происходит в admin/plugin lifecycle. Доступный unauthenticated/low-privileged writer не найден.
- **Nonce:** неприменим к bootstrap sink; settings writer наследует nonced `manage_options` workflow, plugin activation/deactivation защищается WordPress core.
- **External attacker control:** нереалистичен без отдельного option-write primitive и подходящего `loader.php`.
- **Impact:** условная LFI/RCE; полный захват сайта только при выполнении обеих дополнительных предпосылок.
- **Recommended fix:** статический registry `module id => canonical loader`, пересечение option keys с allowlist и optional `realpath()` boundary.
- **Backward compatibility:** низкий для встроенных модулей; средний для недокументированных сторонних module ids.

### Исходная цепочка данных до Security Batch 2

`wp_options.option_name = qtranslate_modules_state`
→ `get_option(QTX_OPTIONS_MODULES_STATE, [])`
→ array key становится `$module_id`, value сравнивается строго с integer `QTX_MODULE_STATE_ACTIVE`
→ `QTRANSLATE_DIR . '/src/modules/' . $module_id . '/loader.php'`
→ `require_once` в `QTX_Module_Loader::load_active_modules()`.

Sink находится в `src/modules/module_loader.php:33-39`; имя option определено как `qtranslate_modules_state` в `src/options.php:89`.

### Кто и как записывает option

В репозитории найден один writer значений:

- `QTX_Admin_Module_Manager::update_modules_state()` в `src/modules/admin_module_manager.php:32-55` создаёт новый массив **только** обходом `QTX_Admin_Module::get_modules()`; keys равны встроенным `$module->id`. Произвольный request key туда не копируется.

Этот writer вызывается:

- при activation qTranslate-XT (`src/admin/activation_hook.php:800-803`);
- после reset/save settings (`src/admin/admin_options_update.php:374`, `:921`);
- после WordPress hooks `activated_plugin`/`deactivated_plugin` (`src/modules/admin_module_manager.php:13-17`, `:113-131`). Core controllers для plugin activation/deactivation требуют соответствующие capabilities и nonces; hook получает уже выбранный plugin filename, но он влияет только на проверку состояния зарегистрированных модулей, не на их ids.

`src/admin/admin_options_update.php:353` умеет только удалить весь option при reset. Migration `qtranxf_rename_legacy_option('qtranslate_modules', QTX_OPTIONS_MODULES_STATE)` в `src/admin/activation_hook.php:750` переносит legacy option при activation; это дополнительный источник уже сохранённых DB-данных, но request writer произвольных keys в репозитории не найден.

- **Admin/REST/AJAX:** штатный settings POST требует config page, `manage_options` и nonce; он управляет flags известных модулей и затем полностью пересобирает option из registry. Собственных REST/AJAX paths, записывающих `qtranslate_modules_state`, нет.
- **Plugin/theme code:** любой выполняющийся PHP-код может вызвать `update_option()` либо изменить DB/filter/register definitions. Это технически влияет на source, но такой код уже имеет возможность исполнения PHP и не создаёт новую privilege boundary.
- **Прямой DB/SQL compromise:** может подменить option, но такой primitive данным репозиторием не предоставлен.

### Проверка path semantics

| Ввод в `$module_id` | Результат |
|---|---|
| `../` | Синтаксически принимается; файловая система нормализует traversal. |
| `..\` | Работает как traversal на Windows; на POSIX backslash обычно является обычным символом. |
| абсолютный POSIX/Windows path | Не становится абсолютным target, потому что перед ним уже стоит `QTRANSLATE_DIR/src/modules/`; drive/root находится в середине строки. |
| stream wrapper (`php://`, `phar://` и т. п.) | Не активируется как wrapper: scheme не находится в начале окончательной строки. |
| null byte | Современный PHP отвергает path с null byte; useful bypass не подтверждён. |

- Расширение ограничено сильнее, чем просто `.php`: всегда добавляется точный суффикс **`/loader.php`**.
- `realpath()` не используется.
- Проверки, что итоговый path остаётся внутри `src/modules`, нет.
- Runtime allowlist отсутствует; доверие option прямо задокументировано в `module_loader.php:27-29`.

Следовательно, можно включить не произвольный существующий PHP-файл, а только файл, достижимый в форме `<контролируемый/найденный каталог>/loader.php`. Например, traversal до каталога другого plugin с таким filename технически возможен. Для RCE атакующему всё равно нужно разместить собственный `loader.php` в достижимом каталоге либо найти существующий loader с полезным побочным поведением. Ни file-write primitive, ни конкретный пригодный существующий target репозиторием не доказаны.

### Исторический вывод до исправления

LFI sink и traversal были подтверждены, но attacker-controlled source отсутствовал. Цепочка становилась эксплуатируемой только после отдельной подмены option и наличия подходящего `/loader.php`; самостоятельная LFI/RCE не была доказана. В текущем коде даже подменённый option не может выбрать неизвестный filesystem path, поэтому finding закрыт как **NOT EXPLOITABLE**.

## QTX-SEC-006 — Object injection

**Вердикт:** **HARDENING ONLY**
**Текущая / валидированная severity:** HIGH / **LOW**
**CWE:** CWE-502

- **Affected files/functions:** `src/frontend.php::qtranxf_translate_deep()`, `qtranxf_translate_metadata()`; migration helpers в `src/admin/admin_utils_db.php`, `src/admin/import_export.php`; ACF formatters.
- **Attack entry point:** чтение сохранённых options/meta либо привилегированная migration; прямого request-to-unserialize path не найдено.
- **Authentication/capability:** зависит от owner конкретного option/meta; admin migrations требуют `manage_options`. Доказанного low-privileged raw serialized-object writer нет.
- **Nonce:** неприменим к frontend reads; admin migration идёт через nonced settings workflow.
- **External attacker control:** не доказан; отсутствуют одновременно raw object source и gadget chain.
- **Impact:** object hydration/DoS теоретически; RCE не подтверждена.
- **Recommended fix:** десериализация с `allowed_classes => false`, reject objects, compatibility tests для scalar/array/nested serialized values.
- **Backward compatibility:** средний/высокий, если сторонние options/meta легитимно содержат objects.

### Полная цепочка данных

Основные runtime-цепочки:

1. `значение option после get_option/filter` → `qtranxf_translate_option()` → `qtranxf_translate_deep()` → проверка `qtranxf_isMultilingual()` и `is_serialized()` → `unserialize()` в `src/frontend.php:358-380`.
2. `raw metadata cache` → `qtranxf_translate_metadata()` → для meta key с `_url` `is_serialized()` → `unserialize()` (`src/frontend.php:714-729`); для прочих keys дополнительно требуется multilingual marker (`:733-747`) → возврат через metadata filter/cache.
3. `raw metadata cache` → fallback return path → `maybe_unserialize()` (`src/frontend.php:762-777`).
4. Admin migration читает все rows options/postmeta → пропускает только строки с multilingual markers → `maybe_unserialize()` → conversion → DB update (`src/admin/admin_utils_db.php:182-224`, `:263-300`).
5. Legacy option migration использует `maybe_unserialize()` в `src/admin/import_export.php:22-54`; ACF formatters — в `src/modules/acf/extended.php:52-58` и `src/modules/acf/fields/post_object.php:74-78`.

Ни один вызов не передаёт `allowed_classes => false`. `maybe_unserialize()` WordPress также не ограничивает classes.

### Источник и возможность управления

- **Options:** штатная запись произвольных options обычно требует `manage_options` либо уже выполняющегося plugin/theme code. Кроме того, стандартный `get_option()` выполняет `maybe_unserialize()` сохранённого option **до** динамического `option_{$option}` filter; поэтому qTranslate в обычном option path получает уже unserialized value. Его дополнительный прямой `unserialize()` важен преимущественно для вложенной serialized string или прямого вызова helper, а не для верхнего уровня option.
- **Metadata:** plugin short-circuit metadata filter читает raw cache и сам воспроизводит стандартное `maybe_unserialize()` поведение WordPress. Авторы/интеграции могут записывать отдельные разрешённые post meta, но WordPress `update_metadata()` применяет `maybe_serialize()`; уже serialized string обычно сохраняется с дополнительным уровнем serialization. Не показана конкретная permission/configuration, при которой low-privilege attacker может поместить нужный object payload именно в raw форму, которую данный sink гидратирует.
- **Admin migration:** запускается только через привилегированный settings workflow; атакующий источник не появляется.
- **ACF:** trust boundary и capability зависят от установленной ACF field configuration. В этом репозитории нет доказанного public writer crafted serialized objects.

### Gadget validation

Поиск всего репозитория по `__wakeup`, `__destruct`, `__toString`, `__unserialize` не нашёл ни одного определения magic method. Следовательно, собственной gadget chain qTranslate-XT не содержит. В реальной WordPress-инсталляции gadgets могут появиться из core/темы/других plugins, но без зафиксированного состава установки и конкретной цепочки это нельзя считать доказательством RCE.

### Дополнительная поверхность qTranslate

qTranslate действительно расширяет число мест, где raw cached metadata может десериализоваться, особенно все serialized values в keys, содержащих `_url`. Однако стандартный Metadata API при обычном чтении того же значения также вызывает `maybe_unserialize()`. Существенная дополнительная поверхность — раннее short-circuit чтение и рекурсивная обработка вложенных multilingual serialized strings — не превращается в эксплойт без контролируемой raw записи и gadget chain.

### Вывод

Факт unrestricted object hydration подтверждён, но эксплуатация не подтверждена: нет одновременно доказанного attacker-controlled source и правдоподобного gadget path. RCE заявлять нельзя. Finding следует оставить как LOW hardening: запрет classes уменьшит риск цепочек с другими plugins, но это не валидированная самостоятельная уязвимость qTranslate-XT.

## QTX-SEC-003 — Local JSON read

**Вердикт:** **HARDENING ONLY**
**Текущая / валидированная severity:** MEDIUM / **LOW**
**CWE:** CWE-22, CWE-73 (defence-in-depth)

- **Affected files/functions:** `src/admin/activation_hook.php::qtranxf_load_config_files()`, `qtranxf_update_config_options()`; input в `src/admin/admin_options_update.php`; inspector в `src/admin/admin_settings.php`.
- **Attack entry point:** settings POST `json_config_files` либо уже сохранённый `qtranslate_config_files`.
- **Authentication/capability:** `manage_options`; штатно Administrator/custom privileged role.
- **Nonce:** settings POST защищён `qtranslate-x_configuration_form` nonce.
- **External attacker control:** unauthenticated/low-privileged writer и CSRF path не найдены.
- **Impact:** чтение и отображение только распознанной части структурированного local JSON; raw arbitrary file disclosure/LFI не подтверждены.
- **Recommended fix:** approved roots, `.json`, size/schema limits, `realpath()` + prefix boundary, symlink/wrapper rejection.
- **Backward compatibility:** высокий для установок с custom absolute/out-of-tree config paths; потребуется allowlist/filter/migration warning.

### Полная цепочка данных

`POST json_config_files` на settings page
→ `sanitize_text_field(stripslashes(...))`
→ split по whitespace/comma в `src/admin/admin_options_update.php:836-847`
→ `qtranxf_load_config_files()`
→ разрешение path через `file_exists()`/конкатенацию
→ `file_get_contents()`
→ `json_decode(..., true)`
→ merge только распознанных `admin-config`/`front-config` данных
→ options `qtranslate_admin_config`/`qtranslate_front_config`
→ escaped JSON в Configuration Inspector (`src/admin/admin_settings.php:235-262`).

Сохранённый источник — option `qtranslate_config_files` (`src/admin/activation_hook.php:189-197`, writer `:408-416`).

### Авторизация и path behavior

- Settings page исполняет update только внутри `qtranxf_admin_init()` с `manage_options` (`src/admin/admin.php:139-149`).
- Изменяющий settings request является POST и проходит `check_admin_referer('qtranslate-x_configuration_form')` через `qtranxf_verify_nonce()`; fail-open для пустого POST из QTX-SEC-001 здесь не помогает передать `json_config_files`.
- Абсолютный читаемый path принимается напрямую: первый `file_exists($config_file)` в `src/admin/activation_hook.php:96-99` оставляет его без изменений.
- `./...` добавляется к `QTRANSLATE_DIR`; прочий несуществующий relative path — к `WP_CONTENT_DIR` (`:100-114`). `../` не удаляется, `realpath()`/root boundary нет. Поэтому traversal работает при разрешении файловой системой.
- Extension не проверяется: `file_get_contents()` попытается прочитать PHP, text и любой readable regular file. Но дальнейший sink требует непустой JSON array. Обычный PHP-файл не является JSON и не возвращается браузеру как raw content.
- Stream wrapper обычно не проходит предварительный `file_exists()` и для него нет специальной поддержки; эксплуатируемый wrapper path не подтверждён.

### Что реально раскрывается

Браузер не получает raw bytes файла. `json_decode()` должен вернуть array, затем loader сливает конфигурацию. Inspector показывает только итоговые `admin-config` и `front-config`, причём через `esc_textarea()`. Значит, полезное раскрытие возможно для читаемого JSON, который уже имеет ожидаемую структуру; произвольный JSON secret с другими top-level keys не выводится. Ошибки сообщают path/parse failure, но не содержимое.

Другие writers списка — activation/theme/plugin discovery и `qtranxf_adjust_config_files()` (`src/admin/activation_hook.php:428-698`) — получают paths из установленной темы/plugin lifecycle. Выполняющийся theme/plugin code также может вызвать Options API, но уже обладает PHP authority. REST/AJAX writer option в репозитории не найден.

### Пересечение границы привилегий

Штатный инициатор уже имеет `manage_options` и валидный nonce. На типичной single-site установке Administrator часто может устанавливать plugins и тем самым читать файлы через PHP-код. В hardened установке с `DISALLOW_FILE_MODS`, multisite/custom roles или shared hosting `manage_options` не обязательно равно filesystem read, поэтому path restriction всё равно разумна. Однако этот репозиторий не предоставляет lesser-privilege или CSRF path к параметру и не возвращает сырой произвольный файл.

### Вывод

Path traversal/absolute JSON loading являются реальным функциональным поведением, но доказанного пересечения WordPress privilege boundary нет. Исходную MEDIUM vulnerability следует понизить до LOW hardening recommendation. Это не arbitrary PHP read в браузер и не LFI/RCE.

## QTX-SEC-007 — Redirect / Host header

**Вердикт:** **CONDITIONALLY EXPLOITABLE**
**Текущая / валидированная severity:** MEDIUM / **LOW**
**CWE:** CWE-601, CWE-346

- **Affected files/functions:** `src/init.php::qtranxf_init_language()`, `src/language_detect.php::qtranxf_check_url_maybe_redirect()`, URL builders в `src/url.php`.
- **Attack entry point:** HTTP `Host` после обработки web server/reverse proxy; косвенно plugin filter может заменить target, но это уже trusted PHP code.
- **Authentication/capability:** не требуются.
- **Nonce:** неприменим к frontend redirect.
- **External attacker control:** raw HTTP Host контролируем, но обычный browser не позволяет hostile Host для victim URL; practical exploit зависит от proxy/vhost/cache misconfiguration.
- **Impact:** условный off-domain 301, phishing/cache poisoning; стандартный victim-host open redirect не доказан.
- **Recommended fix:** canonical allowed hosts, `wp_safe_redirect()`, корректная proxy trust policy и tests для всех URL modes.
- **Backward compatibility:** средний для reverse proxy, multisite и per-domain installations.

### Полная цепочка данных

`web server / reverse proxy формирует $_SERVER['HTTP_HOST']`
→ raw host копируется в `$q_config['url_info']['host']` в `src/init.php:63-69`
→ `qtranxf_complete_url_info()` определяет только path-base и не проверяет host (`src/url.php:339-371`)
→ при redirect `$url_orig` повторно строится из raw `HTTP_HOST` и `REQUEST_URI` (`src/language_detect.php:459-466`)
→ `qtranxf_convertURL('', $lang)` копирует request `url_info` (`src/url.php:179-194`)
→ `qtranxf_url_set_language()` меняет path/query/domain в зависимости от mode
→ `qtranxf_buildURL()` строит абсолютный target
→ filter `qtranslate_language_detect_redirect`
→ `wp_redirect($target, 301)` (`src/language_detect.php:483-487`).

### Фактическое влияние Host

- В `QTX_URL_PATH` и `QTX_URL_QUERY` host из request сохраняется; target действительно может получить forged off-domain host.
- В `QTX_URL_DOMAIN` `qtranxf_url_del_language()` сначала заменяет host на configured `homeinfo['host']` (`src/url.php:89-93`), затем добавляет language subdomain.
- В `QTX_URL_DOMAINS` target обычно берётся из настроенного `$q_config['domains'][$lang]` (`:95-99`, `:124-127`). Эти modes значительно меньше зависят от request Host, хотя неверная конфигурация/filters остаются отдельным trust input.

WordPress здесь не sanitizes `$_SERVER['HTTP_HOST']` заранее. Web server обычно отклоняет синтаксически неверный Host, а `wp_redirect()` применяет санитарную обработку Location, включая CR/LF, но **не ограничивает host**. `wp_safe_redirect()` вызывает host validation/fallback и устранил бы off-domain target, если canonical/allowed hosts настроены корректно; в domain/multisite режимах потребуется заполнить `allowed_redirect_hosts` либо предварительно выбрать target из явного registry.

### Может ли внешний атакующий получить off-domain redirect

На raw HTTP уровне — да: если virtual host принимает произвольный `Host: attacker.example`, path/query mode может ответить `Location: https://attacker.example/...`. Но обычная ссылка `https://victim.example/...` заставляет browser отправить `Host: victim.example`; JavaScript не может установить запрещённый Host header. Поэтому это не обычный open redirect с attacker-controlled query parameter и не доказанный phishing link под victim hostname.

Практическая эксплуатация требует дополнительного условия:

- reverse proxy доверяет user-controlled `X-Forwarded-Host` и переносит его в `HTTP_HOST`;
- default virtual host маршрутизирует произвольные hosts к WordPress;
- shared cache некорректно не разделяет cache по Host; либо
- внутренний client позволяет вручную задать Host и доверяет redirect.

Корректная proxy canonical-host policy снимает exploit path. Обратный proxy существенно важен: qTranslate сам не читает `X-Forwarded-Host`, но upstream может переписать из него `HTTP_HOST`.

### Вывод

Off-domain Location воспроизводим при forged Host в path/query modes, однако внешняя эксплуатация через стандартный browser требует ошибочной инфраструктурной конфигурации. Finding остаётся условно эксплуатируемым и понижается до LOW. Использование `wp_safe_redirect()` плюс явный canonical host allowlist устранит plugin-side часть риска.

## SECURITY PRIORITY TABLE

| ID | Original severity | Validated severity | Exploitability | Authentication required | Capability required | Impact | Fix priority |
|---|---|---|---|---|---|---|---|
| QTX-SEC-001 | HIGH | **INFORMATIONAL (RESOLVED)** | **NOT EXPLOITABLE** в текущем коде; исходная версия была confirmed | Да | `manage_options` | Текущий CSRF impact отсутствует; исторически — массовое изменение posts/pages/options | **Closed; P0 regression coverage** |
| QTX-SEC-005 | HIGH | **INFORMATIONAL (RESOLVED)** | **NOT EXPLOITABLE** в текущем коде; исходная версия была conditional | Неприменимо к закрытому vector | Option writers не могут регистрировать paths | Текущий path-traversal/LFI impact отсутствует | **Closed; P0 regression coverage** |
| QTX-SEC-007 | MEDIUM | **LOW** | **CONDITIONALLY EXPLOITABLE** | Нет | Нет | Off-domain 301, phishing/cache poisoning только при permissive Host/proxy path | **P1/P2** |
| QTX-SEC-003 | MEDIUM | **LOW** | **HARDENING ONLY** | Да | `manage_options` + settings nonce | Чтение/отображение структурированной части локального JSON; влияние на config | **P2** |
| QTX-SEC-006 | HIGH | **LOW** | **HARDENING ONLY** | Зависит от writer; доказанного attacker writer нет | Зависит от option/meta/ACF owner | Object hydration/DoS; RCE не доказана без source и gadgets | **P2** |

## Пересмотр порядка исправлений из SECURITY-AUDIT.md

1. **QTX-SEC-001** закрыт Security Batch 1; приоритет — не допустить регрессии POST/nonce/capability boundary.
2. **QTX-SEC-005** закрыт Security Batch 2; приоритет — сохранить authoritative registry и regression cases для corrupted option/path payloads.
3. **QTX-SEC-007** следует исправлять с обязательными regression tests для reverse proxy, multisite и domain modes.
4. **QTX-SEC-003** и **QTX-SEC-006** следует держать в hardening backlog. Для `QTX-SEC-006` изменение deserialization semantics имеет заметный compatibility risk и требует тестов реальных option/meta objects.

В исходном коде из пяти findings только `QTX-SEC-001` имел доказанный самостоятельный путь эксплуатации; он исправлен. Условный filesystem sink `QTX-SEC-005` также закрыт authoritative registry и canonical boundary. Для `QTX-SEC-006` по-прежнему отсутствуют controllable source и gadget chain. Реалистичный site compromise текущим состоянием репозитория не подтверждён.

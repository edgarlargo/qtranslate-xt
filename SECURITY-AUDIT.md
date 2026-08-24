# Полный аудит безопасности qTranslate-XT

Дата: 2026-08-21
Версия: 3.16.1
Ветка: `modernisation`
Метод: статический анализ всего репозитория, трассировка источников и sinks, ручная проверка PHP/JS/JSON. Рабочее окружение не содержит PHP/WordPress runtime, поэтому динамические WordPress-тесты не выполнялись. Производственный код не изменялся.

## Executive summary

Обнаружено **4 подтверждённые уязвимости**:

- CRITICAL: **0**
- HIGH: **1**
- MEDIUM: **1**
- LOW: **2**

Всего задокументировано **18 findings** с учётом потенциальных рисков и hardening-рекомендаций:

- CRITICAL: **0**
- HIGH: **3**
- MEDIUM: **7**
- LOW: **6**
- INFORMATIONAL: **2**

Наиболее опасная подтверждённая поверхность — обработчик страницы настроек: изменяющие GET-действия проходят без nonce и позволяют через CSRF заставить администратора массово изменить языки или содержимое БД. Наиболее опасные условные цепочки — динамический `require_once` по module id из `wp_options` и небезопасная десериализация metadata/options. Прямого unauthenticated site compromise по результатам статического анализа не подтверждено.

Рекомендуемый порядок исправлений:

1. закрыть GET-CSRF и AJAX authorization/CSRF;
2. заменить динамический module include на статический allowlist;
3. запретить классы при десериализации;
4. ограничить i18n-config и DB splitter разрешёнными каталогами;
5. валидировать REST route/language/body и redirect host;
6. затем выполнить SQL/output/nonce/debug hardening.

### Статусы доказанности

- **CONFIRMED vulnerability** — достижимый дефект и воздействие прослеживаются в коде без дополнительной неизвестной уязвимости.
- **POTENTIAL risk** — опасный sink существует, но эксплуатация зависит от отдельной возможности записи, конфигурации сервера, gadget chain или поведения стороннего компонента.
- **HARDENING recommendation** — нарушение defence-in-depth без доказанного самостоятельного exploit path.

## Охват аудита

Проверены все PHP-файлы `qtranslate.php`, `src/**`, `dev/**`, JavaScript `js/**`, committed bundles `dist/**`, companion scripts `i18n-config/**`, JSON-конфигурации, Composer/npm metadata. Найдены:

- источники: `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, `$_SERVER`, REST body/params и AJAX params;
- `$_FILES` и `php://input` непосредственно плагином не используются;
- собственных `admin_post_*` и `register_rest_route()` нет;
- AJAX actions: `admin_debug_info`, `qtranslate_admin_notice`, ACF post-object authenticated/nopriv;
- remote HTTP/cURL вызовов нет;
- secrets/API keys/credentials в репозитории не найдены;
- raw SQL, filesystem operations, serialize/unserialize, redirects и output sinks проверены отдельно.

## Подтверждённые уязвимости

### QTX-SEC-001 — Изменяющие GET-действия настроек обходят nonce

- **Статус:** CONFIRMED vulnerability
- **Severity:** HIGH
- **CWE:** CWE-352 (Cross-Site Request Forgery)
- **Файлы:** `src/admin/admin_utils.php`, `src/admin/admin.php`, `src/admin/admin_options_update.php`
- **Функции:** `qtranxf_verify_nonce()`, `qtranxf_admin_init()`, `qtranxf_edit_config()`
- **Точные места:** `admin_utils.php:410-411`; `admin.php:139-150`; `admin_options_update.php:144-200`, `224-299`
- **Уязвимое поведение:** `qtranxf_verify_nonce()` возвращает `true`, когда `$_POST` пуст. На странице `options-general.php?page=qtranslate-xt` GET-параметры `convert`, `markdefault`, `delete`, `enable`, `disable`, `moveup`, `movedown` выполняют изменения. `convert` и `markdefault` запускают массовые SQL updates. Capability `manage_options` проверяется внешним `qtranxf_admin_init()`, но CSRF от этого не предотвращается.
- **Предусловия:** атакующий убеждает вошедшего администратора открыть URL/страницу, загружающую crafted URL сайта; qTranslate-XT активен.
- **Реалистичный сценарий:** `<img src="https://victim/wp-admin/options-general.php?page=qtranslate-xt&convert=1">` или ссылка с `delete=lv` выполняется в admin session. Возможны удаление/переключение языка либо массовая конверсия post content.
- **Impact:** нарушение целостности и доступности контента, SEO/URL, потенциально дорогое массовое изменение БД; восстановление может потребовать backup.
- **Рекомендация:** все изменяющие действия перевести на POST; обязательные `check_admin_referer()` и `current_user_can('manage_options')` внутри каждого handler; для GET оставить только read-only `edit`/inspector. Не считать пустой POST валидным nonce.
- **Риск совместимости:** средний — существующие GET-ссылки UI придётся заменить POST forms/nonced action links; формат данных не затрагивается.
- **Тесты:** GET без nonce не меняет options/posts; GET с фиктивным nonce не меняет; POST без/неверным nonce отклонён; POST с nonce+capability выполняется; subscriber/editor всегда получает 403; regression на каждое действие.
- **Сравнение с `ARCHITECTURE-AUDIT.md`:** архитектурный аудит отметил распределённую nonce-логику и изменяющие GET; security trace подтверждает exploitable CSRF и повышает это до HIGH.

### QTX-SEC-002 — AJAX dismiss уведомлений без nonce и capability

- **Статус:** CONFIRMED vulnerability
- **Severity:** LOW
- **CWE:** CWE-352, CWE-862
- **Файлы:** `src/admin/admin_notices.php`, `js/notices.js`
- **Функции:** `qtranxf_ajax_qtranslate_admin_notice()`, `qtranxf_update_admin_notice()`
- **Точные места:** `admin_notices.php:47-50`, `319-328`; `js/notices.js:8-20`
- **Уязвимое поведение:** authenticated `wp_ajax_qtranslate_admin_notice` принимает `notice_id`/`notice_action`, не проверяет nonce/capability и изменяет глобальный `qtranslate_admin_notices`. JS nonce не отправляет.
- **Предусловия:** любой вошедший пользователь для прямого запроса; либо посещение crafted страницы любым вошедшим пользователем для CSRF.
- **Сценарий:** subscriber отправляет `action=qtranslate_admin_notice&notice_id=config-files-changed`, скрывая важное глобальное admin notice для администраторов.
- **Impact:** изменение глобальной настройки и сокрытие security/configuration notices; прямого захвата сайта нет.
- **Рекомендация:** `check_ajax_referer`, capability `manage_options`, allowlist notice IDs/actions, `wp_send_json_success/error`.
- **Риск совместимости:** низкий; требуется локализовать nonce в script.
- **Тесты:** unauth/subscriber/editor/CSRF rejected; admin+valid nonce accepted; неизвестный id rejected; option не растёт неограниченно.
- **Сравнение:** полностью подтверждает finding архитектурного аудита.

### QTX-SEC-003 — Authenticated arbitrary local JSON file read через i18n-config

- **Статус:** CONFIRMED vulnerability
- **Severity:** MEDIUM
- **CWE:** CWE-22, CWE-73, CWE-200
- **Файлы:** `src/admin/activation_hook.php`, `src/admin/admin_options_update.php`, `src/admin/admin_settings.php`
- **Функции:** `qtranxf_load_config_files()`, `qtranxf_update_settings()`
- **Точные места:** `activation_hook.php:93-134`; `admin_options_update.php:836-858`; inspector output `admin_settings.php:248-268`
- **Уязвимое поведение:** absolute path принимается, если `file_exists($config_file)`; relative path конкатенируется с plugin/content directory без `realpath` boundary check; затем выполняется `file_get_contents`. JSON с подходящими keys сливается и доступен через inspector/debug configuration.
- **Предусловия:** пользователь с `manage_options` и действующим nonce; целевой файл читаем PHP и содержит JSON, пригодный для merge. На single-site такой пользователь часто уже очень привилегирован, но при `DISALLOW_FILE_MODS` не обязательно имеет filesystem/code access.
- **Сценарий:** администратор указывает traversal/absolute путь к JSON-конфигурации другого приложения в общей среде и просматривает merged values через inspector.
- **Impact:** раскрытие локальных JSON-данных; дополнительное влияние на filters/DOM config. Не доказано чтение произвольного не-JSON файла в ответ.
- **Рекомендация:** разрешить только `.json` под `QTRANSLATE_DIR`, активными plugin/theme roots или отдельным approved directory; `realpath` + prefix boundary; reject wrappers, symlinks вне roots, oversized files; JSON Schema allowlist keys.
- **Риск совместимости:** высокий для установок, использующих custom paths; нужен migration warning/explicit allowlist filter.
- **Тесты:** absolute/traversal/wrapper/symlink/outside-root rejected; approved file accepted; malformed/large/deep JSON bounded; secrets не попадают в inspector.
- **Сравнение:** архитектурный аудит определил это как риск; security trace подтверждает admin-authenticated arbitrary path read.

### QTX-SEC-004 — Публично исполняемый dev-конвертер пишет файлы

- **Статус:** CONFIRMED vulnerability
- **Severity:** LOW
- **CWE:** CWE-862, CWE-749
- **Файл:** `dev/xml2po.php`
- **Функция:** top-level script
- **Точные места:** `xml2po.php:1-8`, `247`, `261`
- **Уязвимое поведение:** файл не имеет `ABSPATH` guard и при прямом HTTP-вызове запускает glob/парсинг, создаёт `language-translations` и пишет множество `.po` файлов. Входные пути фиксированы, поэтому arbitrary path write не подтверждён.
- **Предусловия:** dev directory доступна из web; PHP process может писать в соответствующий каталог; ожидаемые source XML присутствуют либо ошибки отображаются.
- **Сценарий:** unauthenticated запросы многократно запускают тяжёлую конверсию/запись, расходуют CPU/I/O/disk; при `display_errors` раскрываются filesystem paths.
- **Impact:** ограниченный DoS/нежелательная модификация filesystem и information disclosure.
- **Рекомендация:** исключить `dev/` из release artifact либо добавить CLI-only/ABSPATH guard; запретить web execution серверным правилом.
- **Риск совместимости:** низкий; dev workflow перенести в CLI.
- **Тесты:** прямой HTTP request возвращает 403/404 и ничего не пишет; CLI tool работает только с explicit output directory.

## Потенциальные риски

### QTX-SEC-005 — Dynamic module include допускает traversal при подмене option

- **Статус:** POTENTIAL risk
- **Severity:** HIGH
- **CWE:** CWE-22, CWE-98
- **Файл:** `src/modules/module_loader.php`
- **Метод:** `QTX_Module_Loader::load_active_modules()`
- **Точное место:** `module_loader.php:31-39`
- **Поведение:** keys массива `qtranslate_modules_state` напрямую попадают в `require_once QTRANSLATE_DIR . '/src/modules/' . $module_id . '/loader.php'`. Runtime не сверяет id с `QTX_Admin_Module::get_modules()`, не нормализует path и сам комментирует, что доверяет state.
- **Предусловия:** атакующий должен отдельно получить запись произвольного key/value в option (SQLi, compromised admin/import, malicious plugin, DB write) и разместить/найти подходящий `loader.php` по traversal path. Эти возможности данным репозиторием для unauthenticated attacker не подтверждены.
- **Сценарий:** key `../../../../uploads/evil` со state `1` приводит к включению вычисленного PHP пути, если конечный `loader.php` существует.
- **Impact:** PHP code execution и полный site compromise.
- **Рекомендация:** static registry `id => absolute loader`; `array_intersect_key`; `realpath` boundary; никогда не строить include из option key.
- **Риск совместимости:** низкий для встроенных модулей, средний для недокументированных сторонних module ids.
- **Тесты:** traversal, absolute, null-byte-like, unknown ids ignored/logged; только registry loaders включаются; tampered option self-heals.
- **Сравнение:** подтверждает опасный sink из архитектурного аудита, но корректирует классификацию: без отдельной primitive записи option это не самостоятельная подтверждённая LFI.

### QTX-SEC-006 — PHP object injection sinks в переводе options/metadata

- **Статус:** POTENTIAL risk
- **Severity:** HIGH
- **CWE:** CWE-502
- **Файлы:** `src/frontend.php`, `src/admin/admin_utils_db.php`, `src/admin/import_export.php`, ACF fields
- **Функции:** `qtranxf_translate_deep()`, `qtranxf_translate_metadata()`, conversion/import helpers
- **Точные места:** `frontend.php:358-380`, `720-745`, `767-772`; `admin_utils_db.php:196-218`, `275-295`; `import_export.php:48-54`; `acf/fields/post_object.php:28-30`, `74-78`
- **Поведение:** `unserialize()`/`maybe_unserialize()` разрешают class instantiation; переводчик намеренно рекурсивно мутирует objects. Для прямого `unserialize()` достаточно serialized value с ML marker.
- **Предусловия:** attacker-controlled serialized option/meta должен попасть в обрабатываемый key; для RCE нужен подходящий autoloaded gadget chain. Доступная lower-privilege write primitive и gadget chain не подтверждены.
- **Сценарий:** пользователь/интеграция сохраняет crafted serialized object с `[:en]` в разрешённое meta; frontend read вызывает magic methods/gadget chain.
- **Impact:** от object instantiation/DoS до code execution/site compromise.
- **Рекомендация:** `unserialize($v, ['allowed_classes' => false])`; по возможности обрабатывать serialized structure без object hydration; reject objects and `__PHP_Incomplete_Class`; документировать supported scalar/array types.
- **Риск совместимости:** средний/высокий, если легитимные options содержат objects; требуется telemetry и allowlist только конкретных value objects.
- **Тесты:** malicious `__wakeup/__destruct` не вызывается; scalar/array round-trip; nested ML values; corrupted serialized strings безопасно отклоняются.

### QTX-SEC-007 — Возможный Host-header/open redirect в canonical language redirect

- **Статус:** POTENTIAL risk
- **Severity:** MEDIUM
- **CWE:** CWE-601, CWE-346
- **Файлы:** `src/init.php`, `src/language_detect.php`, `src/url.php`
- **Функции:** `qtranxf_init_language()`, `qtranxf_check_url_maybe_redirect()`
- **Точные места:** `init.php:66-69`; `language_detect.php:459-487`; `url.php:157+`
- **Поведение:** `HTTP_HOST`/`REQUEST_URI` формируют `$url_orig`; redirect выполняется через `wp_redirect`, не `wp_safe_redirect`. В некоторых URL modes converted target опирается на request url/global `url_info`; filter также может заменить target.
- **Предусловия:** web server/WordPress принимает attacker-controlled Host без canonical host enforcement; конкретный URL mode должен сохранить hostile host в target. Статически полная достижимость не доказана, поскольку URL helpers частично возвращают configured home host.
- **Сценарий:** request с forged Host и language mismatch получает 301 на attacker domain; возможны phishing/cache poisoning.
- **Impact:** open redirect, host-header poisoning, неверный permanent cache.
- **Рекомендация:** canonical allowed hosts из `home_url/site_url/domains`, `wp_safe_redirect`, reject CR/LF/invalid ports, use 302/307 до уверенной canonicalization.
- **Риск совместимости:** средний для reverse proxy/domain mode.
- **Тесты:** forged Host/Forwarded headers, ports, IPv6, CRLF, all URL modes, multisite/proxy; Location остаётся allowlisted.

### QTX-SEC-008 — Глобальный REST interceptor допускает повреждение ML-данных

- **Статус:** POTENTIAL risk
- **Severity:** MEDIUM
- **CWE:** CWE-20, CWE-362
- **Файл:** `src/admin/block_editor.php`
- **Методы:** `rest_request_before_callbacks()`, `rest_request_after_callbacks()`
- **Точные места:** `block_editor.php:96-147`, `160-171`
- **Поведение:** любой POST/PUT REST request с `qtx_editor_lang` проходит обработку независимо от route/controller; язык не валидируется; JSON decode/post lookup errors не проверяются; read-modify-write берёт текущий post из DB без optimistic locking.
- **Предусловия:** REST caller уже должен пройти permission callback целевого controller и иметь возможность изменять запись. Обход core authorization не подтверждён.
- **Сценарий:** authenticated author отправляет unknown/malformed language или конкурирующие updates; чужие языковые значения теряются/появляются некорректные markers, возможен 500 с path disclosure при display_errors.
- **Impact:** целостность/доступность переводов, warnings; не privilege escalation.
- **Рекомендация:** route/post-type/method scope; validate against enabled languages; validate JSON/string shapes/post existence; use registered REST field/schema; concurrency/revision guard.
- **Риск совместимости:** средний для custom REST controllers.
- **Тесты:** invalid language/id/body/route, autosave/revision, concurrent edits, forbidden request, arrays vs strings, no mutation on WP_Error.
- **Сравнение:** архитектурный аудит верно выделил data-integrity риск; security audit не подтверждает authorization bypass.

### QTX-SEC-009 — SQL injection sink через option-driven LIKE patterns

- **Статус:** POTENTIAL risk
- **Severity:** MEDIUM
- **CWE:** CWE-89
- **Файл:** `src/frontend.php`
- **Функция:** `qtranxf_filter_options()`
- **Точные места:** `frontend.php:403-424`
- **Поведение:** значения `$q_config['filter_options']` конкатенируются в `option_name LIKE "..."` без `$wpdb->prepare()`/`esc_like()`. Admin form применяет `sanitize_text_field`, но кавычки SQL не экранирует.
- **Предусловия:** атакующий должен контролировать `qtranslate_filter_options` либо filter/config registration. Встроенный save требует `manage_options`+nonce; unauthenticated option write не найден.
- **Сценарий:** poisoned option меняет SELECT condition, создаёт expensive query или потенциальную SQL injection в зависимости от DB mode/payload.
- **Impact:** чтение names options, DoS; stacked statements обычно недоступны через wpdb/MySQL, но полагаться на это нельзя.
- **Рекомендация:** динамические placeholders, `$wpdb->esc_like`, строго ограниченная wildcard grammar; не смешивать identifiers/data.
- **Риск совместимости:** низкий, если `%`/`_` wildcard поведение сохранено явно.
- **Тесты:** quotes, backslash, `%`, `_`, multibyte, malicious patterns; SQL remains parameterized.

### QTX-SEC-010 — Unsafe output/DOM sinks при poisoning options/config

- **Статус:** POTENTIAL risk
- **Severity:** MEDIUM
- **CWE:** CWE-79
- **Файлы:** `src/admin/admin.php`, `src/admin/admin_settings.php`, `src/modules/slugs/admin.php`, ACF field renderers, `js/core/hooks/handlers.js`
- **Функции:** admin footer/config/CSS renderers, settings forms, `createSetOfLSBwith()`
- **Точные места:** `admin.php:453-460`, `478-488`, `515-518`; `admin_settings.php:128-216`, `339-400`, `810`, `829-831`; `slugs/admin.php:140-143`, `405-407`, `544`; `handlers.js:1028-1046`
- **Поведение:** множество option/config/metadata values выводятся без контекстного escaping; `json_encode` вставляется в `<script>` без `wp_json_encode`/JSON_HEX flags; deprecated config `javascript` выводится как код; language name устанавливается через `innerHTML`.
- **Предусловия:** attacker должен отравить admin-only options/config/term meta или добиться сохранения payload через стороннюю интеграцию. Штатный language save использует `sanitize_text_field`, а WordPress/ACF часто выполняет собственную sanitization; поэтому lower-privilege exploit не подтверждён.
- **Сценарий:** poisoned `qtranslate_language_names`/admin config содержит `</script>` или HTML, выполняемый при открытии admin page.
- **Impact:** stored admin XSS → takeover администратора/site compromise.
- **Рекомендация:** `wp_json_encode(... JSON_HEX_TAG...)`; отказаться от executable JSON config; `textContent`, не `innerHTML`; `esc_html/attr/url/textarea/js` по контексту; ACF escaping helpers.
- **Риск совместимости:** средний для намеренно HTML-содержащих display fields; отделить display HTML от identifiers/labels.
- **Тесты:** payloads в каждом option/meta/config; `</script>`, quotes, SVG/event handlers; legitimate ML HTML сохраняется в content, но labels/attrs остаются escaped.

### QTX-SEC-011 — Admin DB splitter принимает произвольный filesystem path

- **Статус:** POTENTIAL risk
- **Severity:** MEDIUM
- **CWE:** CWE-22, CWE-73
- **Файл:** `src/admin/admin_utils_db.php`
- **Функции:** `qtranxf_convert_database()`, `qtranxf_split_database_file()`
- **Точные места:** `admin_utils_db.php:16-33`, `305-420`
- **Поведение:** `db_file` после `sanitize_text_field` передаётся в `fopen('r')`; outputs создаются рядом с input, копируются и один файл может удаляться. Языки из файла влияют на output filename, хотя regex ограничивает codes 2–3 letters.
- **Предусловия:** `manage_options` + valid settings nonce; filesystem readable/writable; для disclosure результат должен оказаться доступен атакующему/admin.
- **Сценарий:** администратор/compromised session читает произвольный файл и создаёт/перезаписывает derived `.sql` files в его каталоге. Не подтверждена запись PHP extension или прямой вывод содержимого.
- **Impact:** локальное file probing, overwrite `.sql`, disk usage, возможное раскрытие через web-accessible output.
- **Рекомендация:** работать только с uploaded SQL через WP Filesystem/upload validation или fixed private directory; canonical path boundary; non-overwrite names; size limits; no follow-symlink.
- **Риск совместимости:** высокий для текущего workflow с server-side path.
- **Тесты:** traversal/symlink/special files/huge input/unwritable dir/existing output; output non-public and unique.

## Hardening findings

### QTX-SEC-012 — Parser сохраняет активный HTML и неоднозначно обрабатывает malformed markers

- **Статус:** HARDENING recommendation
- **Severity:** LOW
- **CWE:** CWE-116 (контекстно; самостоятельной XSS нет)
- **Файлы:** `src/language_blocks.php`, `js/core/multi-lang/parser.js`
- **Функции:** `qtranxf_get_language_blocks()`, `qtranxf_split_blocks()`, `qtranxf_use*()`, JS `splitTokens/parseTokens`
- **Точные места:** `language_blocks.php:10-108`, `357-474`; `parser.js:19-101`
- **Поведение и test vector:** строка `[:lv]<script>alert(1)</script>[:ru]test[:]` разбивается как lv=`<script>alert(1)</script>`, ru=`test`; parser намеренно не sanitizes HTML. Это правильно архитектурно: parser и HTML policy должны оставаться раздельными. Malformed/duplicate/nested markers переключают state, а unknown language создаёт новый key в PHP; PHP и JS могут расходиться по trimming/unknown keys.
- **Предусловия:** XSS возникает только если upstream допускает dangerous HTML и downstream выводит результат как trusted HTML. Для `post_content` WordPress KSES/capability model обычно является границей; для arbitrary options/meta это зависит от consumer.
- **Сценарий:** интеграция сохраняет unsanitized ML value в поле, затем theme/plugin выводит selected translation без appropriate KSES/escaping.
- **Impact:** потенциальная stored XSS в конкретном consumer, не в parser сам по себе.
- **Рекомендация:** не добавлять stripping в parser. Документировать trust contract; sanitize при записи согласно типу поля или escape при output; corpus/fuzz tests malformed markers и PHP↔JS parity; limit input size/marker count.
- **Риск совместимости:** высокий, если ошибочно sanitizing parser уничтожит legitimate HTML/markers; рекомендуемый подход такого риска не создаёт.
- **Тесты:** заданный script vector сохраняется parser-ом, но блокируется policy layer для недоверенного поля; разрешённый HTML остаётся; nested/duplicate/unclosed/unknown/case/huge markers deterministic; round-trip parity.

### QTX-SEC-013 — Slug save nonce проверяется fail-open

- **Статус:** HARDENING recommendation
- **Severity:** MEDIUM
- **CWE:** CWE-352
- **Файл:** `src/modules/slugs/admin.php`
- **Функции:** `qtranxf_slugs_save_postdata()`, `qtranxf_slugs_save_term()`
- **Точные места:** `admin.php:268-290`, `366-385`
- **Поведение:** post nonce отклоняется только если присутствует и неверен; отсутствие nonce допускается. Term save вообще не проверяет module nonce и использует общую `edit_posts`, не taxonomy-specific capability.
- **Предусловия:** штатные WordPress save/term controllers сами проверяют nonce/capability до hooks, поэтому самостоятельный CSRF/privilege escalation не подтверждён. Риск появляется при необычном вызове hooks/стороннем controller.
- **Сценарий:** сторонний authorized flow вызывает save hooks без core nonce, и qTranslate slug metadata меняется из request params.
- **Impact:** изменение translated slugs/redirects.
- **Рекомендация:** require nonce when qts fields present; object/taxonomy-specific capability; verify post/term/taxonomy relationship.
- **Риск совместимости:** средний для AJAX/quick-edit integrations; отдельные documented nonces для каждого flow.
- **Тесты:** missing/wrong nonce rejected when fields present; autosave/quick edit/attachment/term AJAX preserved; exact capabilities.

### QTX-SEC-014 — Debug context удерживает raw AJAX/cron POST

- **Статус:** HARDENING recommendation
- **Severity:** LOW
- **CWE:** CWE-532, CWE-200
- **Файлы:** `src/init.php`, `src/admin/admin_utils.php`, `js/options.js`
- **Функции:** `qtranxf_init_language()`, `qtranxf_admin_debug_info()`
- **Точные места:** `init.php:49-60`; `admin_utils.php:414-493`; `options.js:108-129`
- **Поведение:** при `WP_DEBUG` весь `$_POST` AJAX/cron сохраняется в `url_info`; debug AJAX возвращает почти весь `$q_config`. Redaction удаляет часть fields, но не raw `WP_DOING_AJAX_POST/WP_DOING_CRON_POST`. Handler требует `manage_options`, SOP мешает CSRF exfiltration, поэтому disclosure постороннему не подтверждён.
- **Предусловия:** WP_DEBUG, sensitive POST в текущем request/context, admin debug access либо logs/support copy.
- **Реалистичный сценарий:** администратор воспроизводит AJAX/cron проблему с token/password-like параметром и копирует debug report в публичный issue; raw POST остаётся в `url_info`.
- **Impact:** токены/PII/passwords могут попасть в UI/log/support report.
- **Рекомендация:** не копировать POST; allowlist keys and redact secrets recursively; nonce для debug AJAX; no console payload logging.
- **Риск совместимости:** низкий.
- **Тесты:** password/token/cookie/card-like fields never returned/logged; capability+nonce required.

### QTX-SEC-015 — Read-only debug AJAX без nonce и строгого JSON API

- **Статус:** HARDENING recommendation
- **Severity:** LOW
- **CWE:** CWE-352 (ограниченно), CWE-200
- **Файлы:** `src/admin/admin.php`, `src/admin/admin_utils.php`, `js/options.js`
- **Функция:** `qtranxf_admin_debug_info()`
- **Точные места:** `admin.php:874`; `admin_utils.php:414-493`; `options.js:110-128`
- **Поведение:** capability есть, nonce нет; response через raw `echo json_encode` вместо `wp_send_json`; JS пишет полный response в console. CSRF не может обычно прочитать response из-за SOP, поэтому это не подтверждённая CSRF disclosure.
- **Предусловия:** authenticated administrator открывает config page; для утечки нужен доступ к browser console/support dump либо отдельный same-origin weakness.
- **Реалистичный сценарий:** sensitive configuration остаётся в shared-browser DevTools log и включается в diagnostic export.
- **Impact:** ограниченное раскрытие конфигурации; самостоятельного cross-origin чтения не доказано.
- **Рекомендация:** nonce, POST only, `wp_send_json_*`, `nocache_headers`, recursive redaction, убрать console logging production payload.
- **Риск совместимости:** низкий.
- **Тесты:** GET/CSRF/wrong nonce rejected; correct content type/status; no payload in console.

### QTX-SEC-016 — Language cookies не устанавливаются HttpOnly

- **Статус:** HARDENING recommendation
- **Severity:** LOW
- **CWE:** CWE-1004
- **Файл:** `src/language_detect.php`
- **Функции:** `qtranxf_setcookie_language()`, `qtranxf_set_language_cookie()`
- **Точные места:** `language_detect.php:338-363`
- **Поведение:** cookies имеют configurable `secure` и `SameSite=Lax`, но `httponly` не задан. Это не auth cookies и их чтение JS может быть функционально нужно; самостоятельного security impact почти нет.
- **Предусловия:** уже существующая XSS или hostile same-origin script.
- **Реалистичный сценарий:** script меняет language cookie, вызывая нежелательные redirects/локализацию; authentication cookie этим не раскрывается.
- **Impact:** при XSS attacker может менять/читать language preference; auth не компрометируется.
- **Рекомендация:** если JS не использует cookies напрямую, установить HttpOnly; всегда строго validate enabled language; Secure на HTTPS.
- **Риск совместимости:** проверить client language switching.
- **Тесты:** cookie flags on HTTP/HTTPS; invalid/oversized cookie ignored.

## Информационные findings

### QTX-SEC-017 — Remote requests и secrets отсутствуют

- **Статус:** HARDENING recommendation
- **Severity:** INFORMATIONAL
- **CWE:** не применимо
- **Охват:** весь репозиторий
- **Затронутые функции/точки:** отсутствуют; проверены PHP/JS/config/build files.
- **Точное место:** не применимо — dangerous sink не найден.
- **Результат:** не найдены `wp_remote_get/post`, cURL или user-controlled remote fetch; SSRF sink не обнаружен. Не найдены API keys, tokens, private keys или credentials. Gettext updater по текущему коду не содержит собственного remote transport.
- **Предусловия/сценарий:** не применимо для текущего кода; риск появится при добавлении remote updater/config loader.
- **Impact:** текущий подтверждённый impact отсутствует.
- **Рекомендация:** сохранить secret scanning и dependency audit в CI; при добавлении remote config использовать `wp_safe_remote_*`, host allowlist и size/time limits.
- **Риск совместимости:** отсутствует для CI-only мер.
- **Тесты:** automated secret scan; grep/static rule на новые remote sinks.

### QTX-SEC-018 — Deprecated APIs, weak types и warnings повышают disclosure/DoS риск

- **Статус:** HARDENING recommendation
- **Severity:** INFORMATIONAL
- **CWE:** CWE-703
- **Файлы:** `src/deprecated.php`, `src/date_time.php`, `src/modules/acf/admin.php`, `src/modules/slugs/slugs.php`, REST/parser/admin code
- **Точные места:** `deprecated.php:239-303` (`strftime`); `acf/admin.php:595` (`FILTER_SANITIZE_STRING`); `slugs/slugs.php:997` (`escape_by_ref`); многочисленные `assert`, array/string assumptions.
- **Поведение:** PHP 8.x deprecations, removed constants/APIs, unchecked null/array access и `assert` as validation могут выдавать warnings/paths при `display_errors` или приводить к request DoS. `wpdb->show_errors()` включается в migration paths.
- **Предусловия:** несовместимая PHP/WordPress версия или malformed input; `display_errors`/видимый DB error output повышает impact.
- **Реалистичный сценарий:** malformed REST body вызывает type warning/fatal, и misconfigured production server раскрывает абсолютные пути/строки SQL.
- **Impact:** information disclosure и request-level DoS; самостоятельный site compromise не подтверждён.
- **Рекомендация:** compatibility CI PHP 7.4–8.4/WordPress matrix; не использовать assert для input validation; production error display off; structured error handling.
- **Риск совместимости:** средний при удалении legacy facade, низкий для guard checks.
- **Тесты:** malformed types/JSON/REST values never emit warnings; PHP matrix with `E_ALL`.

## Дополнительный анализ по обязательным областям

### Authentication и authorization

`is_admin()` используется для выбора runtime, а не как явная authorization check; это допустимо только при отсутствии write operations. Главная write page окружена `manage_options`, но CSRF остаётся. AJAX notices нарушают authorization. Core REST permission callbacks сохраняются, потому что плагин не регистрирует routes; bypass не подтверждён. ACF nopriv action повторяет inherited ACF handler — его фактическая disclosure-модель зависит от установленной версии ACF и должна проверяться integration tests; собственный plugin code не добавляет permission checks.

### SQL

Большинство updates используют `$wpdb->prepare`. Slugs migration динамически подставляет table/column, но callers передают только `$wpdb->postmeta/termmeta` и literals, поэтому SQLi там не подтверждена. `admin_options_update.php:153-154` вставляет language code, однако custom language validator ограничивает `[a-z]{2,3}`; legacy uppercase также безопасен. Реальный dangerous pattern — option-driven LIKE в QTX-SEC-009. `ORDER BY`/`LIMIT` с user input не найдены.

### Serialization

Прямого `unserialize($_POST)` нет. Опасность возникает при чтении уже сохранённых serialized options/meta. `maybe_unserialize` также разрешает objects. Без write primitive/gadget chain это POTENTIAL, но высокий приоритет defence-in-depth.

### Output и JavaScript

Parser не должен быть sanitizer. Для post content следует сохранять WordPress KSES/capability semantics. Labels, attributes, URLs, JSON и JS должны escape-иться в точке вывода. JS display hooks обычно меняют `nodeValue`/`setAttribute`, что безопаснее HTML insertion; исключения — два `innerHTML` в LSB. Статических `eval`, `new Function`, `document.write` в production JS не найдено. jQuery `.append()` использует только constant HTML в core page script.

### Integrations

- **ACF:** inherited nopriv query и object deserialization требуют versioned integration tests; raw render paths зависят от ACF helpers.
- **WooCommerce:** language/cookie/session data доверяется после core resolution; webhook path глобально flushes cache, но attacker-triggered unauthorized webhook не подтверждён.
- **Yoast/AIOSEO/Jetpack/Gravity Forms:** переводят third-party data без sanitization, предполагая, что owner integration применит собственный output policy.
- **Gutenberg:** подтверждён integrity risk, но core REST authorization bypass не найден.
- **Slugs:** nonce fail-open, raw rendering и direct SQL требуют hardening; slug input проходит `sanitize_title`.
- **i18n-config:** arbitrary JSON path read подтверждён для администратора; executable/deprecated JS config повышает XSS impact при config poisoning.

## REMEDIATION ROADMAP

Порядок выбран по сочетанию security impact, exploitability и минимального риска совместимости.

1. **QTX-SEC-001:** немедленно запретить state-changing GET, добавить nonce/capability внутри handlers. Самый высокий подтверждённый риск; формат данных не меняется.
2. **QTX-SEC-002:** nonce+capability+allowlist AJAX notices. Очень низкий compatibility risk.
3. **QTX-SEC-005:** статический module registry/allowlist. Предотвращает потенциальный RCE chain, почти не влияет на штатные модули.
4. **QTX-SEC-006:** запретить object hydration, сначала в frontend metadata/options; добавить compatibility logging/tests.
5. **QTX-SEC-007:** allowed hosts + safe redirect, начиная с non-domain URL modes; затем proxy/domain compatibility.
6. **QTX-SEC-008:** route/language/body validation и REST error guards; отдельно решить optimistic concurrency.
7. **QTX-SEC-003:** approved roots/realpath/JSON Schema; дать migration path custom installations.
8. **QTX-SEC-009:** parameterize LIKE patterns и определить wildcard contract.
9. **QTX-SEC-010:** системный output-context audit; сначала JSON-in-script/innerHTML/admin attributes, затем integration templates.
10. **QTX-SEC-013:** require nonce для slug fields и точные capabilities с regression tests AJAX/quick edit.
11. **QTX-SEC-011:** перенести DB splitter в private bounded filesystem workflow.
12. **QTX-SEC-014/015:** минимизировать debug data, добавить nonce/JSON API, убрать console payload.
13. **QTX-SEC-004:** исключить/заблокировать web execution `dev/` в release packaging.
14. **QTX-SEC-012/016/018:** parser contract/fuzzing, cookie flags, compatibility/error hardening.

Ни один шаг не требует уничтожать legitimate HTML или qTranslate markers. HTML sanitization должна оставаться policy layer вокруг parser-а, а не частью грамматики ML-строки.

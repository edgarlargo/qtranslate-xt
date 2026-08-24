# Архитектурный аудит qTranslate-XT

Дата аудита: 2026-08-21
Ветка: `modernisation`
Версия плагина: `3.16.1`
Метод: статический анализ PHP, JavaScript, JSON-конфигурации и метаданных сборки. Производственный код не изменялся.

## Резюме

qTranslate-XT хранит переводы не в отдельных сущностях, а внутри одного строкового значения, используя маркеры `[:en]...[:]`, `<!--:en-->...<!--:-->` и `{:en}...{::}`. Во время каждого запроса плагин очень рано определяет язык, заполняет глобальный массив `$q_config`, затем навешивает широкую сеть фильтров WordPress, которые переводят контент при чтении. Административный JavaScript раскладывает/собирает те же строки по языковым полям.

Архитектура работоспособна, но тесно связана с глобальным состоянием WordPress, порядком хуков, внутренними форматами данных и DOM старого редактора. Наиболее срочные направления модернизации:

1. **P0 — безопасность:** nonce/capability для AJAX уведомлений; allowlist для динамической загрузки модулей; строгая валидация языка, host/redirect и i18n-config; аудит прямого SQL.
2. **P0 — целостность данных:** REST/Gutenberg перехватывает все изменяющие REST-запросы с параметром `qtx_editor_lang` и повторно собирает поля из текущей БД без явного контроля ревизии/конкурентного изменения.
3. **P1 — ядро:** выделить типизированные сервисы конфигурации, контекста запроса, парсера и URL вместо `$q_config` и процедурных глобальных функций.
4. **P1 — совместимость:** заменить устаревшие хуки и API, локализовать фильтры метаданных/SQL, прекратить отключение редактора виджетов глобально.
5. **P2 — сопровождение:** тесты отсутствуют (`npm test` намеренно завершается ошибкой); нужны unit/property-тесты парсера, integration-тесты URL/REST/AJAX и матрица совместимости модулей.

Шкала приоритета: **P0** — до любого функционального рефакторинга; **P1** — первый этап модернизации; **P2** — следующий релизный цикл; **P3** — плановое улучшение.

## 1. Bootstrap и последовательность загрузки

- **Файлы:** `qtranslate.php`, `src/init.php`, `src/options.php`, `src/language_detect.php`, `src/hooks.php`, `src/date_time.php`, `src/frontend.php`, `src/admin/admin.php`, `src/admin/activation_hook.php`, `src/modules/module_loader.php`.
- **Назначение:** определить константы, загрузить конфигурацию, распознать язык и контекст, подключить общие/frontend/admin-фильтры и активные модули.
- **Порядок загрузки:** WordPress включает `qtranslate.php` → константы `QTX_VERSION`, `QTRANSLATE_FILE`, `QTRANSLATE_DIR` → `src/init.php` и его базовые зависимости → регистрация `qtranxf_init_language()` на `plugins_loaded` с приоритетом 2 → в admin/WP-CLI подключается activation hook → `qtranxf_load_config()` → сбор `url_info` → `qtranxf_detect_language()` и возможный redirect → REST rewrite/widget/common/date hooks → `qtranslate_load_front_admin` → развилка `frontend.php` или `admin.php` → `QTX_Translator` → активные модули → compatibility API → `qtranslate_init_language`.
- **Зависимости:** WordPress Plugin API, глобальные `$q_config`, `$pagenow`, `$_SERVER`, `$_COOKIE`; PHP `intl` и JSON.
- **Публичные хуки/функции:** `qtranxf_init_language()`, `qtranslate_load_front_admin`, `qtranslate_init_language`, `qtranslate_language`, `qtranslate_load_config`; интеграционный facade `QTX_Translator` и фильтры `translate_text`, `translate_term`, `translate_url`, `get_language`.
- **Риски:** слишком ранний приоритет до полной готовности других плагинов; побочные эффекты и redirect во время bootstrap; порядок `require_once` является неявным DI; глобальное состояние трудно тестировать; `WP_DEBUG` сохраняет весь `$_POST` AJAX/cron в `$q_config['url_info']`, после чего эти данные потенциально попадают в debug output.
- **Приоритет:** **P1**, а защита debug-данных — **P0**.

## 2. Структура каталогов

- **Файлы/каталоги:** корневой `qtranslate.php`; `src/` — runtime PHP; `src/admin/` — admin и миграции; `src/modules/` — встроенные адаптеры; `js/` — исходники ES modules; `dist/` — собранные bundle; `i18n-config.json` и `i18n-config/` — декларативные интеграции; `css/`, `flags/`, `img/`, `lang/`; `dev/` — ручные тесты/исторические инструменты.
- **Назначение:** разделение по окружению и интеграциям, однако доменная логика остаётся процедурной и распределённой.
- **Порядок загрузки:** PHP подключается вручную через `require_once`; Composer-autoload отсутствует; JS собирается Webpack из пяти entry points в `dist/`.
- **Зависимости:** `composer.json` требует PHP >=7.4, `ext-intl`, `ext-json`, `composer/installers`; npm содержит только Babel/Webpack.
- **Публичные точки:** главный файл плагина и committed `dist/*.js`; JSON-конфигурации фактически являются extension API.
- **Риски:** нет namespaces/PSR-4; production и dev/legacy assets лежат рядом; огромные наборы флагов и переводов усложняют пакет; исходники и bundle могут рассинхронизироваться; отсутствует автоматический test/lint pipeline.
- **Приоритет:** **P2**; ввести `src/Core`, `src/Admin`, `src/Integration`, autoload и CI, не меняя формат хранения в первом этапе.

## 3. Многоязычный парсер

- **Файлы:** `src/language_blocks.php`, `src/hooks.php`, `src/class_translator.php`, JS-зеркало `js/core/multi-lang/parser.js`, consumers в `frontend.php`, admin, ACF и Gutenberg.
- **Назначение:** обнаружить ML-строку, разбить на языковые блоки, выбрать перевод, показать fallback и собрать строку обратно.
- **Порядок:** `qtranxf_isMultilingual()` → `qtranxf_get_language_blocks()`/`qtranxf_split()` → `qtranxf_split_blocks()` или `qtranxf_split_languages()` → `qtranxf_use()`/`qtranxf_use_language()` → `qtranxf_use_block()`/`qtranxf_use_content()`; сохранение выполняют `qtranxf_join_b/c/s()` и join-by-line/separator.
- **Зависимости:** `QTX_LANG_CODE_FORMAT`, `$q_config['enabled_languages']`, default language, fallback-настройки и filter `qtranslate_content_translation_not_available`.
- **Публичные функции/хуки:** `qtranxf_split`, `qtranxf_join_b`, `qtranxf_join_c`, `qtranxf_join_s`, `qtranxf_use`, `qtranxf_getAvailableLanguages`, семейство `qtranxf_useCurrentLanguage*`; `translate_text`; JS `qTranx.ml.splitLangs/splitTokens/parseTokens`.
- **Риски:** три синтаксиса и две независимые реализации PHP/JS могут расходиться; regex-парсинг не является полноценной грамматикой и чувствителен к повреждённым/вложенным маркерам; неизвестные языки и текст вне блоков имеют сложную fallback-семантику; repeated parsing на каждом фильтре создаёт нагрузку; маркеры могут повреждаться HTML sanitizer/editor-ами; нет property/fuzz-тестов round-trip.
- **Приоритет:** **P1**. Создать единую спецификацию грамматики и corpus-тесты `parse(join(x))`, затем immutable `MultilingualValue`; сохранить legacy parser как compatibility layer.

## 4. Определение языка

- **Файлы:** `src/language_detect.php`, `src/url.php`, `src/utils.php`, `src/init.php`, `src/options.php`; дополнительное влияние `src/modules/woo-commerce/loader.php` и slugs.
- **Назначение:** извлечь язык из query/path/domain, cookie, referer или `Accept-Language`, определить admin/front и канонический URL.
- **Порядок:** начальный `url_info` → `qtranxf_parse_language_info()` → admin `qtranxf_detect_language_admin()` либо front `qtranxf_detect_language_front()` → фильтры `qtranslate_detect_*` → cookie → `qtranxf_check_url_maybe_redirect()`.
- **Зависимости:** URL modes `QTX_URL_QUERY/PATH/DOMAIN/DOMAINS`, WordPress rewrite/home/siteurl, HTTP headers, enabled/default languages, cookies.
- **Публичные функции/хуки:** `qtranxf_detect_language`, `qtranxf_get_browser_language`, `qtranxf_http_negotiate_language`, `qtranxf_set_language_cookie`; `qtranslate_detect_language`, `qtranslate_parse_language_info(_mode)`, `qtranslate_detect_admin_language`, `qtranslate_language_detect_redirect`.
- **Риски:** `HTTP_HOST` и `REQUEST_URI` участвуют в построении redirect; используется `wp_redirect`, а не `wp_safe_redirect`; cookie читаются до строгой нормализации; эвристическое определение REST/AJAX/GraphQL; referer влияет на язык; 301 может закэшировать ошибочное решение; собственный parser `Accept-Language`; переписывание `$_SERVER['REQUEST_URI']` создаёт неочевидные эффекты.
- **Приоритет:** **P0/P1**. Ввести валидированный `RequestLanguageContext`, разрешённые hosts и `wp_safe_redirect`; покрыть таблицей тестов все URL modes и proxy/multisite случаи.

## 5. Frontend-фильтры

- **Файлы:** `src/frontend.php`, `src/hooks.php`, `src/utils.php`, `src/taxonomy.php`, `src/date_time.php`, `src/url.php`, `i18n-config.json`.
- **Назначение:** перевод posts/terms/options/meta/gettext, URL, меню, RSS/oEmbed/date-time; опционально скрывать непереведённый контент.
- **Порядок:** common `qtranxf_add_main_filters()` и date filters → `qtranxf_load_front_config()` → `qtranxf_add_front_filters()` → декларативные `qtranxf_add_filters()` из `front-config`.
- **Зависимости:** текущий язык, parser, WP_Query, `$wpdb`, metadata API, URL converter, i18n-config.
- **Публичные hooks/functions:** WordPress filters `the_content`, `the_title`, `the_posts`, `pre_get_posts`, `get_*_metadata`, `gettext`, `home_url`, `redirect_canonical`, term/link/RSS filters; qTranslate filters `qtranslate_front_config`, `qtranslate_convert_url`; helpers `qtranxf_add_filters`/`qtranxf_remove_filters`.
- **Риски:** фильтры с приоритетом 0/5 и глобальные metadata/gettext hooks имеют большой blast radius; SQL fragments для скрытия переводов зависят от внутреннего хранения; recursive metadata filters требуют защит; `esc_html` фильтруется глобально; совместимость с object cache и сторонними запросами хрупкая; некоторые старые hooks уже не являются основной точкой WordPress.
- **Приоритет:** **P1**. Сначала инвентаризировать наблюдаемые контракты тестами, затем ограничить фильтры контекстом и отказаться от SQL-модификации в пользу query service/index.

## 6. Admin-фильтры

- **Файлы:** `src/admin/admin.php`, `admin_utils.php`, `admin_taxonomy.php`, `admin_options*.php`, `admin_settings*.php`, `user_options.php`, `import_export.php`, `admin_utils_db.php`, `i18n-config.json`, JS core/pages.
- **Назначение:** интерфейс языков, LSB/SLM редакторы, сбор переводов из формы, перевод term/admin UI, настройки, импорт/экспорт и миграции БД.
- **Порядок:** `qtranxf_admin_load()` → config → hooks `plugins_loaded(5)`, `admin_init(2)`, enqueue/footer/menu → `qtranslate_init_language(20)` выбирает page config → JS `qtranx.load` создаёт content/display hooks → перед сохранением `qtranxf_collect_translations_posted()` мутирует superglobals.
- **Зависимости:** конкретные admin page names/DOM selectors, jQuery, TinyMCE, `wp.hooks`, `$q_config`, current user/capabilities.
- **Публичные hooks/functions:** `qtranslate_admin_config`, `qtranslate_admin_page_config`, `qtranslate_configuration`, `qtranslate_update_settings(_pre/_admin)`, `qtranslate_save_config`; JS actions `qtranx.load`, `qtranx.languageSwitch`.
- **Риски:** прямое изменение `$_REQUEST`, `$_POST`, `$_GET`; зависимость от DOM и внутренних editor API; settings действия частично идут через GET; capability/nonce логика распределена; вывод некоторых POST значений и admin notices требует системного escaping-аудита; TinyMCE/Classic Editor является центральной зависимостью.
- **Приоритет:** **P1**, security/escaping review — **P0**.

## 7. Использование `wp_options`

- **Файлы:** прежде всего `src/options.php`, `src/admin/admin_options_update.php`, `src/admin/activation_hook.php`, `src/admin/import_export.php`, module manager/loaders и настройки ACF/slugs.
- **Назначение:** хранить глобальную конфигурацию, языковые словари, i18n-config cache, состояния модулей и уведомления.
- **Порядок:** `qtranxf_set_default_options()` → множество `qtranxf_load_option*()` → merge predefined/stored language properties → `qtranslate_load_config`; запись через admin update/activation/module settings.
- **Зависимости:** Options API и глобальные `$qtranslate_options`, `$q_config`.
- **Ключи:** `qtranslate_default_language`, `enabled_languages`, `language_names`, `locales`, `locales_html`, `na_messages`, `date_formats`, `time_formats`, `flags`, `front_config`, `admin_config`, `config_files`, `custom_i18n_config`, `filter_options`, `text_field_filters`, `domains`, `header_css`, `qtrans_compatibility`, `ignore_file_types`, `modules_state`, `module_acf`, `module_slugs`, `admin_notices`, `config_errors`, `next_update_mo`, `next_thanks`, временный `term_name`; user meta `qtranslate_highlight_disabled`; slug translations — post/term meta `qtranslate_slug_{lang}`.
- **Публичные функции/хуки:** `qtranxf_load_config`, `qtranxf_reload_config`, `qtranxf_load_option*`, `qtranslate_option_config`, `qtranslate_load_config`, `qtranslate_save_config`.
- **Риски:** десятки разрозненных ключей без schema/versioned migration; неизвестная/непоследовательная autoload-политика; большие JSON/config arrays могут автозагружаться; временное состояние term translations в глобальном option создаёт race condition между параллельными запросами; импорт/сброс имеет широкий радиус; модульный option влияет на файловый include.
- **Приоритет:** **P0** для `term_name` race и module state; **P1** для единой schema, autoload audit и versioned migrations.

## 8. AJAX

- **Файлы:** `src/admin/admin.php`, `src/admin/admin_utils.php`, `src/admin/admin_notices.php`, `js/notices.js`, `js/options.js`, ACF post-object field; косвенно WooCommerce AJAX.
- **Назначение:** debug info, dismiss/reset admin notices, ACF queries и сохранение UI настроек.
- **Порядок:** admin bootstrap регистрирует `wp_ajax_admin_debug_info`; notices регистрирует `wp_ajax_qtranslate_admin_notice`; JS отправляет `$.post` в `ajaxurl`.
- **Зависимости:** WordPress AJAX dispatcher, authentication cookie, jQuery; ACF собственный AJAX contract.
- **Публичные actions/functions:** `wp_ajax_admin_debug_info` → `qtranxf_admin_debug_info`; `wp_ajax_qtranslate_admin_notice` → `qtranxf_ajax_qtranslate_admin_notice`; ACF action `acf/fields/qtranslate_post_object/query` также зарегистрирован как `nopriv`.
- **Риски:** **`qtranslate_admin_notice` не проверяет nonce и capability** — любой вошедший пользователь может менять глобальный option уведомлений через CSRF/запрос; JS не отправляет nonce. Debug handler проверяет `manage_options`, но nonce отсутствует и ответ может содержать конфигурационные/POST данные. ACF `nopriv` наследует реализацию/защиту ACF и должен быть отдельно проверен на раскрытие posts.
- **Приоритет:** **P0**. Добавить nonce + capability, JSON responses/status codes, минимизацию debug payload; проверить необходимость `nopriv`.

## 9. REST

- **Файлы:** `src/rest_api.php`, `src/admin/block_editor.php`, `src/language_detect.php`, `src/utils.php`, `js/block-editor.js`.
- **Назначение:** language-prefixed rewrites REST и адаптация Gutenberg single-language editing к ML-строкам.
- **Порядок:** rewrite rules на `init(11)`; admin class на `rest_api_init` добавляет `rest_prepare_{post_type}` и глобальные before/after callback filters; JS middleware `wp.apiFetch.use` добавляет `qtx_editor_lang` для текущего post endpoint.
- **Зависимости:** WP REST API, `use_block_editor_for_post`, REST schema posts, `wp.data`, текущая запись в БД.
- **Публичные hooks/functions:** `qtranxf_rest_api_register_rewrites`, filter `qtranslate_admin_block_editor`, `rest_prepare_*`, `rest_request_before_callbacks`, `rest_request_after_callbacks`.
- **Риски:** before/after filters глобальны для REST; доверие к клиентскому `qtx_editor_lang` без локальной проверки enabled language/capability; `get_post(id)` может вернуть null; JSON errors не обработаны; read-modify-write из БД создаёт lost update при параллельном редактировании; обработка только `title/content/excerpt`; custom post endpoints и autosave/revisions могут вести себя иначе; rewrite flush lifecycle не очевиден.
- **Приоритет:** **P0** для валидации и целостности; **P1** — REST field/schema и scoped endpoint middleware с revision/ETag semantics.

## 10. Встроенные модули

- **Файлы:** `src/modules/module_{loader,state}.php`, admin module manager/settings; каталоги `acf`, `all-in-one-seo-pack`, `events-made-easy`, `google-site-kit`, `gravity-forms`, `jetpack`, `slugs`, `woo-commerce`, `wp-seo`.
- **Назначение:** адаптеры ACF, AIOSEO, Events Made Easy, Site Kit, Gravity Forms, Jetpack, translated slugs, WooCommerce и Yoast SEO.
- **Порядок:** admin manager вычисляет state → option `qtranslate_modules_state` → после core front/admin загрузки `QTX_Module_Loader::load_active_modules()` включает `src/modules/{id}/loader.php` → большинство адаптеров окончательно ветвятся на `qtranslate_init_language`.
- **Зависимости:** активность/версии сторонних плагинов, их нестабильные hooks/API; slugs зависит от rewrite/metadata; WooCommerce меняет cache/taxonomies/webhooks.
- **Публичные hooks/functions:** `QTX_Module_Loader::is_module_active/load_active_modules`; module-specific qTranslate/WP/vendor hooks; состояния `ACTIVE/INACTIVE/BLOCKED`.
- **Риски:** **динамический `require_once` использует module id из БД без allowlist/path validation**; runtime loader доверяет сохранённому state и не проверяет наличие/совместимость; адаптеры не изолированы; WooCommerce flushes весь object cache и перерегистрирует taxonomy при webhook; slugs содержит крупный fork rewrite/link logic и прямой SQL; нет automated compatibility matrix.
- **Приоритет:** **P0** allowlist/realpath; **P1** registry с manifest/version constraints; **P2** contract tests для каждого vendor.

## 11. Интеграция ACF

- **Файлы:** `src/modules/acf/loader.php`, `extended.php`, `admin.php`, `fields/*.php`, `js/acf/*.js`, `dist/modules/acf.js`, `css/modules/acf.css`.
- **Назначение:** перевод стандартных ACF text/textarea/wysiwyg, многоязычные расширенные field types и синхронизация языковых вкладок.
- **Порядок:** module loader → немедленный `qtranxf_acf_init()` и повтор на `after_setup_theme(-10)` → ACF >=5.6 → extended filters → в admin класс/asset/config → JS регистрирует field models и hooks.
- **Зависимости:** глобальная `acf()` и внутренние классы `acf_field_*`, ACF JS API, qTranslate core JS, jQuery/underscore/wp-hooks.
- **Публичные hooks/functions:** `acf/format_value`, `acf/include_fields`, `acf/get_field_types`, `acf/input/admin_enqueue_scripts`, `acf/validate_value/type=*`; qTranslate settings hooks.
- **Риски:** минимальная версия ACF 5.6 слишком стара и не ограничивает верхнюю совместимость; наследование внутренних field classes; AJAX `nopriv` post-object; `FILTER_SANITIZE_STRING` удалён/устарел в современных PHP; настройки/field values имеют разные форматы; двойная инициализация прикрыта static flag, но порядок зависит от способа доставки ACF.
- **Приоритет:** **P1**. Поддержать актуальный ACF public API, убрать `FILTER_SANITIZE_STRING`, проверить authorization AJAX и добавить integration tests ACF 6.x.

## 12. Система i18n-config

- **Файлы:** корневой `i18n-config.json`, `i18n-config/**/i18n-config.json`, optional companion `qtx-admin.js`, `src/admin/activation_hook.php`, `src/admin/admin.php`, `src/frontend.php`, `src/admin/admin_options_update.php`, `src/utils.php`.
- **Назначение:** декларативно описывать страницы, формы, selectors/anchors, поля, encoding и PHP filters для core/themes/plugins.
- **Порядок:** activation/plugin/theme changes обнаруживают config files → `file_get_contents`/`json_decode` → merge vendor/admin/front config и custom JSON → сохранение compiled `qtranslate_admin_config`/`front_config` → на запросе page matching → PHP filters и JS hooks.
- **Зависимости:** filesystem путей активных plugins/themes, JSON schema по соглашению, page/query regex, jQuery selectors, filter names/priorities.
- **Публичные hooks/functions:** `qtranslate_admin_config`, `qtranslate_front_config`, `qtranslate_admin_page_config`; config keys `pages`, `anchors`, `forms.fields`, `filters.text/term/url`, `encode`, `jquery`, `attrs`.
- **Риски:** формальной JSON Schema и schema version validation нет; custom config и список файлов поступают из options/admin input; regex/selectors/filter names являются исполняемой конфигурацией; чтение произвольных локальных путей необходимо ограничить approved roots; companion JS расширяет supply-chain поверхность; ошибки merge могут глобально изменить filters; конфигурация зависит от URL query и DOM.
- **Приоритет:** **P0** path/shape/size allowlist; **P1** versioned JSON Schema, deterministic compiler/cache и validation UI.

## 13. JavaScript-архитектура

- **Файлы:** `js/core/{config,hooks,multi-lang,pages,support}`, `js/acf`, `js/block-editor.js`, `js/options.js`, `js/notices.js`, `webpack.config.js`, `dist/*.js`.
- **Назначение:** предоставить global library `qTranx`, управлять language switching/content/display hooks, интегрировать admin pages, ACF и block editor.
- **Порядок:** Webpack entries → global `qTranx`; config читает `window.qTranslateConfig`; page modules подписываются на `qtranx.load`; loader вызывает init и `wp.hooks.doAction('qtranx.load')`; handlers связывают DOM/TinyMCE и localStorage; переключение вызывает `qtranx.languageSwitch`.
- **Зависимости:** globals `window.qTranslateConfig`, `qTranx`, `wp.hooks`, `wp.data`, `wp.apiFetch`, jQuery, TinyMCE, `switchEditors`, legacy widget globals.
- **Публичные API/hooks:** exports `qTranx.config`, `qTranx.ml`, `qTranx.hooks`; actions `qtranx.load`, `qtranx.languageSwitch`; множество legacy methods с `wp.deprecated`.
- **Риски:** global mutable state; PHP и JS parser duplication; DOM registry может удерживать stale nodes; deprecated API слой велик; optional chaining/Babel target не документирован; no tests/types/lint/source maps policy; committed dist нельзя автоматически сверить; block editor middleware строит endpoint строковым сравнением.
- **Приоритет:** **P1** parser/tests и explicit public API; **P2** TypeScript/JSDoc types, eslint, dependency extraction через `@wordpress/*`, bundle verification.

## 14. Gutenberg

- **Файлы:** `src/admin/block_editor.php`, `js/block-editor.js`, `dist/block-editor.js`, `src/admin/admin_notices.php`, `src/admin/admin.php`.
- **Назначение:** поддержать только single-language mode: сервер отдаёт raw выбранного языка, клиент маркирует update, сервер склеивает его с другими переводами.
- **Порядок:** class создаётся при admin bootstrap → REST filters/assets → apiFetch middleware добавляет язык → before callback заменяет поля ML-строками → core сохраняет → after callback снова выбирает один язык.
- **Зависимости:** Gutenberg post store/API shape, REST post controllers и legacy DB content.
- **Публичные hooks/functions:** `qtranslate_admin_block_editor`; `QTX_Admin_Block_Editor` methods; payload field `qtx_editor_lang`.
- **Риски:** single-language limitation; нет UI переключения — язык берётся из request context; race/lost updates; global REST filters; block widgets editor полностью отключается двумя filters для всего сайта; reusable blocks, templates, entities, custom meta и revisions не покрыты.
- **Приоритет:** **P0** data integrity; **P1** entity-aware integration и UI language state; **P2** убрать глобальное отключение widgets editor.

## 15. Устаревшие PHP/WordPress API

- **Файлы:** `src/deprecated.php`, `src/date_time.php`, `src/hooks.php`, `src/admin/*`, JS deprecated facade.
- **Назначение:** совместимость с qTranslate/qTranslate-X и прежними интеграционными именами.
- **Порядок:** `deprecated.php` подключается всегда в базовом bootstrap; compatibility functions дополнительно включаются настройкой; deprecated hooks вызываются рядом с новыми.
- **Зависимости/API:** фактический `strftime()` остаётся в `qtranxf_strftime`; режимы strftime; `wp_title` и RSS title hooks; `FILTER_SANITIZE_STRING`; `$wpdb->escape_by_ref`; legacy `wp_translator`, `i18n_*`, camelCase qTranslate hooks; JS wrappers через `wp.deprecated`.
- **Публичные функции/хуки:** весь `qtrans_*` compatibility surface, перечисленные deprecated qTranslate actions/filters, legacy date functions.
- **Риски:** PHP 8.1+ deprecations; удалённые/изменённые WordPress hooks; постоянная загрузка compatibility code; двойной вызов старого/нового hook усложняет причинность; заявленный PHP >=7.4 не задаёт проверенный верхний предел.
- **Приоритет:** **P1** для реально вызываемых deprecated PHP APIs, **P2** для двухэтапного удаления compatibility facade с telemetry/deprecation guide.

## 16. Security-sensitive области

- **Файлы:** `language_detect.php`, `url.php`, `init.php`, admin AJAX/settings/import/migrations, `module_loader.php`, i18n-config loader, `block_editor.php`, slugs SQL, ACF AJAX.
- **Назначение:** границы HTTP input, authorization, запись options/DB, filesystem include/read, redirects и REST mutation.
- **Порядок/зависимости:** входные данные принимаются ещё в `plugins_loaded(2)`; затем влияют на язык, redirect, фильтры, конфиг и сохранение.
- **Публичные поверхности:** cookies/query/path/host/referer; admin forms/AJAX; REST `qtx_editor_lang`; options/import JSON; module states; activation migrations.
- **Риски и рекомендации:**
  - **P0:** `wp_ajax_qtranslate_admin_notice` — добавить `check_ajax_referer` и `manage_options` (либо более узкую capability).
  - **P0:** module id из `qtranslate_modules_state` — принимать только статический registry id, сравнивать `realpath` с `src/modules`, не доверять option.
  - **P0:** REST — валидировать enabled language, post id/type, authorization/result и JSON; ограничить filter нужным route; защищать от lost update.
  - **P0:** redirects — allowlisted host, normalized path, `wp_safe_redirect`, нейтральный статус до доказанной каноничности.
  - **P0:** i18n-config — approved roots/extensions/size/schema; не позволять options задавать произвольный readable path.
  - **P0:** прямой SQL миграций/slugs — динамические identifiers/IN/LIKE пересобрать через строгие allowlists/placeholders; предусмотреть транзакционность/backup/dry-run.
  - **P1:** не сохранять raw `$_POST` в debug config; redaction по allowlist, а не исключениям.
  - **P1:** провести контекстный escaping audit всех admin HTML/attributes/URLs и output из config/notices.
  - **P1:** отказаться от временного глобального option `qtranslate_term_name`, который допускает межзапросное смешение данных.
  - **P1:** нормализовать cookie/query language до использования и установить единые Secure/HttpOnly/SameSite/domain/path правила.

## Карта зависимостей и рекомендуемая очередность

```text
qtranslate.php
  -> init/options
  -> request language + URL context
  -> parser + common hooks
  -> frontend filters | admin filters + JS + Gutenberg REST
  -> active module registry
       -> ACF / SEO / Forms / Slugs / WooCommerce / Jetpack / Site Kit
```

Рекомендуемый план модернизации без смены формата данных:

1. **Security patch set (P0):** AJAX, module registry, REST validation/concurrency, redirect, config paths/schema, SQL review.
2. **Characterisation tests:** parser PHP↔JS corpus, URL-mode matrix, filter snapshots, REST round-trip, settings/migrations.
3. **Core seams:** `ConfigRepository`, `LanguageContext`, `MultilingualParser`, `UrlLocalizer`, `ModuleRegistry`; legacy functions становятся тонкими facade.
4. **Scoped integrations:** контекстные frontend/admin filters, REST route-aware Gutenberg adapter, versioned module contracts.
5. **Compatibility cleanup:** deprecated APIs и DOM/TinyMCE зависимости удаляются только после опубликованного migration guide.

## Итоговая оценка

Главное достоинство проекта — чёткий и давно используемый формат хранения и богатая система интеграционных hooks/config. Главный архитектурный долг — отсутствие границ между request detection, глобальной конфигурацией, переводом данных и WordPress hooks. Модернизацию следует начинать не с переписывания UI или смены хранения, а с защиты входных границ и фиксации поведения тестами. После этого процедурный API можно постепенно оставить как совместимый facade над тестируемыми сервисами.

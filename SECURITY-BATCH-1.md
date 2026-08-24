# Security Batch 1 — QTX-SEC-001

Дата: 2026-08-21
Область изменений: только `QTX-SEC-001` (admin CSRF). Другие findings из `SECURITY-AUDIT.md` не исправлялись.

## Root cause

Страница `/wp-admin/options-general.php?page=qtranslate-xt` вызывала `qtranxf_edit_config()` для каждого просмотра. `qtranxf_verify_nonce()` считает запрос допустимым, если `$_POST` пуст, а изменяющие действия `convert`, `markdefault`, `delete`, `enable`, `disable`, `moveup`, `movedown` читались из `$_GET`. В результате cross-site top-level GET в сессии пользователя с `manage_options` достигал SQL updates или сохранения языковых options без nonce.

До исправления путь был таким:

`GET options-general.php?page=qtranslate-xt&<action>=...`
→ `qtranxf_admin_init()`
→ внешняя проверка `manage_options`
→ `qtranxf_edit_config()`
→ nonce fail-open при пустом POST
→ SQL / изменение `$q_config`
→ `qtranxf_save_config()` для language actions.

## Files changed

- `src/admin/admin_options_update.php` — server-side authorization, POST-only action dispatch, nonce path, sanitization и validation.
- `src/admin/admin_settings.php` — move/disable UI переведён с GET links на POST submit-buttons; добавлена минимальная nonced action form; изменяемый output экранирован.
- `src/admin/admin_settings_language_list.php` — enable/disable/delete/reset UI переведён на POST submit-buttons; URLs/attributes экранированы.
- `src/admin/import_export.php` — массовые `convert` и `markdefault` переведены с GET links на POST submit-buttons.
- `SECURITY-BATCH-1.md` — настоящий отчёт; production behavior не содержит иных изменений.

Option names, таблицы, SQL-преобразования, формат данных и qTranslate inline markers не изменены.

## Before / after behavior

### До

- Изменяющие actions принимались через GET.
- GET не требовал nonce из-за `empty($_POST)` в общем helper.
- Capability проверялась только внешним caller `qtranxf_admin_init()`.
- Language action parameters проходили `sanitize_text_field()`, но не все имели явную синтаксическую validation в dispatcher.
- `convert` и `markdefault` могли изменить много posts/pages по одному внешнему GET.

### После

- Все семь изменяющих actions принимаются только через `$_POST`.
- Actions из основных tabs отправляются отдельной минимальной POST-формой, а таблица списка языков — своей POST-формой; обе выводят `wp_nonce_field('qtranslate-x_configuration_form')` и не отправляют несвязанные settings fields.
- `qtranxf_edit_config()` явно требует `current_user_can('manage_options')`; `is_admin()` не используется как authorization.
- Любой action POST проходит существующий server-side `check_admin_referer()` через `qtranxf_verify_nonce()`.
- Разрешён ровно один state-changing action на запрос.
- `convert`/`markdefault` принимают только scalar value `1`.
- Language values должны быть scalar strings и соответствовать `QTX_LANG_CODE_FORMAT`; сохранена поддержка legacy двухбуквенных uppercase codes.
- Action submit не отправляет остальные settings fields и не вызывает побочно общий settings update либо сторонние реакции на эти fields.
- Read-only `edit=<lang>` остаётся GET, как и раньше.
- Старые GET URLs с `convert`, `markdefault`, `delete`, `enable`, `disable`, `moveup`, `movedown` больше не выполняют изменений.

## Security improvement

Для изменения состояния теперь одновременно требуются:

1. аутентифицированная WordPress-сессия;
2. capability `manage_options`;
3. POST request;
4. валидный nonce `qtranslate-x_configuration_form`;
5. ровно один известный action;
6. валидированный action value/language code.

Cross-site top-level GET, включая случай с `SameSite=Lax`, больше не достигает dangerous sink. Capability проверяется непосредственно в write handler, поэтому безопасность не зависит только от порядка внешних callers.

## Compatibility impact

- Штатный admin UI сохраняет те же действия и использует ту же configuration form. Изменяются только HTML controls: links стали submit-buttons.
- Старые bookmarks, вручную сформированные URLs и сторонний код, вызывающий изменяющие GET actions, перестанут работать. Это намеренное несовместимое поведение, необходимое для устранения CSRF; таким callers нужно отправлять POST с nonce и `manage_options` session.
- `edit` остаётся ссылкой и не затронут.
- DB operations, сообщения, option names, language ordering и multilingual storage format сохранены.
- Тексты двух legacy database actions переформулированы, потому что переводимые строки содержали literal `<a href>`; новые строки выводятся через escaping и используют отдельные submit-buttons. Existing translations для старых двух предложений не будут применены до обновления language catalogs.
- CSS class `button-link` использует штатное WordPress оформление кнопки как ссылки; функциональность не зависит от JavaScript.

## Tests performed

- `git diff --check` — успешно, whitespace errors не найдены.
- Полный поиск server-side callers — изменяющих обращений к `$_GET` для семи actions не осталось.
- Полный поиск UI callers — все найденные `moveup`, `movedown`, `enable`, `disable`, `delete`, `convert`, `markdefault` теперь являются POST submit controls; read-only `edit` остаётся GET.
- Проверено, что минимальная action form и отдельная форма списка языков содержат `wp_nonce_field('qtranslate-x_configuration_form')`, а handler вызывает `qtranxf_verify_nonce()` при любом непустом POST.
- Проверено, что действие из общей формы не запускает `qtranxf_update_settings()` благодаря `$state_action_requested`.
- `npm test` запущен; завершается кодом 1, потому что repository script является намеренной заглушкой: `Error: no test specified`.
- PHPUnit/phpunit configuration в репозитории отсутствует; добавить интеграционный WordPress regression test в существующую инфраструктуру невозможно.
- PHP CLI в рабочем окружении отсутствует (`where.exe php` не нашёл executable), поэтому `php -l` выполнить невозможно. Синтаксис проверен просмотром diff, но это не заменяет PHP lint; перед release требуется запустить `php -l` для четырёх изменённых PHP-файлов в PHP 7.4+ окружении.

Рекомендуемые автоматические regression cases при появлении WordPress test suite:

- GET для каждого из семи actions не меняет posts/options;
- POST без nonce и с неверным nonce отклоняется;
- subscriber/editor с валидным nonce получает 403;
- administrator + valid nonce выполняет ровно выбранное действие;
- array/malformed language, неизвестный code и несколько actions не меняют состояние;
- `convert`/`markdefault` принимают только `1`;
- enable/disable/delete/order сохраняют прежний результат и сообщения;
- обычный Save Changes по-прежнему вызывает settings update, а action button — нет.

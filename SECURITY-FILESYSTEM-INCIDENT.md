# QTX 4 Filesystem Incident Investigation

Date: 2026-08-25

Audited branch: `modernisation`

Audited commit: `0d83d0b005965d82b8908be846787a8704f0f0b6`

Audit type: source-level forensic review; no production changes

## Executive verdict

**Can the audited QTX 4 code copy, clone, reinstall, restore, download, unpack, or update itself? No.**

The audited repository has no runtime path that creates a WordPress plugin
directory, copies PHP plugin files, downloads a QTX package, unpacks an archive,
or invokes a plugin upgrader. It also registers no WP-Cron event and no Action
Scheduler job. In particular, it contains no mechanism with a 30-35 minute
interval.

This verdict is scoped to the repository tree at the commit above. It does not
prove that the code deployed on the affected server is byte-identical, or that
another plugin, MU plugin, theme, drop-in, compromised account, hosting agent,
backup job, deployment process, or malicious PHP file did not recreate QTX.

The supplied Linux audit record proves that, on 2026-08-24 at 16:38:47, a
`php-fpm8.2` process successfully issued `unlink(2)`/syscall 87 for:

`/var/www/avantihome/data/www/avantihome.lv/wp-content/plugins/qtranslate-xt/qtranslate.php`

Its working directory was `/var/www/avantihome/data/www/avantihome.lv/wp-admin`.
That identifies a web PHP worker as the deleting process, but the abbreviated
record does not identify the request, loaded script, call stack, parent process,
authenticated user, or code responsible. The QTX source reviewed here has no
call capable of deleting that file.

## Scope and evidence limitations

Reviewed evidence:

- all tracked QTX PHP runtime source at `0d83d0b`;
- development and test PHP filesystem calls;
- activation, deactivation, uninstall, update, module-loader, cron, remote-fetch,
  archive, command-execution, and obfuscation indicators;
- working-tree provenance and untracked executable files;
- the supplied audit event and reported 30-35 minute recurrence.

Unavailable evidence:

- no production WordPress filesystem or forensic image;
- no production database, `cron` option, Action Scheduler tables, or plugin list;
- no duplicate QTX directories or hashes;
- no web access/error logs, PHP-FPM logs, full audit event serial, process tree,
  shell history, control-panel jobs, crontabs, systemd timers, or backup logs;
- no local WordPress installation in this workspace (`wp-config.php` was not
  found), and no duplicate `qtranslate*` directory was found here.

Consequently, a WordPress-wide malware attribution and byte-for-byte comparison
of the recurring copies cannot be completed from the supplied evidence. The
actual producer must be identified on the affected host before remediation.

## Complete QTX filesystem write inventory

### Production runtime

| Source | Trigger and target | Operation | Can recreate or alter the QTX plugin? |
|---|---|---|---|
| `src/admin/activation_hook.php:702-713` | Plugin activation; fixed target `WP_CONTENT_DIR/debug-qtranslate.log` | Truncates the debug log with `fopen` + `ftruncate` when `WP_DEBUG` is enabled, otherwise deletes that one fixed log with `unlink` | **No.** Target is fixed outside the plugin directory. This is the only runtime `unlink` unrelated to generated SQL/config-log files. |
| `src/admin/update_gettext_db.php:12-83` | Admin request when automatic language updates are enabled, explicit settings action, or enabling a language; target `WP_LANG_DIR` | Creates `WP_LANG_DIR` if absent and invokes WordPress `Language_Pack_Upgrader` with `type = 'core'` | **No.** It installs WordPress core translation packs, not plugins. There is no `Plugin_Upgrader`. |
| `src/admin/admin_options_update.php:355-360` | Saving settings with automatic language updates enabled; fixed target `WP_LANG_DIR/qtranslate.test` | Creates/appends and immediately deletes a write-test file | **No.** Fixed language-directory test file only. |
| `src/utils.php:351-363, 507-573` | Optional diagnostic mode only if external configuration has populated `$q_config['i18n-log-dir']`; target `i18n-config-*.json` under that directory | `mkdir`, `fopen`, `fwrite`, and deletion of an empty generated config log | **No QTX self-copy logic.** The repository itself never assigns `i18n-log-dir`; the filenames are fixed JSON diagnostic names, not PHP/plugin packages. If this external debug key is used in production, its configured path must nevertheless be inspected. |
| `src/admin/admin_utils_db.php:3-48, 317-432` | Administrator-only, nonce-protected `db_split` settings action; readable local `.sql` input must pass `LocalSqlFilePolicy` and be inside canonical `ABSPATH` or `WP_CONTENT_DIR` (or an explicit filter-added root) | Writes, copies, appends, and sometimes removes generated language-specific `.sql` files adjacent to the approved input dump | **No.** Output extensions are constructed as `.sql`; it does not create directories or PHP files. This is the repository's only direct `copy()` call. |
| `src/modules/slugs/admin.php:23-111` | Slugs module activation/deactivation/uninstall | Calls WordPress `flush_rewrite_rules()`; on Apache WordPress core may rewrite `.htaccess` | **No.** QTX performs no direct filesystem call here, and the indirect core target is rewrite configuration, not a plugin directory. |

Important read-only references that may look relevant but do not write:

- `src/admin/activation_hook.php:778-781` only checks whether legacy qTranslate
  fork entry files exist.
- `src/admin/activation_hook.php:492-650` only discovers and reads local
  `i18n-config.json` files.
- `src/admin/import_export.php:127` only checks whether compatible plugins exist;
  its functions named `copy` copy WordPress option values in the database.
- `src/admin/admin.php:804-820` only reads the normal WordPress plugin-update
  transient and renders a link to WordPress's update screen.
- `src/Core/Integration/BuiltinModuleProvider.php` has a dynamic `require_once`,
  but `src/modules/module_loader.php` supplies only canonical `loader.php` paths
  from the hard-coded built-in module registry after `realpath` containment
  under `QTRANSLATE_DIR/src/modules`. Stored option keys cannot construct paths.

### Development and tests (not WordPress runtime)

| Source | Filesystem behavior |
|---|---|
| `dev/xml2po.php:247,261` | Development XML-to-PO converter creates an output directory and writes `language-<lang>.po`. It is not loaded by `qtranslate.php` or runtime source. |
| `tests/Unit/I18nConfigFilePolicyTest.php` | Creates and deletes temporary JSON/PHP policy fixtures during tests. |
| `tests/Unit/LocalSqlFilePolicyTest.php` | Creates, renames, and deletes temporary SQL/JSON policy fixtures during tests. |

No other direct production PHP filesystem write primitives were found. The
review covered `file_put_contents`, `fopen` write modes, `fwrite`/`fputs`,
`unlink`, `mkdir`, `rmdir`, `rename`, `copy`, `touch`, `chmod`, `chown`, links,
uploads, temp files, archive extraction, and WordPress filesystem APIs.

## Cron and periodic execution inventory

QTX registers **zero cron hooks**. No runtime uses of the following were found:

- `wp_schedule_event`, `wp_schedule_single_event`, `wp_next_scheduled`, or
  `wp_clear_scheduled_hook`;
- the `cron_schedules` filter;
- Action Scheduler scheduling APIs;
- a custom loop, sleep, timer, or 30-35 minute interval.

Cron-related references are observational only:

- `wp_doing_cron()` prevents redirects and records debug request context;
- the WordPress `cron` option is explicitly excluded from QTX option
  translation so QTX does not corrupt scheduled events;
- a comment mentions `wp-cron.php` behavior.

`qtranslate_next_update_mo` is not a scheduled event. It is a seven-day
timestamp gate checked during an admin request before updating WordPress core
language packs. `qtranslate_next_thanks` controls an admin notice over much
longer/random intervals. Neither can explain 30-35 minute directory creation.

## Activation, update, restore, and remote transport review

Activation and lifecycle hooks:

- `qtranslate.php:68-70` loads and registers QTX activation hooks in admin/WP-CLI
  context.
- `src/admin/activation_hook.php:716-843` registers activation and deactivation.
  Activation initializes options/module state, reads integration configuration,
  clears the fixed debug log, and detects/deactivates incompatible forks. It does
  not copy, download, unpack, or create plugin files.
- `src/modules/slugs/admin.php:23-24` registers deactivation/uninstall callbacks
  that update database metadata/options and rewrite rules only.
- QTX exposes `qtranslate_activation_hook`, `qtranslate_deactivation_hook`, and
  other WordPress action extension points. They do nothing by themselves. Code
  in a different plugin could attach arbitrary callbacks, so production hook
  registrations still need inspection; no QTX callback attached to these hooks
  performs self-restore.

Updater and transport results:

- no custom QTX updater;
- no `Plugin_Upgrader`, plugin installation API, plugin update-transient filter,
  package URL, self-update endpoint, or GitHub release downloader;
- no `WP_Filesystem`, `download_url`, `unzip_file`, `ZipArchive`, cURL, or
  `wp_remote_get`/`wp_remote_post` runtime call;
- only WordPress `Language_Pack_Upgrader` for **core translations** exists.

Therefore QTX cannot reinstall itself through its audited activation or update
code. Normal WordPress core/plugin update machinery or a third-party management
agent remains outside this conclusion and must be examined on production.

## Malicious or foreign-code review

No runtime hits were found for common PHP webshell/loader indicators including
`eval`, `base64_decode`, `gzinflate`, compressed/rotated payload decoders,
dynamic `assert`, deprecated `/e` replacement, `shell_exec`, `exec`, `system`,
`passthru`, `proc_open`, or remote/dynamic request-controlled includes.

Repository state further showed:

- no untracked PHP, JavaScript, or other executable source file;
- no working-tree source change relative to audited `HEAD`;
- the only pre-existing working-tree modifications were the three security and
  release Markdown documents `RELEASE-READINESS.md`, `SECURITY-REAUDIT.md`, and
  `SECURITY.md`;
- QTX 4 intentionally differs substantially from `origin/master` (modernisation
  core, integrations, tests, documentation, and CI). The new tracked PHP tree
  was included in this scan; no anomalous self-installer or encoded loader was
  identified.

This is evidence against malware inside the audited repository, not a malware
clearance for the deployed QTX directory or the rest of WordPress. A malicious
file injected only on production would not be present here.

At the time of this filesystem review, the separate QTX 4 security re-audit had
one open configuration-textarea finding (`QTX4-SEC-001`). It had no filesystem
write primitive and did not explain this incident. It was subsequently resolved
and regression-tested in `SECURITY-BATCH-QTX4-SEC-001.md` on 2026-09-02.

## Duplicate-copy analysis

No duplicate QTX directories or captured copies were supplied or found in the
workspace, so the following could not be determined:

- whether all recurring copies are byte-identical;
- whether timestamps, owners, modes, extended attributes, or injected files
  differ;
- whether copies match commit `0d83d0b`, a WordPress.org/GitHub package, a
  hosting backup, or a compromised template;
- whether every recurrence comes from the same producer.

Do not delete the next duplicate before preserving it. Collect a tar archive
with numeric owner, modes, nanosecond timestamps where supported, ACLs/xattrs,
and a sorted SHA-256 manifest. Compare manifests with `diff`; compare the entry
file and any anomalous file byte-for-byte with `cmp`/`sha256sum`.

## Most likely source of the repeated recreation

The audited QTX code is not the source. The 30-35 minute cadence is consistent
with an external periodic producer. Ranked hypotheses to test, not conclusions:

1. a compromised plugin, MU plugin, theme, drop-in, or injected PHP file reached
   through a web request or WordPress cron;
2. hosting control-panel/plugin manager, security scanner auto-repair, backup
   restore, filesystem synchronization, or deployment agent;
3. WordPress plugin update/recovery initiated by core or an authenticated/API
   management account;
4. system/user cron, systemd timer, queue worker, or remote orchestration;
5. persistent unauthorized administrator/application-password credentials or a
   stolen authenticated session.

The audit record's `php-fpm8.2` process makes a PHP/web-triggered path especially
important for the deletion event. It does not prove the recreating process is
the same process. Correlate both deletion and recreation timestamps independently.

## Immediate containment and evidence preservation

Perform these actions on the affected host before cleanup, under an incident
change record:

1. Restrict public access or place the site in an isolated maintenance network.
   Preserve a full filesystem snapshot, database dump, relevant logs, process
   state, scheduled-job definitions, and the next duplicate QTX directory first.
2. Expand the full audit event by serial number around the supplied record.
   Correlate PHP-FPM access/error logs, reverse-proxy logs, WordPress audit logs,
   and authentication events at the deletion and recreation times.
3. Inventory WordPress core, ordinary plugins, MU plugins, themes, drop-ins, and
   PHP under uploads/cache/temp directories. Verify WordPress core checksums and
   compare every extension against a separately obtained known-good package.
4. Inspect the database `cron` option, Action Scheduler tables/actions, active
   plugins, recently changed options, administrator users, application
   passwords, sessions, and unexpected autoloaded payloads.
5. Inspect root and service-user crontabs, `/etc/cron*`, systemd timers, hosting
   panel jobs, backup/restore agents, malware scanners, deployment hooks, and
   synchronization services. Search specifically for the observed 30-35 minute
   cadence and QTX paths/names.
6. After evidence capture, revoke WordPress sessions and application passwords;
   rotate WordPress admin, hosting panel, SSH/SFTP, deployment, database, and API
   credentials; replace WordPress salts. Do this from a trusted workstation.
7. Prevent PHP execution in upload/cache directories where the application does
   not require it and apply least-privilege ownership. Do not make the whole
   WordPress tree immutable before identifying required core update/upload paths.

Do **not** implement periodic deletion, broad write blocking, or QTX code changes
to hide the recurrence. Those actions destroy evidence or mask persistence.

## Exact remediation decision

**QTX production code change for this filesystem incident: none.** There is no
QTX self-copy/self-update path to remove, and altering QTX would not remove the
producer.

The exact host fix depends on the producer established from evidence:

- If malicious WordPress/PHP code is confirmed, take the host out of service,
  rebuild WordPress core and every plugin/theme from independently verified
  packages, remove unauthorized persistence, restore content/database only from
  a verified clean point, rotate all secrets, patch the initial access vector,
  and monitor a clean rebuild rather than trusting in-place cleanup.
- If a hosting/backup/deployment/security agent is confirmed, disable or correct
  that specific restore/repair policy and its stored artifact, then redeploy the
  intended verified QTX build.
- If WordPress update/recovery is confirmed, identify the initiating user/job and
  package source, correct the update policy/credentials, and verify package
  integrity before re-enabling it.

Release and production deployment remain blocked until the production-wide
forensic gap is closed and the producer is identified. The separate
`QTX4-SEC-001` release finding was resolved on 2026-09-02 and does not change
this incident-response requirement.

## Local validation record

- static filesystem, scheduler, updater, remote transport, include, command
  execution, and obfuscation searches: completed;
- JavaScript parser/security tests: passed (`1` file, `2` subtests);
- `git diff --check`: passed (line-ending conversion warnings only);
- PHP lint/PHPUnit: not executable locally because PHP and PHPUnit are not
  installed in this environment; no result is inferred from their absence.

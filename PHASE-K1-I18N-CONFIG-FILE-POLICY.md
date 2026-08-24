# Phase K1 — i18n-config file policy

## Root cause and objective

The `qtranslate_config_files` option previously authorized filesystem paths by
itself. Absolute paths and traversal could reach `file_get_contents()` without
an extension, canonical-root or size boundary. QTX-SEC-003 was validated as LOW
hardening rather than a privilege-crossing vulnerability, but the architecture
violated the rule that options contain state rather than filesystem authority.

## Final file boundary

`I18nConfigFilePolicy` resolves configured entries under canonical approved
roots. Defaults are the qTranslate-XT plugin directory and `WP_CONTENT_DIR`,
which retain built-in, plugin, mu-plugin and theme configurations. Trusted PHP
may add an intentional external root through `qtranslate_i18n_config_roots`;
the option alone cannot do so.

Before reading, the policy requires:

- a literal `.json` suffix;
- no URI/stream-wrapper scheme;
- an existing regular file;
- `realpath()` remaining beneath an approved canonical root (including symlink
  resolution and Windows case-insensitive comparison);
- a default maximum size of 1 MiB, adjustable by trusted PHP through
  `qtranslate_i18n_config_maximum_bytes`.

The bounded reader checks the limit again. Decoded configuration must contain
an array-valued `admin-config` or `front-config`. Files without
`schema-version` remain accepted in compatibility mode; an explicit version is
currently restricted to `1`. Unknown top-level legacy metadata remains ignored
as before.

## Compatibility impact

Normal `./i18n-config.json`, bundled compatibility files and configs in active
plugins/themes remain within the default roots. Existing absolute files outside
the plugin and `WP_CONTENT_DIR` are ignored and generate the existing admin
diagnostic until trusted site code registers their parent root. No option name,
database structure or configuration merge format changed.

The packaged default config is exercised directly and remains readable without
a schema migration.

## Tests

Coverage includes valid plugin/content-relative paths, traversal, outside
absolute paths, wrappers, wrong extensions, size limits, malformed schema,
legacy schema, version 1, unsupported versions and the real packaged default.

PHP 8.1, 8.2, 8.3 and 8.4 each pass 273 tests and 7698 assertions with zero
failures. QTX-SEC-003 is remediated as a filesystem-boundary hardening item.

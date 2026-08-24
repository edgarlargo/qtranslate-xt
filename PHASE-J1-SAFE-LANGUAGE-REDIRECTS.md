# Phase J1 — Safe language redirects

## Security objective

Close the plugin-side portion of validated finding QTX-SEC-007. Previously a
request-derived Host could survive URL conversion in query/path modes and reach
`wp_redirect()`. Practical exploitation required a permissive virtual host or
misconfigured proxy/cache, but the redirect sink did not enforce an origin
boundary.

## Final data flow

Request and configured language select a target URL; the existing
`qtranslate_language_detect_redirect` filter may adjust it. Immediately before
the sink, WordPress `wp_safe_redirect()` validates the target against an
allowlist containing only:

- WordPress `home_url()` host;
- WordPress `site_url()` host;
- `network_home_url()` host in multisite;
- explicitly configured qTranslate per-language domain hosts; and
- hosts independently added by trusted PHP through WordPress' standard
  `allowed_redirect_hosts` filter.

Candidates are parsed, lower-cased, trailing-dot normalized and required to be a
valid DNS hostname or IP address. Malformed strings, arrays and CR/LF input are
ignored. The qTranslate allowlist callback exists only for the duration of the
redirect call. If WordPress refuses the redirect, qTranslate does not exit and
records cancellation in request diagnostics.

## Compatibility

- Query, path, subdomain and per-domain modes retain their target construction.
- Configured cross-domain language switching remains supported.
- Multisite network hosts are explicitly supported.
- Redirect status remains 301 and the public redirect filter remains available.
- Unknown off-domain targets returned by request poisoning or a filter are no
  longer accepted unless trusted PHP explicitly registers the host through the
  WordPress allowlist.

Reverse proxies must still be configured to pass a canonical Host. The new sink
prevents off-domain redirects even when that infrastructure policy is weak, but
it cannot correct all upstream cache-key or virtual-host mistakes.

## Tests

Regression tests cover home/site/configured hosts, case normalization,
multisite, malformed/non-string domain entries, absence of attacker hosts and
the scoped `wp_safe_redirect()` call.

PHP 8.1, 8.2, 8.3 and 8.4 each pass 268 tests and 7682 assertions with zero
failures. QTX-SEC-007 is remediated at the plugin redirect boundary; real reverse
proxy and multisite integration fixtures remain part of the release matrix.

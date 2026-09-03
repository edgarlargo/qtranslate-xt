# Security policy

## Supported development state

This repository is undergoing QTX 4 modernisation. The current branch is not a
published production release and receives no implied security-support window.
Report suspected vulnerabilities privately to the repository maintainers; do
not include production credentials, personal data or destructive proof of
concepts in a public issue.

## Security boundaries

- Multilingual parsing treats content as opaque text. HTML sanitization belongs
  to the WordPress field/write policy; contextual escaping belongs to output.
- Module options can enable only authoritative built-in module IDs and cannot
  provide filesystem paths.
- i18n JSON and SQL maintenance inputs use approved canonical roots.
- qTranslate-owned unserialization disables PHP class hydration.
- State-changing admin actions require explicit capabilities and nonces.
- Diagnostic and notice AJAX endpoints require both capability and endpoint nonce.
- Administrator textarea values are escaped at the final HTML sink; input
  sanitization is not treated as output escaping.
- Legacy ACF field renderers contextually escape attachment metadata,
  attributes and language labels, and protect editor textarea boundaries.
- Privileged REST editor data remains behind native route/object permissions;
  stale language saves use revision conflicts.
- Language redirects use the scoped WordPress safe-host policy at every sink.
- Release CI uses immutable action/image revisions and verifies the committed
  JavaScript bundles from an audited lock graph.

Historical findings and exploitability validation are in
`SECURITY-AUDIT.md`, `SECURITY-VALIDATION.md`, and `SECURITY-REAUDIT.md`. The
post-Woo result is `FINAL-SECURITY-REAUDIT.md`. QTX4-SEC-001 and every
release-blocking finding discovered by the final audit are remediated and
regression-tested. The current runtime has no confirmed open Critical, High,
Medium or Low issue; the final security gate authorizes exact release-candidate
ZIP construction and validation, not publication without that package check.

## Reporting information

Include the exact commit, WordPress/PHP/plugin versions, enabled modules, URL
mode, required role/capability, request method/route and a non-destructive
reproduction. Never test against a site you do not own or have permission to
assess.

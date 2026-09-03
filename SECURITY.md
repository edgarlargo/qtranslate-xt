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
- Administrator textarea values are escaped at the final HTML sink; input
  sanitization is not treated as output escaping.
- Privileged REST editor data remains behind native route/object permissions;
  stale language saves use revision conflicts.

Historical findings and exploitability validation are in
`SECURITY-AUDIT.md`, `SECURITY-VALIDATION.md`, and the current-tree result in
`SECURITY-REAUDIT.md`. The full QTX 4 re-audit at commit `0d83d0b` found
QTX4-SEC-001 in the settings renderer. The finding was remediated and
regression-tested on 2026-09-02; the current runtime has no confirmed open
Critical, High or Medium issue. Required third-party integration and build/CI
supply-chain work remains incomplete, so this does not authorize a release.

## Reporting information

Include the exact commit, WordPress/PHP/plugin versions, enabled modules, URL
mode, required role/capability, request method/route and a non-destructive
reproduction. Never test against a site you do not own or have permission to
assess.

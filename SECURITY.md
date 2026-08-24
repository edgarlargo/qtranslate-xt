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
- Privileged REST editor data remains behind native route/object permissions;
  stale language saves use revision conflicts.

Historical findings and exploitability validation are in
`SECURITY-AUDIT.md`, `SECURITY-VALIDATION.md`, and the current-tree result in
`SECURITY-REAUDIT.md`. The current re-audit has no confirmed open Critical or
High issue, but required third-party integration tests are incomplete.

## Reporting information

Include the exact commit, WordPress/PHP/plugin versions, enabled modules, URL
mode, required role/capability, request method/route and a non-destructive
reproduction. Never test against a site you do not own or have permission to
assess.

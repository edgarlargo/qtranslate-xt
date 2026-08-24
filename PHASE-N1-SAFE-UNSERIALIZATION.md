# Phase N1 — Safe unserialization

## Finding

QTX-SEC-006 was validated as HARDENING ONLY: no low-privilege writer or local
gadget chain was demonstrated, but qTranslate added unrestricted object
hydration at metadata, migration and legacy ACF paths.

## Remediation

All qTranslate-owned `unserialize()` / `maybe_unserialize()` sinks now use
`qtranxf_maybe_unserialize_safe()`. It first retains WordPress serialized-value
detection and then calls PHP `unserialize()` with `allowed_classes => false`.
Plain values, booleans, scalars and arrays retain their prior shapes. Object
payloads, including objects nested in arrays, become `__PHP_Incomplete_Class`
without instantiating application classes or invoking magic methods.

The boundary covers frontend recursive translation/URL metadata, translated
metadata return values, legacy option/meta database conversion, import/export
and ACF generic/post-object formatting. No unrestricted production unserialize
sink remains.

## Compatibility risk

qTranslate-owned persisted formats are scalar/array based. A third-party value
that intentionally depended on qTranslate hydrating a live PHP object will now
receive an incomplete object. That behavior is intentionally unsupported at the
security boundary; WordPress/plugin code needing live objects must own and
authorize its own deserialization before passing safe data to qTranslate.

## Tests

Regression tests cover arrays, integers, serialized false, plain values, a
top-level class with `__wakeup`, and the same class nested in an array. Wakeup is
never invoked. PHP 8.1–8.4 each pass 282 tests and 7742 assertions.

QTX-SEC-006 is remediated as defence in depth; this does not retroactively claim
the original hardening item was an exploitable qTranslate RCE.

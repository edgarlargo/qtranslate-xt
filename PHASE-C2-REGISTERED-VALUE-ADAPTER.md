# Phase C2 — registered scalar value adapter

## Goal

Add a WordPress-independent translation path for explicitly registered scalar
storage values while keeping all production option/metadata filters in legacy
compatibility mode.

## Architecture

`RegisteredValueAdapter` requires a `FieldRegistry`, configured parser and
translation service. It translates only when the exact storage/key pair is
registered and the supplied value is a string. Arrays and objects are returned
by identity; serialized-looking strings are returned byte-for-byte and are
never deserialized or parsed as multilingual containers.

Selection uses explicit `LanguageRequest` and `LanguageContext`; the adapter
does not read WordPress globals, options, requests or the filesystem. HTML is
opaque content and remains outside sanitization/escaping policy.

## Files and compatibility

Created `src/Core/Storage/RegisteredValueAdapter.php`, added it to production and
test bootstraps, and added focused unit tests. No WordPress hook, public legacy
function, option/meta key, serialized value or database representation changed.

## Tests

PHP 8.1–8.4 each pass 207 tests and 7450 assertions with zero failures. Coverage
includes exact opt-in, wrong scope/key, plain strings, arrays, objects and a
serialized-looking multilingual container. `git diff --check` is green.

## Next step and rollback

C3 can construct a request-scoped registry/adapter at the WordPress boundary and
add opt-in callbacks while retaining broad legacy filters as compatibility mode.
Rollback removes the class includes/file/tests; no data rollback is required.

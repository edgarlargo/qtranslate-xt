# Phase M1 — Executable JavaScript parser tests

## Test architecture

The Node test runner executes the actual `js/core/multi-lang/parser.js` source in
an isolated VM with the same language configuration as the shared Phase A1
corpus. The test does not duplicate parser logic. It compares low-level tokens
and high-level language projections for all 27 PHP/JavaScript corpus cases,
including malformed markers, Unicode, HTML, script-looking opaque content and a
generated 64 KiB value.

JavaScript tests run with `npm test` / `npm run test:js`. A security assertion
also rejects dynamic execution and direct DOM HTML sinks in the parser source.

## Drift found and resolved

Native JavaScript `String.split()` produces empty implementation tokens; the
test ignores these when comparing PHP's exposed blocks. This is not semantic
content.

Four high-level cases differed because JavaScript retained boundary whitespace
introduced by neutral text while the characterized PHP split contract trims
multilingual projections. `parseTokens()` now trims each projected language only
after parsing multilingual token arrays. Plain one-token input, internal
whitespace, HTML, Unicode and markers remain unchanged.

Final shared corpus parity: **100% (27/27)**.

## Build

Webpack 5.102.0 production build passes and synchronizes `dist/core.js` and the
F5 `dist/modules/acf.js` bundle with source. Node 18.14.1 emits the previously
documented babel-loader engine warning; build and tests nevertheless pass. npm
reports development dependency audit findings which remain a toolchain upgrade
task rather than a runtime plugin dependency.

Remaining Phase M work includes executable language-switch/admin/Gutenberg DOM
tests and a supported Node/toolchain dependency refresh.

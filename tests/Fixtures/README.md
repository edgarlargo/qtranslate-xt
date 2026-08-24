# Multilingual characterization corpus

`multilingual-corpus.json` is the shared, runtime-neutral input corpus for the
legacy PHP parser and the JavaScript parser. It deliberately stores PHP
expectations under `expected_php`; JavaScript expectations can be added under
`expected_js` without changing the input cases when executable JavaScript
parity tests are introduced.

The corpus covers:

- square-bracket, HTML-comment and curly syntaxes;
- plain, empty, neutral, missing, duplicate and unknown-language content;
- malformed, nested-looking, adjacent and case-variant markers;
- whitespace, newlines, Latvian, Cyrillic and emoji content;
- HTML, quoted attributes, JSON-looking and serialized-looking payloads;
- script-looking input and a generated 64 KiB translation.

`round_trip.classification` has these meanings:

- `LOSSLESS`: the canonical join for the original syntax reproduces the input;
- `NORMALIZED`: split/join preserves translations but normalizes syntax,
  whitespace, neutral text, duplicate blocks or closing markers;
- `LEGACY-QUIRK`: the observed result loses or reinterprets input because of a
  documented legacy behavior.

Parser expectations intentionally preserve HTML and script-looking strings.
The parser selects language blocks; output sanitization remains a separate
responsibility.

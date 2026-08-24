# Phase H3 — route-scoped Gutenberg revision protocol

## Goal

Prevent stale single-language Gutenberg saves/autosaves from silently
overwriting newer multilingual raw content while retaining the existing editor
projection behavior.

## Protocol

Edit-context REST responses now include SHA-256 revisions for title, content and
excerpt in `qtx_editor_revisions`, alongside the existing
`qtx_editor_lang`. The block-editor middleware sends both values on post saves
and autosaves.

Before mutation, the server now:

1. accepts only POST/PUT carrying the QTX editor protocol;
2. verifies the request targets the exact registered `/wp/v2/{rest_base}/{id}`
   post route or its `/autosaves` child;
3. verifies the language is enabled;
4. requires a revision for each submitted multilingual field;
5. compares it with the current raw field using `hash_equals()`.

A mismatch or missing revision returns HTTP 409 and performs no merge. An
invalid language/request returns HTTP 400. Arbitrary REST routes carrying a
lookalike parameter are ignored and never mutated.

## Compatibility

The inline storage format and projected editor values are unchanged. A browser
tab with a pre-H3 cached bundle lacks revisions and will fail safely with 409;
reload obtains the new protocol. Normal saves and autosaves continue to use the
legacy-compatible join path, now protected by revisions. The H2 structured
registered-field adapter remains available for later controller-level adoption.

## Real WordPress validation

Executed on WordPress 7.1 / PHP 8.4.16:

- authenticated `context=edit` returned 64-character revisions;
- valid English save rebuilt
  `[:en]Conflict EN Saved[:de]Konflikt DE[:]`;
- replay of the stale revisions returned `qtx_editor_conflict`, HTTP 409, and
  left storage unchanged;
- autosave created a revision containing
  `[:en]Conflict EN Autosave[:de]Konflikt DE[:]` while parent content remained
  unchanged.

Unit/source contract and the production bundle cover the connected protocol.
Interactive two-browser UI messaging remains to be manually verified.

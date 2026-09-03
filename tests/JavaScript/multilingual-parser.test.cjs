'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..', '..');
const corpus = JSON.parse(fs.readFileSync(path.join(root, 'tests', 'Fixtures', 'multilingual-corpus.json'), 'utf8'));

function loadParser(configuration) {
    const languages = Object.fromEntries(configuration.enabled_languages.map((language) => [language, true]));
    const config = {languages, lang: {codeRegex: configuration.language_code_regex}};
    let source = fs.readFileSync(path.join(root, 'js', 'core', 'multi-lang', 'parser.js'), 'utf8');
    source = source
        .replace("import {config} from '../config';", '')
        .replaceAll('export const ', 'const ')
        .concat('\nmodule.exports = {splitLangs, splitTokens, parseTokens};\n');
    const module = {exports: {}};
    vm.runInNewContext(source, {config, module}, {filename: 'js/core/multi-lang/parser.js'});
    return module.exports;
}

const parser = loadParser(corpus.configuration);
const cases = corpus.cases.filter((entry) => entry.runtimes.includes('js'));

function rawValue(entry) {
    if (typeof entry.raw === 'string') return entry.raw;
    const generator = entry.raw_generator;
    return generator.prefix + generator.repeat.repeat(generator.count) + generator.suffix;
}

test('shared corpus token and split parity', () => {
    const mismatches = [];
    for (const entry of cases) {
        const raw = rawValue(entry);
        const tokens = Array.from(parser.splitTokens(raw)).filter((token) => token !== '');
        if (entry.expected_php.blocks && JSON.stringify(tokens) !== JSON.stringify(entry.expected_php.blocks)) {
            mismatches.push(entry.id + ':tokens');
        }
        const split = {...parser.splitLangs(raw)};
        if (entry.expected_php.split && JSON.stringify(split) !== JSON.stringify(entry.expected_php.split)) {
            mismatches.push(entry.id + ':split');
        }
        if (entry.expected_php.split_length) {
            for (const [language, length] of Object.entries(entry.expected_php.split_length)) {
                if (split[language].length !== length) mismatches.push(entry.id + ':length:' + language);
            }
        }
    }
    assert.deepStrictEqual(mismatches, []);
});

test('parser source contains no dynamic execution or DOM sink', () => {
    const source = fs.readFileSync(path.join(root, 'js', 'core', 'multi-lang', 'parser.js'), 'utf8');
    assert.doesNotMatch(source, /\beval\s*\(|\bFunction\s*\(|innerHTML|document\.write|\.html\s*\(/);
});

test('language switch labels use text-only DOM sinks', () => {
    const source = fs.readFileSync(path.join(root, 'js', 'core', 'hooks', 'handlers.js'), 'utf8');
    assert.match(source, /textContent:\s*lang_conf\.name/);
    assert.match(source, /textContent:\s*config\.l10n\.CopyFrom/);
    assert.doesNotMatch(source, /innerHTML:\s*(?:lang_conf\.name|config\.l10n\.CopyFrom)/);
});

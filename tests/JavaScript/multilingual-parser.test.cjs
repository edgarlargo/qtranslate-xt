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

function loadAcfBridgeValues() {
    let source = fs.readFileSync(path.join(root, 'js', 'acf', 'options-bridge-values.js'), 'utf8');
    source = source
        .replace("import {splitLangs} from '../core/multi-lang';", '')
        .replaceAll('export const ', 'const ')
        .concat('\nmodule.exports = {normalizeBridgeLanguages, parseBridgeValue, serializeBridgeValue};\n');
    const module = {exports: {}};
    vm.runInNewContext(source, {splitLangs: parser.splitLangs, module, Object, Array, String, RegExp}, {
        filename: 'js/acf/options-bridge-values.js',
    });
    return module.exports;
}

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

test('WooCommerce block strings select the active language without HTML sinks', () => {
    let source = fs.readFileSync(path.join(root, 'js', 'woocommerce-blocks', 'translator.js'), 'utf8');
    source = source
        .replaceAll('export const ', 'const ')
        .concat('\nmodule.exports = {selectTranslation};\n');
    const module = {exports: {}};
    vm.runInNewContext(source, {module, Object, Array, RegExp}, {filename: 'js/woocommerce-blocks/translator.js'});
    const settings = {
        language: 'ru',
        defaultLanguage: 'en',
        enabledLanguages: ['lv', 'ru', 'en'],
        languageCodePattern: '[a-z]{2,3}',
    };
    const select = module.exports.selectTranslation;

    assert.equal(select('[:en]Checkout[:lv]Pasūtījums[:ru]Оформление заказа[:]', settings), 'Оформление заказа');
    assert.equal(
        select('[:en]Set of 250 mockups[:lv]Viss 250 maketu komplekts[:ru]Весь набор 250 мокапов[:]', settings),
        'Весь набор 250 мокапов',
    );
    assert.equal(select('<!--:lv-->Summa<!--:ru-->Итого<!--:en-->Subtotal<!--:-->', settings), 'Итого');
    assert.equal(select('{:lv}Grozs{:en}Cart{:}', {...settings, language: 'de'}), 'Cart');
    assert.equal(select('€20.00', settings), '€20.00');

    const domSource = fs.readFileSync(path.join(root, 'js', 'woocommerce-blocks', 'index.js'), 'utf8');
    assert.doesNotMatch(domSource, /innerHTML|outerHTML|insertAdjacentHTML|document\.write/);
    assert.match(domSource, /node\.nodeValue = translated/);
    assert.match(domSource, /i18n\.gettext/);
});

test('ACF Options bridge uses isolated text-only panels and a named original field', () => {
    const source = fs.readFileSync(path.join(root, 'js', 'acf', 'options-bridge.js'), 'utf8');

    assert.match(source, /qTranslateModuleAcf\?\.show_language_tabs/);
    assert.match(source, /qtx-acf-options-original/);
    assert.match(source, /serializeBridgeValue\(values, languages\)/);
    assert.match(source, /document\.createElement\(fieldType === 'textarea'/);
    assert.match(source, /tab\.textContent = language\.toUpperCase\(\)/);
    assert.doesNotMatch(source, /innerHTML|outerHTML|insertAdjacentHTML|document\.write|active_plugins/);
});

test('ACF Options bridge round-trips configured and disabled language values', () => {
    const bridge = loadAcfBridgeValues();
    const languages = bridge.normalizeBridgeLanguages({lv: {}, ru: {}, en: {}, '<bad>': {}});
    assert.deepStrictEqual(Array.from(languages), ['lv', 'ru', 'en']);

    const plain = bridge.parseBridgeValue('Sākuma teksts', languages, 'lv');
    assert.equal(plain.lv, 'Sākuma teksts');
    assert.equal(plain.ru, '');
    assert.equal(plain.en, '');

    const values = bridge.parseBridgeValue('[:lv]Latviski[:ru]Русский[:en]English[:de]Deutsch[:]', languages, 'lv');
    assert.equal(values.lv, 'Latviski');
    assert.equal(values.ru, 'Русский');
    assert.equal(values.en, 'English');
    assert.equal(values.de, 'Deutsch');
    assert.equal(
        bridge.serializeBridgeValue(values, languages),
        '[:lv]Latviski[:ru]Русский[:en]English[:de]Deutsch[:]',
    );
});

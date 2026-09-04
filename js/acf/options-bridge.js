'use strict';

import {
    normalizeBridgeLanguages,
    parseBridgeValue,
    serializeBridgeValue,
} from './options-bridge-values';

const fieldMarker = 'data-qtx-acf-options-bridge';
const originalClass = 'qtx-acf-options-original';
const tabClass = 'qtx-acf-language-tab';
const panelClass = 'qtx-acf-language-panel';
const inputClass = 'qtx-acf-language-input';
const activeClass = 'is-active';
const copiedAttributes = [
    'autocomplete',
    'cols',
    'inputmode',
    'maxlength',
    'minlength',
    'placeholder',
    'rows',
    'spellcheck',
];

const copyPresentationAttributes = function (source, target) {
    copiedAttributes.forEach(function (attribute) {
        if (source.hasAttribute(attribute)) {
            target.setAttribute(attribute, source.getAttribute(attribute));
        }
    });
    target.disabled = source.disabled;
    target.readOnly = source.readOnly;
};

const setActivePanel = function (tabs, panels, language) {
    tabs.querySelectorAll('.' + tabClass).forEach(function (tab) {
        const active = tab.dataset.language === language;
        tab.classList.toggle(activeClass, active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
        tab.setAttribute('tabindex', active ? '0' : '-1');
    });
    panels.querySelectorAll('.' + panelClass).forEach(function (panel) {
        panel.hidden = panel.dataset.language !== language;
    });
};

/**
 * Enhance one standard ACF field. Returns true when the field is handled by
 * either this native bridge or the legacy standalone bridge, preventing two
 * editors from being attached to the same original input.
 */
export const attachSafeOptionsTabs = function (fieldElement, fieldType, selector) {
    if (!window.qTranslateModuleAcf?.show_language_tabs || !fieldElement?.length) {
        return false;
    }

    const field = fieldElement[0];
    if (!field || fieldElement.closest('.acf-clone').length) {
        return false;
    }
    if (field.qtxAcfOptionsBridgeAttached === true) {
        return true;
    }
    if (field.querySelector('.qtx-safe-wrap')) {
        return true;
    }
    if (fieldType !== 'text' && fieldType !== 'textarea') {
        return false;
    }

    const inputContainer = fieldElement.find('.acf-input').first()[0];
    // `input:text` is an intentional jQuery selector used by the legacy ACF
    // integration and is not valid for querySelector().
    const original = inputContainer ? fieldElement.find(selector).first()[0] : null;
    const languages = normalizeBridgeLanguages(qTranx.config.languages);
    if (!inputContainer || !original || original.classList.contains(inputClass) || !languages.length) {
        return false;
    }

    // ACF may clone an already rendered row without cloning its event
    // listeners. Remove generated UI from that clone and rebuild it from the
    // serialized original value instead of treating the copied marker as live.
    if (field.getAttribute(fieldMarker) === '1') {
        fieldElement.find('.qtx-acf-language-tabs, .qtx-acf-language-panels').remove();
        original.classList.remove(originalClass);
        original.removeAttribute('aria-hidden');
        original.removeAttribute('tabindex');
    }

    const defaultLanguage = qTranx.config.lang.default;
    const values = parseBridgeValue(original.value, languages, defaultLanguage);
    const tabs = document.createElement('div');
    const panels = document.createElement('div');
    const editors = Object.create(null);
    const baseId = original.id || ('qtx-acf-' + Math.random().toString(36).slice(2));

    tabs.className = 'qtx-acf-language-tabs';
    tabs.setAttribute('role', 'tablist');
    tabs.setAttribute('aria-label', 'qTranslate-XT languages');
    panels.className = 'qtx-acf-language-panels';

    const syncOriginal = function () {
        languages.forEach(function (language) {
            values[language] = editors[language].value;
        });
        original.value = serializeBridgeValue(values, languages);
        jQuery(original).trigger('input').trigger('change');
    };

    languages.forEach(function (language) {
        const languageConfig = qTranx.config.languages[language] || {};
        const panelId = baseId + '--qtx-' + language;
        const tab = document.createElement('button');
        const panel = document.createElement('div');
        const editor = document.createElement(fieldType === 'textarea' ? 'textarea' : 'input');

        tab.type = 'button';
        tab.className = tabClass;
        tab.dataset.language = language;
        tab.textContent = language.toUpperCase();
        tab.title = languageConfig.admin_name || languageConfig.name || language;
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-controls', panelId);

        panel.id = panelId;
        panel.className = panelClass;
        panel.dataset.language = language;
        panel.setAttribute('role', 'tabpanel');

        if (fieldType === 'text') {
            editor.type = 'text';
        }
        editor.className = inputClass;
        editor.dataset.language = language;
        editor.value = values[language] || '';
        editor.setAttribute('aria-label', tab.title);
        copyPresentationAttributes(original, editor);
        editor.addEventListener('input', syncOriginal);
        editor.addEventListener('change', syncOriginal);
        tab.addEventListener('click', function () {
            setActivePanel(tabs, panels, language);
            editor.focus();
        });

        editors[language] = editor;
        panel.appendChild(editor);
        tabs.appendChild(tab);
        panels.appendChild(panel);
    });

    original.classList.add(originalClass);
    original.setAttribute('tabindex', '-1');
    original.setAttribute('aria-hidden', 'true');
    inputContainer.insertBefore(tabs, original.nextSibling);
    inputContainer.insertBefore(panels, tabs.nextSibling);
    field.setAttribute(fieldMarker, '1');
    // The standalone 0.4 bridge uses this marker. Setting it makes coexistence
    // safe regardless of which script handles a dynamically appended field first.
    field.setAttribute('data-qtx-safe', '1');
    field.qtxAcfOptionsBridgeAttached = true;

    const activeLanguage = qTranx.hooks.getActiveLanguage();
    const initialLanguage = languages.indexOf(activeLanguage) >= 0
        ? activeLanguage
        : (languages.indexOf(defaultLanguage) >= 0 ? defaultLanguage : languages[0]);
    setActivePanel(tabs, panels, initialLanguage);

    return true;
};

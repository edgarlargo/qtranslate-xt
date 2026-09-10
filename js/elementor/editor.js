'use strict';

import {parseElementorValue, serializeElementorValue} from './values';

const settings = window.qtxElementorEditor;
const supportedControls = [
    '.elementor-control-type-text input[data-setting]',
    '.elementor-control-type-textarea textarea[data-setting]',
    '.elementor-control-type-wysiwyg textarea[data-setting]',
].join(',');
const attachedAttribute = 'data-qtx-elementor-attached';

const languageLabel = function (language) {
    return settings.languageNames?.[language] || language.toUpperCase();
};

const setActiveLanguage = function (bridge, language) {
    bridge.querySelectorAll('[data-qtx-language]').forEach(function (element) {
        const active = element.dataset.qtxLanguage === language;
        if (element.classList.contains('qtx-elementor-language-tab')) {
            element.classList.toggle('is-active', active);
            element.setAttribute('aria-selected', active ? 'true' : 'false');
            element.setAttribute('tabindex', active ? '0' : '-1');
        } else {
            element.hidden = !active;
        }
    });
};

const dispatchNativeUpdate = function (original, serialized) {
    original.value = serialized;
    original.dispatchEvent(new Event('input', {bubbles: true}));
    original.dispatchEvent(new Event('change', {bubbles: true}));
};

const attachControl = function (original) {
    if (!settings || !Array.isArray(settings.enabledLanguages) || !settings.enabledLanguages.length ||
        original.hasAttribute(attachedAttribute) || original.closest('.qtx-elementor-language-bridge')) {
        return;
    }

    const languages = settings.enabledLanguages;
    const values = parseElementorValue(original.value, languages, settings.defaultLanguage);
    const bridge = document.createElement('div');
    const tabs = document.createElement('div');
    const panels = document.createElement('div');
    const editors = Object.create(null);
    const isTextarea = original.tagName === 'TEXTAREA';

    bridge.className = 'qtx-elementor-language-bridge';
    tabs.className = 'qtx-elementor-language-tabs';
    tabs.setAttribute('role', 'tablist');
    tabs.setAttribute('aria-label', 'qTranslate-XT languages');
    panels.className = 'qtx-elementor-language-panels';

    const synchronize = function () {
        languages.forEach(function (language) {
            values[language] = editors[language].value;
        });
        dispatchNativeUpdate(original, serializeElementorValue(values, languages));
    };

    languages.forEach(function (language) {
        const tab = document.createElement('button');
        const panel = document.createElement('div');
        const editor = document.createElement(isTextarea ? 'textarea' : 'input');

        tab.type = 'button';
        tab.className = 'qtx-elementor-language-tab';
        tab.dataset.qtxLanguage = language;
        tab.textContent = language.toUpperCase();
        tab.title = languageLabel(language);
        tab.setAttribute('role', 'tab');

        panel.className = 'qtx-elementor-language-panel';
        panel.dataset.qtxLanguage = language;
        panel.setAttribute('role', 'tabpanel');

        if (!isTextarea) {
            editor.type = 'text';
        }
        editor.className = 'qtx-elementor-language-input';
        editor.value = values[language] || '';
        editor.setAttribute('aria-label', languageLabel(language));
        editor.addEventListener('input', synchronize);
        editor.addEventListener('change', synchronize);
        tab.addEventListener('click', function () {
            setActiveLanguage(bridge, language);
            editor.focus();
        });

        editors[language] = editor;
        panel.appendChild(editor);
        tabs.appendChild(tab);
        panels.appendChild(panel);
    });

    original.setAttribute(attachedAttribute, '1');
    original.classList.add('qtx-elementor-original-control');
    original.setAttribute('aria-hidden', 'true');
    original.setAttribute('tabindex', '-1');
    bridge.appendChild(tabs);
    bridge.appendChild(panels);
    original.insertAdjacentElement('afterend', bridge);

    const active = languages.includes(settings.language)
        ? settings.language
        : (languages.includes(settings.defaultLanguage) ? settings.defaultLanguage : languages[0]);
    setActiveLanguage(bridge, active);
};

const scanControls = function (root) {
    if (!root?.querySelectorAll) {
        return;
    }
    if (root.matches?.(supportedControls)) {
        attachControl(root);
    }
    root.querySelectorAll(supportedControls).forEach(attachControl);
};

const scheduleScan = function () {
    window.requestAnimationFrame(function () {
        scanControls(document.getElementById('elementor-panel') || document);
    });
};

const registerElementorHooks = function () {
    if (!window.elementor?.hooks?.addAction) {
        return;
    }
    ['widget', 'section', 'column', 'container'].forEach(function (elementType) {
        elementor.hooks.addAction('panel/open_editor/' + elementType, scheduleScan);
    });
};

registerElementorHooks();
scheduleScan();

const panel = document.getElementById('elementor-panel');
if (panel) {
    new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(scanControls);
        });
    }).observe(panel, {childList: true, subtree: true});
}

'use strict';

const tabContainerClass = 'qtx-acf-language-tabs';
const tabClass = 'qtx-acf-language-tab';
const activeClass = 'is-active';

const syncTabs = function (language) {
    document.querySelectorAll('.' + tabClass).forEach(function (tab) {
        const active = tab.dataset.language === language;
        tab.classList.toggle(activeClass, active);
        tab.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
};

/**
 * Add a text-only language switch beside a standard ACF input. The actual
 * multilingual storage remains owned by qTranslate-XT's normal content hook.
 */
export const attachSafeOptionsTabs = function (fieldElement) {
    if (!window.qTranslateModuleAcf?.show_language_tabs || !fieldElement?.length) {
        return;
    }

    const inputContainer = fieldElement.find('.acf-input').first()[0];
    if (!inputContainer || inputContainer.querySelector('.' + tabContainerClass)) {
        return;
    }

    const tabs = document.createElement('div');
    tabs.className = tabContainerClass;
    tabs.setAttribute('role', 'group');
    tabs.setAttribute('aria-label', 'qTranslate-XT languages');

    Object.keys(qTranx.config.languages).forEach(function (language) {
        const languageConfig = qTranx.config.languages[language];
        const tab = document.createElement('button');
        tab.type = 'button';
        tab.className = tabClass;
        tab.dataset.language = language;
        tab.textContent = language.toUpperCase();
        tab.title = languageConfig?.admin_name || languageConfig?.name || language;
        tab.addEventListener('click', function () {
            qTranx.hooks.switchActiveLanguage(language);
        });
        tabs.appendChild(tab);
    });

    inputContainer.insertBefore(tabs, inputContainer.firstChild);
    syncTabs(qTranx.hooks.getActiveLanguage());
};

wp.hooks.addAction('qtranx.languageSwitch', 'qtranx/acf/options-bridge', syncTabs);

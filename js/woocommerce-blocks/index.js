'use strict';

import {selectTranslation} from './translator';

const settings = window.qtxWooBlocks;
const rootSelector = '.wc-block-cart, .wc-block-checkout, .wc-block-mini-cart';
const skippedParents = 'script, style, textarea, noscript';

const translateValue = function (value) {
    return selectTranslation(value, settings);
};

const translateTextNode = function (node) {
    const parent = node.parentElement;
    if (!parent || !parent.closest(rootSelector) || parent.closest(skippedParents) || parent.isContentEditable) {
        return;
    }
    const translated = translateValue(node.nodeValue);
    if (translated !== node.nodeValue) {
        node.nodeValue = translated;
    }
};

const translateTree = function (root) {
    if (root.nodeType === Node.TEXT_NODE) {
        translateTextNode(root);
        return;
    }
    if (root.nodeType !== Node.ELEMENT_NODE) {
        return;
    }
    const element = root;
    if (!element.closest(rootSelector) && !element.matches(rootSelector) && !element.querySelector(rootSelector)) {
        return;
    }
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
    let node;
    while ((node = walker.nextNode())) {
        translateTextNode(node);
    }
};

const registerI18nFilters = function () {
    if (!window.wp || !wp.hooks || typeof wp.hooks.addFilter !== 'function') {
        return;
    }
    const filter = function (translation) {
        return translateValue(translation);
    };
    [
        'i18n.gettext',
        'i18n.gettext_with_context',
        'i18n.ngettext',
        'i18n.ngettext_with_context',
    ].forEach(function (hook) {
        wp.hooks.addFilter(hook, 'qtranslate-xt/woocommerce-blocks', filter);
    });
};

const observeBlocks = function () {
    document.querySelectorAll(rootSelector).forEach(translateTree);
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'characterData') {
                translateTextNode(mutation.target);
                return;
            }
            mutation.addedNodes.forEach(translateTree);
        });
    });
    observer.observe(document.documentElement, {childList: true, subtree: true, characterData: true});
};

registerI18nFilters();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observeBlocks, {once: true});
} else {
    observeBlocks();
}

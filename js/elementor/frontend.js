'use strict';

import {selectTranslation} from '../woocommerce-blocks/translator';

const settings = window.qtxElementorFrontend;
const rootSelector = '.elementor, .elementor-location-header, .elementor-location-footer, [data-elementor-type]';
const skippedParents = 'script, style, textarea, noscript, pre, code';
const translatedAttributes = ['alt', 'aria-label', 'placeholder', 'title'];

const translateValue = function (value) {
    return selectTranslation(value, settings);
};

const isInElementor = function (element) {
    return !!element?.closest?.(rootSelector);
};

const translateTextNode = function (node) {
    const parent = node.parentElement;
    if (!parent || !isInElementor(parent) || parent.closest(skippedParents) || parent.isContentEditable) {
        return;
    }
    const translated = translateValue(node.nodeValue);
    if (translated !== node.nodeValue) {
        node.nodeValue = translated;
    }
};

const translateAttributes = function (element) {
    if (!isInElementor(element) || element.closest(skippedParents) || element.isContentEditable) {
        return;
    }
    translatedAttributes.forEach(function (attribute) {
        if (!element.hasAttribute(attribute)) {
            return;
        }
        const value = element.getAttribute(attribute);
        const translated = translateValue(value);
        if (translated !== value) {
            element.setAttribute(attribute, translated);
        }
    });
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
    if (!isInElementor(element) && !element.matches(rootSelector) && !element.querySelector(rootSelector)) {
        return;
    }
    translateAttributes(element);
    element.querySelectorAll('*').forEach(translateAttributes);
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
    let node;
    while ((node = walker.nextNode())) {
        translateTextNode(node);
    }
};

const start = function () {
    document.querySelectorAll(rootSelector).forEach(translateTree);
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'characterData') {
                translateTextNode(mutation.target);
                return;
            }
            if (mutation.type === 'attributes') {
                translateAttributes(mutation.target);
                return;
            }
            mutation.addedNodes.forEach(translateTree);
        });
    });
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
        characterData: true,
        attributes: true,
        attributeFilter: translatedAttributes,
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, {once: true});
} else {
    start();
}

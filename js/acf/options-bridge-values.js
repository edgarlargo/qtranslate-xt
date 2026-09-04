/**
 * Pure value helpers for the built-in ACF Options bridge.
 */
'use strict';

import {splitLangs} from '../core/multi-lang';

const languageCodePattern = /^[a-z0-9_-]{2,12}$/i;

export const normalizeBridgeLanguages = function (configuredLanguages) {
    if (!configuredLanguages || typeof configuredLanguages !== 'object') {
        return [];
    }

    return Object.keys(configuredLanguages).filter(function (language) {
        return languageCodePattern.test(language);
    });
};

export const parseBridgeValue = function (rawValue, languages, defaultLanguage) {
    const raw = typeof rawValue === 'string' ? rawValue : '';
    const values = Object.create(null);
    const hasLanguageMarker = /(?:\[:[a-z0-9_-]+]|<!--:[a-z0-9_-]+-->|{:[a-z0-9_-]+})/i.test(raw);

    if (!hasLanguageMarker) {
        languages.forEach(function (language) {
            values[language] = '';
        });
        const targetLanguage = languages.indexOf(defaultLanguage) >= 0 ? defaultLanguage : languages[0];
        if (targetLanguage) {
            values[targetLanguage] = raw;
        }
        return values;
    }

    const parsed = splitLangs(raw);
    Object.keys(parsed).forEach(function (language) {
        if (languageCodePattern.test(language)) {
            values[language] = typeof parsed[language] === 'string' ? parsed[language] : '';
        }
    });
    languages.forEach(function (language) {
        if (!Object.prototype.hasOwnProperty.call(values, language)) {
            values[language] = '';
        }
    });

    return values;
};

export const serializeBridgeValue = function (values, languages) {
    const orderedLanguages = languages.slice();

    // Preserve values belonging to a previously enabled language. This avoids
    // deleting content merely because that language is currently disabled.
    Object.keys(values).forEach(function (language) {
        if (languageCodePattern.test(language) && orderedLanguages.indexOf(language) < 0) {
            orderedLanguages.push(language);
        }
    });

    let serialized = '';
    orderedLanguages.forEach(function (language) {
        const value = typeof values[language] === 'string' ? values[language] : '';
        serialized += '[:' + language + ']' + value;
    });

    return serialized + '[:]';
};

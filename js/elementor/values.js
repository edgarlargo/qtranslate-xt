/**
 * Pure scalar helpers for Elementor controls. This module deliberately has no
 * dependency on the qTranslate admin runtime because Elementor uses its own
 * editor application and script lifecycle.
 */
'use strict';

const languagePattern = /^[a-z0-9_-]{2,12}$/i;

const appendSegment = function (values, activeLanguage, segment) {
    if (!segment) {
        return;
    }
    if (activeLanguage) {
        if (!Object.prototype.hasOwnProperty.call(values, activeLanguage)) {
            values[activeLanguage] = '';
        }
        values[activeLanguage] += segment;
        return;
    }
    Object.keys(values).forEach(function (language) {
        values[language] += segment;
    });
};

export const parseElementorValue = function (rawValue, languages, defaultLanguage) {
    const raw = typeof rawValue === 'string' ? rawValue : '';
    const values = Object.create(null);
    languages.forEach(function (language) {
        values[language] = '';
    });

    const marker = /<!--:([a-z0-9_-]{2,12})-->|<!--:-->|\[:([a-z0-9_-]{2,12})]|\[:]|{:([a-z0-9_-]{2,12})}|{:}/gi;
    let activeLanguage = null;
    let cursor = 0;
    let foundMarker = false;
    let match;
    while ((match = marker.exec(raw)) !== null) {
        foundMarker = true;
        appendSegment(values, activeLanguage, raw.slice(cursor, match.index));
        activeLanguage = match[1] || match[2] || match[3] || null;
        if (activeLanguage && !Object.prototype.hasOwnProperty.call(values, activeLanguage)) {
            values[activeLanguage] = '';
        }
        cursor = marker.lastIndex;
    }
    if (!foundMarker) {
        const target = languages.includes(defaultLanguage) ? defaultLanguage : languages[0];
        if (target) {
            values[target] = raw;
        }
        return values;
    }
    appendSegment(values, activeLanguage, raw.slice(cursor));

    return values;
};

export const serializeElementorValue = function (values, languages) {
    const ordered = languages.slice();
    Object.keys(values).forEach(function (language) {
        if (languagePattern.test(language) && !ordered.includes(language)) {
            ordered.push(language);
        }
    });

    return ordered.map(function (language) {
        return '[:' + language + ']' + (typeof values[language] === 'string' ? values[language] : '');
    }).join('') + '[:]';
};

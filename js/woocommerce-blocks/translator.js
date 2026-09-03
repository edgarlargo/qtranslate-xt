'use strict';

const appendSegment = function (translations, activeLanguage, segment) {
    if (!segment) {
        return;
    }
    if (activeLanguage) {
        if (Object.prototype.hasOwnProperty.call(translations, activeLanguage)) {
            translations[activeLanguage] += segment;
        }
        return;
    }
    Object.keys(translations).forEach(function (language) {
        translations[language] += segment;
    });
};

export const selectTranslation = function (value, settings) {
    if (typeof value !== 'string' || !settings || !Array.isArray(settings.enabledLanguages)) {
        return value;
    }

    const translations = Object.fromEntries(
        settings.enabledLanguages.map(function (language) {
            return [language, ''];
        })
    );
    const codePattern = settings.languageCodePattern || '[a-z]{2,3}';
    const marker = new RegExp(
        '<!--:(' + codePattern + ')-->|<!--:-->|\\[:(' + codePattern + ')]|\\[:]|{:(' + codePattern + ')}|{:}',
        'gi'
    );
    let activeLanguage = null;
    let cursor = 0;
    let foundMarker = false;
    let match;

    while ((match = marker.exec(value)) !== null) {
        foundMarker = true;
        appendSegment(translations, activeLanguage, value.slice(cursor, match.index));
        activeLanguage = match[1] || match[2] || match[3] || null;
        cursor = marker.lastIndex;
    }
    if (!foundMarker) {
        return value;
    }
    appendSegment(translations, activeLanguage, value.slice(cursor));

    const requested = translations[settings.language];
    if (requested) {
        return requested.trim();
    }
    const fallback = translations[settings.defaultLanguage];
    if (fallback) {
        return fallback.trim();
    }
    const available = Object.values(translations).find(function (translation) {
        return translation !== '';
    });
    return available ? available.trim() : '';
};

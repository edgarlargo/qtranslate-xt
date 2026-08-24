<?php

namespace QTX\Tests\Characterization;

use PHPUnit\Framework\TestCase;

final class LegacyWrapperTranslatorTest extends TestCase {
    public function testCurrentAndDefaultWrappersRemainExactUseFacades(): void {
        global $q_config;
        $q_config['language']         = 'lv';
        $q_config['default_language'] = 'en';
        $raw                          = '[:ru]RU[:en]EN[:]';

        self::assertSame( qtranxf_use( 'lv', $raw, false, true ), qtranxf_useCurrentLanguageIfNotFoundShowEmpty( $raw ) );
        self::assertSame( qtranxf_use( 'lv', $raw, true, false ), qtranxf_useCurrentLanguageIfNotFoundShowAvailable( $raw ) );
        self::assertSame( qtranxf_use( 'lv', $raw, false, false ), qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $raw ) );
        self::assertSame( qtranxf_use( 'en', $raw, false, false ), qtranxf_useDefaultLanguage( $raw ) );
    }

    public function testTranslatorTextMethodPreservesLanguageAndFlagMapping(): void {
        global $q_config;
        $q_config['language'] = 'lv';
        $translator           = new \QTX_Translator();
        $raw                  = '[:ru]RU[:en]EN[:]';

        self::assertSame( qtranxf_use( 'lv', $raw ), $translator->translate_text( $raw ) );
        self::assertSame( qtranxf_use( 'en', $raw ), $translator->translate_text( $raw, 'en' ) );
        self::assertSame(
            qtranxf_use( 'lv', $raw, false, true ),
            $translator->translate_text( $raw, 'lv', QTX_TRANSLATOR_SHOW_EMPTY )
        );
        self::assertSame(
            qtranxf_use( 'lv', $raw, true, false ),
            $translator->translate_text( $raw, 'lv', QTX_TRANSLATOR_SHOW_AVAILABLE )
        );
        self::assertSame(
            qtranxf_use( 'lv', $raw, true, true ),
            $translator->translate_text( $raw, 'lv', QTX_TRANSLATOR_SHOW_AVAILABLE | QTX_TRANSLATOR_SHOW_EMPTY )
        );
    }
}

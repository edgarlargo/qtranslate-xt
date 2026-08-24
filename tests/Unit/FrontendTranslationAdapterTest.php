<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\WordPress\FrontendTranslationAdapter;

final class FrontendTranslationAdapterTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig = $q_config;
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
    }

    public function testDeclarativeTitleRegistrationPreservesHookPriorityAndAcceptedArguments(): void {
        $GLOBALS['qtx_test_filters']         = array();
        $GLOBALS['qtx_test_removed_filters'] = array();
        $filters = array( 'text' => array( 'the_title' => 20, 'widget_title' => 7 ) );

        qtranxf_add_filters( $filters );
        self::assertSame( array(
            'the_title',
            array( FrontendTranslationAdapter::class, 'translateTitle' ),
            20,
            1,
        ), $GLOBALS['qtx_test_filters'][0] );
        self::assertSame( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $GLOBALS['qtx_test_filters'][1][1] );

        qtranxf_remove_filters( $filters );
        self::assertSame( array(
            'the_title',
            array( FrontendTranslationAdapter::class, 'translateTitle' ),
            20,
        ), $GLOBALS['qtx_test_removed_filters'][0] );
    }

    public function testTitleAdapterPreservesLegacyCallbackOutputAndTypes(): void {
        global $q_config;
        $q_config['language'] = 'ru';

        foreach ( array(
            'plain title',
            '[:lv]Virsraksts[:ru]Заголовок[:en]Title[:]',
            '[:lv]<b>HTML</b>[:ru]<i>HTML</i>[:]',
            '[:lv][:en]Fallback[:]',
        ) as $title ) {
            self::assertSame(
                qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $title ),
                FrontendTranslationAdapter::translateTitle( $title )
            );
        }

        self::assertSame( 0, FrontendTranslationAdapter::translateTitle( 0 ) );
        self::assertSame( array( 'x' => 'Заголовок' ), FrontendTranslationAdapter::translateTitle( array( 'x' => '[:lv]Virsraksts[:ru]Заголовок[:]' ) ) );
    }

    public function testContentAndExcerptAdaptersPreserveShowAvailablePolicy(): void {
        global $q_config;
        $q_config['language'] = 'en';
        $values = array(
            'plain',
            '[:lv]Saturs[:ru]Содержимое[:]',
            '[:en]<strong>Content</strong>[:]',
            '[:lv]<script>x</script>[:]',
        );

        foreach ( $values as $value ) {
            $legacy = qtranxf_useCurrentLanguageIfNotFoundShowAvailable( $value );
            self::assertSame( $legacy, FrontendTranslationAdapter::translateContent( $value ) );
            self::assertSame( $legacy, FrontendTranslationAdapter::translateExcerpt( $value ) );
        }
    }

    public function testMainFilterRegistrationPreservesContentAndExcerptContracts(): void {
        $GLOBALS['qtx_test_filters'] = array();
        qtranxf_add_main_filters();

        $byHook = array();
        foreach ( $GLOBALS['qtx_test_filters'] as $registration ) {
            $byHook[ $registration[0] ] = $registration;
        }
        self::assertSame(
            array( 'the_content', array( FrontendTranslationAdapter::class, 'translateContent' ), 100, 1 ),
            $byHook['the_content']
        );
        self::assertSame(
            array( 'the_excerpt', array( FrontendTranslationAdapter::class, 'translateExcerpt' ), 0, 1 ),
            $byHook['the_excerpt']
        );
        self::assertSame(
            array( 'the_excerpt_rss', array( FrontendTranslationAdapter::class, 'translateRssExcerpt' ), 0, 1 ),
            $byHook['the_excerpt_rss']
        );
        self::assertSame(
            array( 'the_title_rss', array( FrontendTranslationAdapter::class, 'translateRssText' ), 0, 1 ),
            $byHook['the_title_rss']
        );
        self::assertSame(
            array( 'the_content_rss', array( FrontendTranslationAdapter::class, 'translateRssText' ), 0, 1 ),
            $byHook['the_content_rss']
        );
    }

    public function testRssAdaptersPreserveTheirDistinctLegacyPolicies(): void {
        global $q_config;
        $q_config['language'] = 'en';
        $raw = '[:lv]Saturs[:ru]Содержимое[:]';

        self::assertSame( qtranxf_useCurrentLanguageIfNotFoundShowAvailable( $raw ), FrontendTranslationAdapter::translateRssExcerpt( $raw ) );
        self::assertSame( qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $raw ), FrontendTranslationAdapter::translateRssText( $raw ) );
    }
}

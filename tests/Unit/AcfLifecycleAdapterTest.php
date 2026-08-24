<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\FallbackPolicy;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Multilingual\TranslationService;
use QTX\Integration\Acf\AcfFieldSchema;
use QTX\Integration\Acf\AcfLifecycleAdapter;
use QTX\Integration\Acf\AcfScalarTranslator;
use QTX\Integration\Acf\AcfValueContext;

final class AcfLifecycleAdapterTest extends TestCase {
    public function testOfficialFormatValueHookLifecycleIsSymmetricAndIdempotent(): void {
        $GLOBALS['qtx_test_filters'] = array();
        $GLOBALS['qtx_test_removed_filters'] = array();
        $adapter = $this->adapter();

        $adapter->register();
        $adapter->register();
        self::assertCount( 6, $GLOBALS['qtx_test_filters'] );
        self::assertSame( 'acf/format_value/type=text', $GLOBALS['qtx_test_filters'][0][0] );
        self::assertSame( array( $adapter, 'formatValue' ), $GLOBALS['qtx_test_filters'][0][1] );
        self::assertSame( 5, $GLOBALS['qtx_test_filters'][0][2] );
        self::assertSame( 3, $GLOBALS['qtx_test_filters'][0][3] );

        $adapter->unregister();
        $adapter->unregister();
        self::assertCount( 6, $GLOBALS['qtx_test_removed_filters'] );
        self::assertSame( array( $adapter, 'formatValue' ), $GLOBALS['qtx_test_removed_filters'][0][1] );
    }

    public function testFormatsWhitelistedScalarForPostsAndOptionsPages(): void {
        $adapter = $this->adapter();
        $field = array( 'key' => 'field_heading', 'name' => 'heading', 'type' => 'text' );
        $raw = '[:lv]Virsraksts[:ru]Заголовок[:]';

        self::assertSame( 'translated:' . $raw, $adapter->formatValue( $raw, 42, $field ) );
        self::assertSame( 'translated:' . $raw, $adapter->formatValue( $raw, 'option', $field ) );
    }

    public function testTechnicalAndUnstableFieldsRemainUntouched(): void {
        $adapter = $this->adapter();
        $image = array( 'key' => 'field_image', 'name' => 'image', 'type' => 'image' );
        $unstable = array( 'key' => 'heading', 'name' => 'heading', 'type' => 'text' );

        self::assertSame( 42, $adapter->formatValue( 42, 1, $image ) );
        self::assertSame( 'raw', $adapter->formatValue( 'raw', 1, $unstable ) );
    }

    public function testCompoundFormattingPreservesRowsAndTechnicalValues(): void {
        $adapter = $this->adapter();
        $field = array(
            'key' => 'field_rows',
            'type' => 'repeater',
            'sub_fields' => array(
                array( 'key' => 'field_copy', 'name' => 'copy', 'type' => 'wysiwyg' ),
                array( 'key' => 'field_link', 'name' => 'link', 'type' => 'url' ),
            ),
        );
        $value = array( array( 'copy' => '<b>raw</b>', 'link' => 'https://example.test' ) );

        self::assertSame(
            array( array( 'copy' => 'translated:<b>raw</b>', 'link' => 'https://example.test' ) ),
            $adapter->formatValue( $value, 'options', $field )
        );
    }

    public function testConcreteTranslatorUsesExplicitLanguageRequestAndContext(): void {
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'lv' );
        $context = new LanguageContext( $catalog, 'ru' );
        $translator = new AcfScalarTranslator(
            new MultilingualParser( $catalog->codes(), $context->current() ),
            new TranslationService(),
            new LanguageRequest( 'ru', FallbackPolicy::legacy() ),
            $context
        );
        $adapter = new AcfLifecycleAdapter( new AcfFieldSchema(), $translator );
        $field = array( 'key' => 'field_heading', 'name' => 'heading', 'type' => 'text' );

        self::assertSame(
            'Заголовок',
            $adapter->formatValue( '[:lv]Virsraksts[:ru]Заголовок[:en]Title[:]', 'option', $field )
        );
    }

    public function testFormatsOptionsFieldWithUnicodeAcfKey(): void {
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'lv' );
        $context = new LanguageContext( $catalog, 'lv' );
        $translator = new AcfScalarTranslator(
            new MultilingualParser( $catalog->codes(), $context->current() ),
            new TranslationService(),
            new LanguageRequest( 'lv', FallbackPolicy::legacy() ),
            $context
        );
        $adapter = new AcfLifecycleAdapter( new AcfFieldSchema(), $translator );

        self::assertSame(
            'Tālrunis',
            $adapter->formatValue(
                '[:lv]Tālrunis[:ru]Телефон[:en]Phone[:]',
                'options',
                array( 'key' => 'field_tālrunis_placeholder', 'type' => 'text' )
            )
        );
    }

    public function testRawAdminContextDoesNotProjectValues(): void {
        $adapter = new AcfLifecycleAdapter(
            new AcfFieldSchema(),
            static fn ( string $value ): string => 'translated:' . $value,
            new AcfValueContext( AcfValueContext::RAW )
        );
        $raw = '[:lv]Sūtīt[:ru]Отправить[:en]Send[:]';

        self::assertSame(
            $raw,
            $adapter->formatValue( $raw, 'option', array( 'key' => 'field_button', 'type' => 'text' ) )
        );
    }

    private function adapter(): AcfLifecycleAdapter {
        return new AcfLifecycleAdapter(
            new AcfFieldSchema(),
            static fn ( string $value ): string => 'translated:' . $value
        );
    }
}

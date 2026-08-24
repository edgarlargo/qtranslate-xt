<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
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

final class AcfNativeValuePipelineTest extends TestCase {
    /** @return iterable<string, array{string,string,string}> */
    public static function realWorldOptionsProvider(): iterable {
        yield 'contact title lv' => array( 'lv', '[:lv]Sazināties ar mums[:ru]Связаться с нами[:en]Contact us[:]', 'Sazināties ar mums' );
        yield 'contact title ru' => array( 'ru', '[:lv]Sazināties ar mums[:ru]Связаться с нами[:en]Contact us[:]', 'Связаться с нами' );
        yield 'contact title en' => array( 'en', '[:lv]Sazināties ar mums[:ru]Связаться с нами[:en]Contact us[:]', 'Contact us' );
        yield 'message' => array( 'en', '[:lv]Nosūti mums ziņu![:ru]Отправьте нам сообщение![:en]Send us a message![:]', 'Send us a message!' );
        yield 'placeholder' => array( 'ru', '[:lv]Vārds Uzvārds[:ru]Имя Фамилия[:en]Name Surname[:]', 'Имя Фамилия' );
        yield 'button' => array( 'lv', '[:lv]Sūtīt[:ru]Отправить[:en]Send[:]', 'Sūtīt' );
    }

    #[DataProvider( 'realWorldOptionsProvider' )]
    public function testNormalOptionsValuesNeverExposeRawMarkers( string $language, string $raw, string $expected ): void {
        foreach ( array( 'text', 'textarea', 'wysiwyg' ) as $type ) {
            $field = array( 'key' => 'field_options_' . $type, 'name' => 'copy', 'type' => $type );
            $value = $this->adapter( $language )->formatValue( $raw, 'options', $field );
            self::assertSame( $expected, $value );
            self::assertStringNotContainsString( '[:', $value );
        }
    }

    public function testPlainFallbackEmptyAndMalformedValuesFollowCompatibilityParser(): void {
        $field = array( 'key' => 'field_copy', 'name' => 'copy', 'type' => 'text' );
        $adapter = $this->adapter( 'en' );

        self::assertSame( 'plain', $adapter->formatValue( 'plain', 'option', $field ) );
        self::assertSame( 'Latviski', $adapter->formatValue( '[:lv]Latviski[:ru]Русский[:]', 'option', $field ) );
        self::assertSame( 'Latviski', $adapter->formatValue( '[:lv]Latviski[:en][:]', 'option', $field ) );
        self::assertSame( 'Open', $adapter->formatValue( '[:lv]Open', 'option', $field ) );
    }

    public function testTechnicalFieldsAndSerializedValuesRemainUntouched(): void {
        $adapter = $this->adapter( 'en' );
        $raw = '[:lv]Tehnisks[:en]Technical[:]';
        foreach ( array( 'image', 'file', 'number', 'true_false', 'url', 'email', 'relationship', 'post_object', 'color_picker', 'google_map' ) as $type ) {
            self::assertSame( $raw, $adapter->formatValue( $raw, 'option', array( 'key' => 'field_' . $type, 'type' => $type ) ) );
        }
        self::assertSame(
            'a:1:{s:3:"key";s:5:"value";}',
            $adapter->formatValue( 'a:1:{s:3:"key";s:5:"value";}', 'option', array( 'key' => 'field_text', 'type' => 'text' ) )
        );
    }

    public function testExplicitRawContextPreservesOneUnderlyingInlineValue(): void {
        $raw = '[:lv]Sūtīt[:ru]Отправить[:en]Send[:]';
        $adapter = $this->adapter( 'en', AcfValueContext::RAW );

        self::assertSame( $raw, $adapter->formatValue( $raw, 'options', array( 'key' => 'field_button', 'type' => 'text' ) ) );
    }

    private function adapter( string $language, string $mode = AcfValueContext::TRANSLATED ): AcfLifecycleAdapter {
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'en' );
        $translator = new AcfScalarTranslator(
            new MultilingualParser( $catalog->codes(), 'en' ),
            new TranslationService(),
            new LanguageRequest( $language, FallbackPolicy::legacy() ),
            new LanguageContext( $catalog, $language )
        );

        return new AcfLifecycleAdapter( new AcfFieldSchema(), $translator, new AcfValueContext( $mode ) );
    }
}

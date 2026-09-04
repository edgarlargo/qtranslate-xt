<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\Acf\AcfSafeBridgeValueAdapter;

final class AcfSafeBridgeValueAdapterTest extends TestCase {
    public function testRegistersStandaloneCompatibleLateTypeHooksExactlyOnce(): void {
        $GLOBALS['qtx_test_filters'] = array();
        $adapter = new AcfSafeBridgeValueAdapter( static fn (): bool => true, static fn ( string $value ): string => $value );

        $adapter->register();
        $adapter->register();

        self::assertSame(
            array(
                array( 'acf/format_value/type=text', array( $adapter, 'formatValue' ), 99, 3 ),
                array( 'acf/format_value/type=textarea', array( $adapter, 'formatValue' ), 99, 3 ),
                array( 'acf/format_value/type=wysiwyg', array( $adapter, 'formatValue' ), 99, 3 ),
            ),
            $GLOBALS['qtx_test_filters']
        );
    }

    public function testReportedFiveLanguageOptionsValueIsProjectedWithoutFieldMetadata(): void {
        $raw = '[:en]Location / Year[:lv]Atrašanās vieta / Gads[:ru]Местоположение / Год[:fi]Sijainti / Vuosi[:sv]Plats / År[:]';
        $adapter = new AcfSafeBridgeValueAdapter(
            static fn (): bool => true,
            static fn ( string $value ): string => $value === $raw ? 'Местоположение / Год' : $value
        );

        self::assertSame( 'Местоположение / Год', $adapter->formatValue( $raw, 'options', array() ) );
        self::assertStringNotContainsString( '[:', $adapter->formatValue( $raw, 'options', array() ) );
    }

    public function testAdminPlainAndNonStringValuesRemainUntouched(): void {
        $translated = false;
        $adapter = new AcfSafeBridgeValueAdapter(
            static fn (): bool => false,
            static function ( string $value ) use ( &$translated ): string {
                $translated = true;
                return $value;
            }
        );
        $raw = '[:en]Location[:ru]Местоположение[:]';

        self::assertSame( $raw, $adapter->formatValue( $raw ) );
        self::assertSame( 'plain', ( new AcfSafeBridgeValueAdapter( static fn (): bool => true ) )->formatValue( 'plain' ) );
        self::assertSame( array( $raw ), ( new AcfSafeBridgeValueAdapter( static fn (): bool => true ) )->formatValue( array( $raw ) ) );
        self::assertFalse( $translated );
    }
}

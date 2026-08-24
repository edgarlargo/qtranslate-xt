<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\FallbackPolicy;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Storage\FieldDefinition;
use QTX\Core\Storage\FieldRegistry;
use QTX\Core\Storage\RegisteredValueAdapter;
use QTX\Integration\WordPress\RegisteredOptionAdapter;

final class RegisteredOptionAdapterTest extends TestCase {
    public function testRegistersOnlyExactOptionDefinitionsAndCanUnregister(): void {
        $GLOBALS['qtx_test_filters'] = array();
        $GLOBALS['qtx_test_removed_filters'] = array();
        $registry = new FieldRegistry();
        $registry->register( new FieldDefinition( 'option', 'tagline' ) );
        $registry->register( new FieldDefinition( 'post', 'tagline' ) );
        $adapter = $this->adapter( $registry );

        $adapter->register();
        self::assertCount( 1, $GLOBALS['qtx_test_filters'] );
        self::assertSame( 'option_tagline', $GLOBALS['qtx_test_filters'][0][0] );
        self::assertSame( 5, $GLOBALS['qtx_test_filters'][0][2] );
        self::assertSame( 1, $GLOBALS['qtx_test_filters'][0][3] );
        self::assertSame(
            'Описание',
            $GLOBALS['qtx_test_filters'][0][1]( '[:lv]Apraksts[:ru]Описание[:]' )
        );

        $adapter->register();
        self::assertCount( 1, $GLOBALS['qtx_test_filters'] );
        $adapter->unregister();
        self::assertCount( 1, $GLOBALS['qtx_test_removed_filters'] );
        self::assertSame( 'option_tagline', $GLOBALS['qtx_test_removed_filters'][0][0] );
        self::assertSame( $GLOBALS['qtx_test_filters'][0][1], $GLOBALS['qtx_test_removed_filters'][0][1] );
    }

    private function adapter( FieldRegistry $registry ): RegisteredOptionAdapter {
        $catalog = new LanguageCatalog( array( 'lv', 'ru' ), 'lv' );
        $context = new LanguageContext( $catalog, 'ru' );
        $request = new LanguageRequest( 'ru', FallbackPolicy::legacy() );
        $valueAdapter = new RegisteredValueAdapter(
            $registry,
            new MultilingualParser( $catalog->codes(), $context->current() )
        );

        return new RegisteredOptionAdapter( $registry, $valueAdapter, $request, $context );
    }
}

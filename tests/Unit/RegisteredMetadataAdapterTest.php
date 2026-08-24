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
use QTX\Core\Storage\MetadataValue;
use QTX\Core\Storage\RegisteredValueAdapter;
use QTX\Integration\WordPress\RegisteredMetadataAdapter;

final class RegisteredMetadataAdapterTest extends TestCase {
    public function testExactRegisteredSingleValueIsTranslatedWithoutRecursion(): void {
        $reads = array();
        $invalidations = array();
        $adapter = $this->adapter(
            function ( string $storage, int $objectId, string $key ) use ( &$reads ): MetadataValue {
                $reads[] = array( $storage, $objectId, $key );
                return MetadataValue::scalar( '[:lv]Vērtība[:ru]Значение[:]' );
            },
            function ( string $storage, int $objectId, string $key ) use ( &$invalidations ): void {
                $invalidations[] = array( $storage, $objectId, $key );
            }
        );

        self::assertSame( 'Значение', $adapter->filter( 'post', null, 42, 'subtitle', true ) );
        self::assertSame( array( array( 'post', 42, 'subtitle' ) ), $reads );
        self::assertNull( $adapter->filter( 'post', null, 42, 'other', true ) );
        self::assertNull( $adapter->filter( 'post', null, 42, 'subtitle', false ) );
        self::assertSame( 'earlier', $adapter->filter( 'post', 'earlier', 42, 'subtitle', true ) );
        self::assertCount( 1, $reads );

        $GLOBALS['qtx_test_actions'][0][1]( 1, 42, 'subtitle', 'new' );
        $GLOBALS['qtx_test_actions'][0][1]( 1, 42, 'other', 'new' );
        self::assertSame( array( array( 'post', 42, 'subtitle' ) ), $invalidations );
    }

    public function testUnsupportedProviderValueFallsThroughToWordPress(): void {
        $adapter = $this->adapter(
            static fn (): MetadataValue => MetadataValue::unsupported(),
            static function (): void {}
        );

        self::assertNull( $adapter->filter( 'post', null, 9, 'subtitle', true ) );
    }

    public function testRegistersOnlyScopesWithDefinitionsAndUnregistersSymmetrically(): void {
        $adapter = $this->adapter(
            static fn (): MetadataValue => MetadataValue::unsupported(),
            static function (): void {}
        );
        self::assertCount( 1, $GLOBALS['qtx_test_filters'] );
        self::assertCount( 1, $GLOBALS['qtx_test_actions'] );
        self::assertSame( 'get_post_metadata', $GLOBALS['qtx_test_filters'][0][0] );
        self::assertSame( 'updated_post_meta', $GLOBALS['qtx_test_actions'][0][0] );

        $adapter->register();
        self::assertCount( 1, $GLOBALS['qtx_test_filters'] );
        $adapter->unregister();
        self::assertCount( 1, $GLOBALS['qtx_test_removed_filters'] );
        self::assertCount( 1, $GLOBALS['qtx_test_removed_actions'] );
    }

    public function testSupportsPostTermAndUserMetadataScopes(): void {
        $this->adapter(
            static fn (): MetadataValue => MetadataValue::unsupported(),
            static function (): void {},
            array( 'post', 'term', 'user' )
        );

        self::assertSame(
            array( 'get_post_metadata', 'get_term_metadata', 'get_user_metadata' ),
            array_column( $GLOBALS['qtx_test_filters'], 0 )
        );
        self::assertSame(
            array( 'updated_post_meta', 'updated_term_meta', 'updated_user_meta' ),
            array_column( $GLOBALS['qtx_test_actions'], 0 )
        );
    }

    private function adapter( callable $reader, callable $invalidator, array $storages = array( 'post' ) ): RegisteredMetadataAdapter {
        $GLOBALS['qtx_test_filters'] = array();
        $GLOBALS['qtx_test_actions'] = array();
        $GLOBALS['qtx_test_removed_filters'] = array();
        $GLOBALS['qtx_test_removed_actions'] = array();
        $registry = new FieldRegistry();
        foreach ( $storages as $storage ) {
            $registry->register( new FieldDefinition( $storage, 'subtitle' ) );
        }
        $catalog = new LanguageCatalog( array( 'lv', 'ru' ), 'lv' );
        $context = new LanguageContext( $catalog, 'ru' );
        $request = new LanguageRequest( 'ru', FallbackPolicy::legacy() );
        $valueAdapter = new RegisteredValueAdapter( $registry, new MultilingualParser( $catalog->codes(), 'ru' ) );
        $adapter = new RegisteredMetadataAdapter( $registry, $valueAdapter, $request, $context, $reader, $invalidator );
        $adapter->register();
        return $adapter;
    }
}

<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\WordPress\FrontendTranslationAdapter;

final class FrontendTermAdapterTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig = $q_config;
        $q_config['language'] = 'ru';
        $q_config['default_language'] = 'lv';
        $q_config['term_name'] = array(
            'Category' => array( 'ru' => 'Категория' ),
        );
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
    }

    public function testTermAdapterMatchesLegacyForScalarObjectAndArray(): void {
        $values = array(
            'Category',
            (object) array(
                'name'        => 'Category',
                'description' => '[:lv]Apraksts[:ru]Описание[:]',
            ),
            array(
                (object) array(
                    'name'        => 'Category',
                    'description' => '[:lv]Apraksts[:ru]Описание[:]',
                ),
            ),
        );

        foreach ( $values as $value ) {
            $legacy  = $this->copyValue( $value );
            $adapter = $this->copyValue( $value );
            self::assertEquals(
                qtranxf_useTermLib( $legacy ),
                FrontendTranslationAdapter::translateTerm( $adapter )
            );
        }
    }

    public function testDeclarativeTermRegistrationAndRemovalPreserveContract(): void {
        $GLOBALS['qtx_test_filters'] = array();
        $GLOBALS['qtx_test_removed_filters'] = array();
        $filters = array( 'term' => array( 'get_terms' => 8 ) );

        qtranxf_add_filters( $filters );
        self::assertSame(
            array( 'get_terms', array( FrontendTranslationAdapter::class, 'translateTerm' ), 8, 1 ),
            $GLOBALS['qtx_test_filters'][0]
        );

        qtranxf_remove_filters( $filters );
        self::assertSame(
            array( 'get_terms', array( FrontendTranslationAdapter::class, 'translateTerm' ), 8 ),
            $GLOBALS['qtx_test_removed_filters'][0]
        );
    }

    private function copyValue( $value ) {
        if ( is_object( $value ) ) {
            return clone $value;
        }
        if ( is_array( $value ) ) {
            return array_map( array( $this, 'copyValue' ), $value );
        }

        return $value;
    }
}

<?php

namespace QTX\Tests\Characterization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyFacadeDifferentialTest extends TestCase {
    public static function corpusProvider(): iterable {
        $path   = dirname( __DIR__ ) . '/Fixtures/multilingual-corpus.json';
        $corpus = json_decode( file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );

        foreach ( $corpus['cases'] as $case ) {
            yield $case['id'] => array( $case );
        }
    }

    #[DataProvider( 'corpusProvider' )]
    public function testFacadeMatchesPreservedLegacyImplementationOnSharedCorpus( array $case ): void {
        $raw = $this->materializeRaw( $case );

        $this->assertCompleteParity( $raw, $case['id'] );
    }

    public function testDeterministicGeneratedFacadeParity(): void {
        foreach ( $this->generatedInputs( 400, 0x51A3 ) as $index => $raw ) {
            $this->assertCompleteParity( $raw, 'generated-' . $index );
        }
    }

    public static function arbitraryBlocksProvider(): iterable {
        yield 'empty block' => array( array( '' ) );
        yield 'split neutral blocks' => array( array( 'plain ', 'text' ) );
        yield 'marker-looking fragment remains text' => array( array( '[:lv]not-a-token' ) );
        yield 'explicit empty content block is found' => array( array( '[:lv]', '', '[:]' ) );
        yield 'unknown language' => array( array( '[:zz]', 'unknown', '[:]' ) );
        yield 'uppercase duplicate' => array( array( '[:LV]', 'A', '[:LV]', 'B' ) );
        yield 'mixed syntax' => array( array( '<!--:lv-->', 'A', '[:ru]', 'B', '{:}' ) );
        yield 'orphan closing' => array( array( 'prefix', '[:]', 'suffix' ) );
    }

    #[DataProvider( 'arbitraryBlocksProvider' )]
    public function testSplitBlockFacadePreservesCallerSuppliedBlockSemantics( array $blocks ): void {
        $legacyFound = array( 'existing' => true );
        $facadeFound = array( 'existing' => true );

        self::assertSame( qtranxf_legacy_split_blocks( $blocks, $legacyFound ), qtranxf_split_blocks( $blocks, $facadeFound ) );
        self::assertSame( $legacyFound, $facadeFound );
        self::assertSame( qtranxf_legacy_split_languages( $blocks ), qtranxf_split_languages( $blocks ) );
    }

    private function assertCompleteParity( string $raw, string $label ): void {
        self::assertSame( qtranxf_legacy_isMultilingual( $raw ), qtranxf_isMultilingual( $raw ), $label . ': detector' );

        $legacyBlocks = qtranxf_legacy_get_language_blocks( $raw );
        $facadeBlocks = qtranxf_get_language_blocks( $raw );
        self::assertSame( $legacyBlocks, $facadeBlocks, $label . ': blocks' );
        self::assertSame( qtranxf_legacy_split( $raw ), qtranxf_split( $raw ), $label . ': split' );

        $legacyFound = array( 'existing' => true );
        $facadeFound = array( 'existing' => true );
        self::assertSame(
            qtranxf_legacy_split_blocks( $legacyBlocks, $legacyFound ),
            qtranxf_split_blocks( $facadeBlocks, $facadeFound ),
            $label . ': split blocks'
        );
        self::assertSame( $legacyFound, $facadeFound, $label . ': found map' );
        self::assertSame( qtranxf_legacy_split_languages( $legacyBlocks ), qtranxf_split_languages( $facadeBlocks ), $label . ': encoded split' );
        self::assertSame( qtranxf_legacy_getAvailableLanguages( $raw ), qtranxf_getAvailableLanguages( $raw ), $label . ': available' );
    }

    /** @return string[] */
    private function generatedInputs( int $count, int $seed ): array {
        $state = $seed;
        $next  = static function ( int $max ) use ( &$state ): int {
            $state = (int) ( ( 1103515245 * $state + 12345 ) & 0x7fffffff );

            return $state % $max;
        };

        $markers = array( '[:lv]', '[:ru]', '[:en]', '[:zz]', '[:LV]', '<!--:lv-->', '<!--:ru-->', '{:lv}', '{:ru}' );
        $closings = array( '[:]', '<!--:-->', '{:}', '{::}', '', '[:l]' );
        $texts = array( 'plain', ' ', "\n", 'Ābols', 'Привет', '😊', '<b>HTML</b>', '0', 'prefix ', ' tail' );
        $result = array();

        for ( $i = 0; $i < $count; ++$i ) {
            $parts     = array();
            $partCount = 1 + $next( 9 );
            for ( $part = 0; $part < $partCount; ++$part ) {
                $kind = $next( 5 );
                if ( $kind <= 1 ) {
                    $parts[] = $texts[ $next( count( $texts ) ) ];
                } elseif ( $kind <= 3 ) {
                    $parts[] = $markers[ $next( count( $markers ) ) ];
                } else {
                    $parts[] = $closings[ $next( count( $closings ) ) ];
                }
            }
            $result[] = implode( '', $parts );
        }

        return $result;
    }

    private function materializeRaw( array $case ): string {
        if ( isset( $case['raw'] ) ) {
            return $case['raw'];
        }

        $generator = $case['raw_generator'];

        return $generator['prefix'] . str_repeat( $generator['repeat'], $generator['count'] ) . $generator['suffix'];
    }
}

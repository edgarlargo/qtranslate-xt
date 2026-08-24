<?php

namespace QTX\Tests\Characterization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyJoinFacadeDifferentialTest extends TestCase {
    public static function corpusProvider(): iterable {
        $path   = dirname( __DIR__ ) . '/Fixtures/multilingual-corpus.json';
        $corpus = json_decode( file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );

        foreach ( $corpus['cases'] as $case ) {
            yield $case['id'] => array( $case );
        }
    }

    #[DataProvider( 'corpusProvider' )]
    public function testPureJoinFacadesMatchPreservedLegacyOnCorpus( array $case ): void {
        $raw          = $this->materializeRaw( $case );
        $translations = qtranxf_legacy_split( $raw );

        $this->assertJoinParity( $translations, $case['id'] );
    }

    public function testPureJoinFacadesMatchPreservedLegacyOnGeneratedTranslationMaps(): void {
        foreach ( $this->generatedTranslationMaps( 400, 0xA32A ) as $index => $translations ) {
            $this->assertJoinParity( $translations, 'generated-' . $index );
        }
    }

    public static function edgeMapProvider(): iterable {
        yield 'empty map' => array( array() );
        yield 'all empty' => array( array( 'lv' => '', 'ru' => '', 'en' => '' ) );
        yield 'all same' => array( array( 'lv' => 'same', 'ru' => 'same', 'en' => 'same' ) );
        yield 'one nonempty' => array( array( 'lv' => 'A', 'ru' => '', 'en' => '' ) );
        yield 'zero strings are empty' => array( array( 'lv' => '0', 'ru' => '0' ) );
        yield 'uppercase and unknown keys' => array( array( 'LV' => 'A', 'zz' => 'B' ) );
        yield 'html remains opaque' => array( array( 'lv' => '<script>x</script>', 'ru' => '<b>y</b>' ) );
        yield 'unicode' => array( array( 'lv' => 'Ābols 😊', 'ru' => 'Привет 🚀' ) );
    }

    #[DataProvider( 'edgeMapProvider' )]
    public function testPureJoinFacadesMatchPreservedLegacyOnEdgeMaps( array $translations ): void {
        $this->assertJoinParity( $translations, 'edge' );
    }

    private function assertJoinParity( array $translations, string $label ): void {
        self::assertSame( qtranxf_legacy_join_b( $translations ), qtranxf_join_b( $translations ), $label . ': bracket' );
        self::assertSame( qtranxf_legacy_join_c( $translations ), qtranxf_join_c( $translations ), $label . ': comment' );
        self::assertSame( qtranxf_legacy_join_s( $translations ), qtranxf_join_s( $translations ), $label . ': curly' );
        self::assertSame(
            qtranxf_legacy_join_b_no_closing( $translations ),
            qtranxf_join_b_no_closing( $translations ),
            $label . ': bracket without closing'
        );
    }

    /** @return array<int,array<string,string>> */
    private function generatedTranslationMaps( int $count, int $seed ): array {
        $state = $seed;
        $next  = static function ( int $max ) use ( &$state ): int {
            $state = (int) ( ( 1103515245 * $state + 12345 ) & 0x7fffffff );

            return $state % $max;
        };
        $languages = array( 'lv', 'ru', 'en', 'zz', 'LV' );
        $texts     = array( '', '0', 'A', 'same', ' ', "\n", 'Ābols', 'Привет', '😊', '<b>HTML</b>', '[:lv]opaque' );
        $result    = array();

        for ( $i = 0; $i < $count; ++$i ) {
            $map       = array();
            $languagesInMap = 1 + $next( count( $languages ) );
            for ( $language = 0; $language < $languagesInMap; ++$language ) {
                $map[ $languages[ $language ] ] = $texts[ $next( count( $texts ) ) ];
            }
            $result[] = $map;
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

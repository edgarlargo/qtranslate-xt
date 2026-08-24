<?php

namespace QTX\Tests\Characterization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyUseFacadeDifferentialTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig = $q_config;
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
    }

    public static function corpusProvider(): iterable {
        $corpus = json_decode( file_get_contents( dirname( __DIR__ ) . '/Fixtures/multilingual-corpus.json' ), true, 512, JSON_THROW_ON_ERROR );
        foreach ( $corpus['cases'] as $case ) {
            yield $case['id'] => array( $case );
        }
    }

    #[DataProvider( 'corpusProvider' )]
    public function testUseFacadesMatchLegacyAcrossCorpusAndPolicies( array $case ): void {
        $raw = $this->materializeRaw( $case );
        foreach ( array( 'lv', 'ru', 'en' ) as $language ) {
            foreach ( array( array( false, false ), array( false, true ), array( true, false ), array( true, true ) ) as $flags ) {
                list( $showAvailable, $showEmpty ) = $flags;
                self::assertSame(
                    qtranxf_legacy_use_language( $language, $raw, $showAvailable, $showEmpty ),
                    qtranxf_use_language( $language, $raw, $showAvailable, $showEmpty ),
                    $case['id'] . ':' . $language . ':' . (int) $showAvailable . ':' . (int) $showEmpty
                );
            }
        }
    }

    public function testUseFacadeMatchesLegacyForGeneratedInputs(): void {
        foreach ( $this->generatedInputs( 250, 0xA333 ) as $index => $raw ) {
            foreach ( array( 'lv', 'ru', 'en' ) as $language ) {
                self::assertSame( qtranxf_legacy_use( $language, $raw ), qtranxf_use( $language, $raw ), 'generated-' . $index );
                self::assertSame( qtranxf_legacy_use( $language, $raw, false, true ), qtranxf_use( $language, $raw, false, true ), 'generated-empty-' . $index );
            }
        }
    }

    public function testUseContentPresentationPoliciesMatchLegacy(): void {
        global $q_config;
        $content   = array( 'lv' => 'LV', 'ru' => 'RU', 'en' => '' );
        $available = array( 'lv' => true, 'ru' => true );

        foreach ( array( false, true ) as $prefix ) {
            foreach ( array( false, true ) as $alternative ) {
                $q_config['show_displayed_language_prefix'] = $prefix;
                $q_config['show_alternative_content']       = $alternative;
                foreach ( array( false, true ) as $showAvailable ) {
                    foreach ( array( false, true ) as $showEmpty ) {
                        self::assertSame(
                            qtranxf_legacy_use_content( 'en', $content, $available, $showAvailable, $showEmpty ),
                            qtranxf_use_content( 'en', $content, $available, $showAvailable, $showEmpty )
                        );
                    }
                }
            }
        }
    }

    public function testRecursiveArrayAndObjectUseMatchesLegacy(): void {
        $array = array( 'title' => '[:lv]A[:ru]Б[:]', 'nested' => array( '[:lv]C[:en]D[:]' ) );
        self::assertSame( qtranxf_legacy_use( 'lv', $array ), qtranxf_use( 'lv', $array ) );

        $legacyObject        = new \stdClass();
        $legacyObject->title = '[:lv]A[:ru]Б[:]';
        $facadeObject        = clone $legacyObject;
        self::assertEquals( qtranxf_legacy_use( 'ru', $legacyObject ), qtranxf_use( 'ru', $facadeObject ) );
    }

    /** @return string[] */
    private function generatedInputs( int $count, int $seed ): array {
        $state = $seed;
        $next  = static function ( int $max ) use ( &$state ): int {
            $state = (int) ( ( 1103515245 * $state + 12345 ) & 0x7fffffff );
            return $state % $max;
        };
        $parts  = array( '', 'plain', ' ', "\n", 'Ābols', 'Привет', '😊', '[:lv]', '[:ru]', '[:en]', '[:zz]', '[:LV]', '[:]', '{:lv}', '{:}', '{::}', '<!--:ru-->', '<!--:-->', '<b>x</b>' );
        $result = array();
        for ( $i = 0; $i < $count; ++$i ) {
            $raw = '';
            for ( $part = 0, $partsInValue = 1 + $next( 8 ); $part < $partsInValue; ++$part ) {
                $raw .= $parts[ $next( count( $parts ) ) ];
            }
            $result[] = $raw;
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

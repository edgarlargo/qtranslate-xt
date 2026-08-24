<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\MultilingualBuilder;
use QTX\Core\Multilingual\MultilingualDetector;
use QTX\Core\Multilingual\MultilingualParser;

final class MultilingualCoreTest extends TestCase {
    public static function corpusProvider(): iterable {
        $path   = dirname( __DIR__ ) . '/Fixtures/multilingual-corpus.json';
        $corpus = json_decode( file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );

        foreach ( $corpus['cases'] as $case ) {
            yield $case['id'] => array( $case );
        }
    }

    #[DataProvider( 'corpusProvider' )]
    public function testNewCoreMatchesLegacyCorpus( array $case ): void {
        $raw      = $this->materializeRaw( $case );
        $expected = $case['expected_php'];
        $parser   = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv' );
        $builder  = new MultilingualBuilder();
        $value    = $parser->parse( $raw );

        self::assertSame( qtranxf_isMultilingual( $raw ), $value->isMultilingual(), $case['id'] . ': detector parity' );
        self::assertSame(
            qtranxf_get_language_blocks( $raw ),
            array_map( static function ( $entry ): string { return $entry->raw(); }, $value->entries() ),
            $case['id'] . ': ordered entry parity'
        );
        self::assertSame( qtranxf_split( $raw ), $value->translations(), $case['id'] . ': split parity' );
        self::assertSame(
            qtranxf_split_languages( qtranxf_get_language_blocks( $raw ) ),
            $value->encodedTranslations(),
            $case['id'] . ': encoded split parity'
        );

        $legacyAvailable = qtranxf_getAvailableLanguages( $raw );
        self::assertSame( $legacyAvailable === false ? array() : $legacyAvailable, $value->availableLanguages(), $case['id'] . ': availability parity' );
        self::assertSame( $raw, $builder->build( $value ), $case['id'] . ': unchanged lossless rebuild' );

        if ( isset( $case['round_trip'] ) ) {
            self::assertSame( $case['round_trip']['join_b'], $builder->build( $value, 'bracket', true ), $case['id'] . ': canonical bracket' );
            self::assertSame( $case['round_trip']['join_c'], $builder->build( $value, 'comment', true ), $case['id'] . ': canonical comment' );
            self::assertSame( $case['round_trip']['join_s'], $builder->build( $value, 'curly', true ), $case['id'] . ': canonical curly' );
        }

        if ( isset( $expected['split'] ) ) {
            self::assertSame( $expected['split'], $value->translations(), $case['id'] . ': frozen expected split' );
        }
    }

    public function testDetectorHandlesNullAndUsesCheapMarkerRecognition(): void {
        $detector = new MultilingualDetector();

        self::assertFalse( $detector->isMultilingual( null ) );
        self::assertFalse( $detector->isMultilingual( '' ) );
        self::assertFalse( $detector->isMultilingual( 'plain [:l] text' ) );
        self::assertTrue( $detector->isMultilingual( 'prefix [:LV]value' ) );
    }

    public function testValuePreservesDuplicatesCaseMalformedFragmentsAndDiagnostics(): void {
        $parser = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv' );
        $value  = $parser->parse( 'prefix [:LV]A[:LV]B{::}' );

        self::assertSame( 'prefix [:LV]A[:LV]B{::}', $value->raw() );
        self::assertSame( array( 'prefix ', '[:LV]', 'A', '[:LV]', 'B{::}' ), array_map( static function ( $entry ): string { return $entry->raw(); }, $value->entries() ) );
        self::assertSame( 'AB{::}', $value->translations()['LV'] );
        self::assertContains( 'missing-closing-marker', $value->diagnostics() );
        self::assertFalse( $value->isChanged() );
    }

    public function testParserTreatsPotentiallyActiveContentAsOpaqueText(): void {
        $raw    = '[:lv]<script>alert(1)</script>[:ru]O:8:"Example":0:{}[:]';
        $parser = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv' );
        $value  = $parser->parse( $raw );

        self::assertSame( '<script>alert(1)</script>', $value->translations()['lv'] );
        self::assertSame( 'O:8:"Example":0:{}', $value->translations()['ru'] );
        self::assertSame( $raw, ( new MultilingualBuilder() )->build( $value ) );
    }

    private function materializeRaw( array $case ): string {
        if ( isset( $case['raw'] ) ) {
            return $case['raw'];
        }

        $generator = $case['raw_generator'];

        return $generator['prefix'] . str_repeat( $generator['repeat'], $generator['count'] ) . $generator['suffix'];
    }
}

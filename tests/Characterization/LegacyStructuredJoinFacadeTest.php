<?php

namespace QTX\Tests\Characterization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyStructuredJoinFacadeTest extends TestCase {
    public static function terminatingSeparatorProvider(): iterable {
        yield 'empty' => array( array(), '/(, )/' );
        yield 'all same' => array( array( 'lv' => 'Same, value', 'ru' => 'Same, value' ), '/(, )/' );
        yield 'different without separator' => array( array( 'lv' => 'A', 'ru' => 'Б' ), '/(, )/' );
        yield 'all zero strings' => array( array( 'lv' => '0', 'ru' => '0' ), '/(, )/' );
    }

    #[DataProvider( 'terminatingSeparatorProvider' )]
    public function testSeparatorFacadeMatchesEveryTerminatingLegacyBranch( array $translations, string $pattern ): void {
        self::assertSame(
            qtranxf_legacy_join_byseparator( $translations, $pattern ),
            qtranxf_join_byseparator( $translations, $pattern )
        );
    }

    public function testSeparatorFacadeAdvancesAcrossDifferingSegmentsInsteadOfLoopingForever(): void {
        self::assertSame(
            '[:lv]B[:ru]Б[:], [:lv]C[:ru]В[:], ',
            qtranxf_join_byseparator( array( 'lv' => 'A, B, C', 'ru' => 'А, Б, В' ), '/(, )/' )
        );
        self::assertSame(
            '[:lv]second[:ru]второй[:]' . "\n",
            qtranxf_join_byseparator( array( 'lv' => "first\nsecond", 'ru' => "первый\nвторой" ), '/(\n)/' )
        );
    }

    public function testLineFacadeMatchesLegacyForGeneratedMaps(): void {
        foreach ( $this->generatedLineMaps( 250, 0xA32B ) as $index => $translations ) {
            self::assertSame(
                qtranxf_legacy_join_byline( $translations ),
                qtranxf_join_byline( $translations ),
                'generated-line-' . $index
            );
        }
    }

    /** @return array<int,array<string,string>> */
    private function generatedLineMaps( int $count, int $seed ): array {
        $state = $seed;
        $next  = static function ( int $max ) use ( &$state ): int {
            $state = (int) ( ( 1103515245 * $state + 12345 ) & 0x7fffffff );

            return $state % $max;
        };
        $values = array( '', 'A', '0', "A\nB", "A\r\nB", "\nA", "A\n", "Ābols\nKoks", "Привет\nМир", '<b>A</b>' );
        $result = array();
        for ( $i = 0; $i < $count; ++$i ) {
            $result[] = array(
                'lv' => $values[ $next( count( $values ) ) ],
                'ru' => $values[ $next( count( $values ) ) ],
                'en' => $values[ $next( count( $values ) ) ],
            );
        }

        return $result;
    }
}

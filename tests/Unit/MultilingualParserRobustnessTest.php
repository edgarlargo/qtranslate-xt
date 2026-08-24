<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\MultilingualBuilder;
use QTX\Core\Multilingual\MultilingualParser;

final class MultilingualParserRobustnessTest extends TestCase {
    public function testRequestLocalCacheIsBoundedAndCanBeDisabled(): void {
        $parser = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv', '[a-z]{2,3}', 1 );
        $first  = $parser->parse( '[:lv]A[:]' );
        self::assertSame( $first, $parser->parse( '[:lv]A[:]' ) );

        $parser->parse( '[:lv]B[:]' );
        self::assertNotSame( $first, $parser->parse( '[:lv]A[:]' ) );

        $uncached = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv', '[a-z]{2,3}', 0 );
        self::assertNotSame( $uncached->parse( '[:lv]A[:]' ), $uncached->parse( '[:lv]A[:]' ) );
    }

    public function testBlockCachePreservesExplicitBoundaries(): void {
        $parser = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv' );
        $blocks = array( '[:lv]', '', '[:ru]', 'Б', '[:]' );
        $value  = $parser->parseBlocks( $blocks );

        self::assertSame( $value, $parser->parseBlocks( $blocks ) );
        self::assertSame( array( 'lv' => '', 'ru' => 'Б' ), $value->encodedTranslations() );
    }

    public function testAdversarialOpaqueAndLargeInputsRemainLossless(): void {
        $parser  = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv' );
        $builder = new MultilingualBuilder();
        $inputs  = array(
            "\0[:lv]<script>alert(1)</script>\0[:ru]O:8:\"X\":0:{}[:]\0",
            str_repeat( '[:lv][:ru]{::}<!--:-->', 2048 ),
            '[:lv]' . str_repeat( 'Ā😊Ж', 100000 ) . '[:]',
            str_repeat( '[', 100000 ) . str_repeat( ']', 100000 ),
        );

        foreach ( $inputs as $input ) {
            $value = $parser->parse( $input );
            self::assertSame( $input, $value->raw() );
            self::assertSame( $input, $builder->build( $value ) );
        }
    }

    public function testNegativeCacheCapacityIsRejected(): void {
        $this->expectException( \InvalidArgumentException::class );
        new MultilingualParser( array( 'lv' ), 'lv', '[a-z]{2,3}', -1 );
    }
}

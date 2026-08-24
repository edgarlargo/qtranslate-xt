<?php

require_once dirname( __DIR__ ) . '/bootstrap.php';

use QTX\Core\Multilingual\MultilingualDetector;
use QTX\Core\Multilingual\MultilingualParser;

$iterations = isset( $argv[1] ) ? max( 1, (int) $argv[1] ) : 10000;
$raw        = 'Prefix [:lv]<strong>Ābols</strong>[:ru]<em>Привет</em>[:en]Hello[:] tail';
$parser     = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv', '[a-z]{2,3}', 0 );
$cachedParser = new MultilingualParser( array( 'lv', 'ru', 'en' ), 'lv' );
$detector   = new MultilingualDetector();

$measure = static function ( callable $callback ) use ( $iterations ): float {
    $start = hrtime( true );
    for ( $i = 0; $i < $iterations; ++$i ) {
        $callback();
    }

    return ( hrtime( true ) - $start ) / 1000000;
};

$results = array(
    'iterations'       => $iterations,
    'legacy_detect_ms' => $measure( static function () use ( $raw ): void { qtranxf_legacy_isMultilingual( $raw ); } ),
    'core_detect_ms'   => $measure( static function () use ( $detector, $raw ): void { $detector->isMultilingual( $raw ); } ),
    'legacy_parse_ms'  => $measure( static function () use ( $raw ): void { qtranxf_legacy_split( $raw ); } ),
    'core_cold_parse_ms' => $measure( static function () use ( $parser, $raw ): void { $parser->parse( $raw ); } ),
    'core_cached_parse_ms' => $measure( static function () use ( $cachedParser, $raw ): void { $cachedParser->parse( $raw ); } ),
);

echo json_encode( $results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;

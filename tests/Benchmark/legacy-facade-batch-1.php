<?php

require_once dirname( __DIR__ ) . '/bootstrap.php';

$cases = array(
    'plain-short'        => array( 'plain title', 5000 ),
    'multilingual-title' => array( '[:lv]Sveiki[:ru]Привет[:en]Hello[:]', 5000 ),
    'medium-content'     => array( 'prefix [:lv]' . str_repeat( 'Latviešu ', 128 ) . '[:ru]' . str_repeat( 'Русский ', 128 ) . '[:] tail', 1000 ),
    '64-kib-content'     => array( '[:lv]' . str_repeat( 'x', 65536 ) . '[:]', 100 ),
    'malformed-content'  => array( 'prefix [:LV][:ru]Привет{::} [:l] tail[:]', 5000 ),
);

$measure = static function ( callable $callback, int $iterations ): float {
    $start = hrtime( true );
    for ( $i = 0; $i < $iterations; ++$i ) {
        $callback();
    }

    return ( hrtime( true ) - $start ) / 1000000;
};

$results = array();
foreach ( $cases as $name => $case ) {
    list( $raw, $iterations ) = $case;
    $legacyBlocks = qtranxf_legacy_get_language_blocks( $raw );
    $facadeBlocks = qtranxf_get_language_blocks( $raw );

    $legacy = static function () use ( $raw, $legacyBlocks ): void {
        qtranxf_legacy_isMultilingual( $raw );
        qtranxf_legacy_get_language_blocks( $raw );
        qtranxf_legacy_split( $raw );
        $found = array();
        qtranxf_legacy_split_blocks( $legacyBlocks, $found );
        qtranxf_legacy_split_languages( $legacyBlocks );
        qtranxf_legacy_getAvailableLanguages( $raw );
    };
    $facade = static function () use ( $raw, $facadeBlocks ): void {
        qtranxf_isMultilingual( $raw );
        qtranxf_get_language_blocks( $raw );
        qtranxf_split( $raw );
        $found = array();
        qtranxf_split_blocks( $facadeBlocks, $found );
        qtranxf_split_languages( $facadeBlocks );
        qtranxf_getAvailableLanguages( $raw );
    };

    $legacyMs = $measure( $legacy, $iterations );
    $facadeMs = $measure( $facade, $iterations );
    $results[ $name ] = array(
        'iterations' => $iterations,
        'legacy_ms'  => round( $legacyMs, 3 ),
        'facade_ms'  => round( $facadeMs, 3 ),
        'ratio'      => round( $facadeMs / $legacyMs, 2 ),
    );
}

echo json_encode( $results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;

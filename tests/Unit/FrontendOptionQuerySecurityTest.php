<?php

use PHPUnit\Framework\TestCase;

final class FrontendOptionQuerySecurityTest extends TestCase {
    public function testOptionScanUsesPreparedPortableLikeQueries(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/frontend.php' );

        self::assertStringContainsString( '$wpdb->esc_like( $marker )', $source );
        self::assertStringContainsString( '$wpdb->prepare(', $source );
        self::assertStringNotContainsString( "%![:__!]%", $source );
        self::assertStringNotContainsString( "option_name LIKE \"' . \$nm", $source );
    }
}

<?php

use PHPUnit\Framework\TestCase;

final class SlugSaveSecurityContractTest extends TestCase {
    public function testPostSlugMutationFailsClosedWithoutItsNonce(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/slugs/admin.php' );

        self::assertStringContainsString( 'if ( ! $has_slug_input )', $source );
        self::assertStringContainsString( "is_string( \$_POST['qts_nonce'] )", $source );
        self::assertStringContainsString( "! wp_verify_nonce( \$nonce, 'qts_nonce' )", $source );
        self::assertStringNotContainsString(
            "isset( \$_POST['qts_nonce'] ) && ! wp_verify_nonce",
            $source
        );
    }
}

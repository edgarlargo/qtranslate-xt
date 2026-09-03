<?php

use PHPUnit\Framework\TestCase;

final class AdminDebugAjaxSecurityContractTest extends TestCase {
    public function testDebugEndpointRequiresCapabilityAndAjaxNonce(): void {
        $handler = file_get_contents( dirname( __DIR__, 2 ) . '/src/admin/admin_utils.php' );
        $enqueue = file_get_contents( dirname( __DIR__, 2 ) . '/src/admin/admin.php' );
        $client  = file_get_contents( dirname( __DIR__, 2 ) . '/js/options.js' );

        self::assertStringContainsString( "current_user_can( 'manage_options' )", $handler );
        self::assertStringContainsString( "check_ajax_referer( 'qtx_admin_debug_info' )", $handler );
        self::assertStringContainsString( 'wp_send_json( $info )', $handler );
        self::assertStringContainsString( "'debugNonce' => wp_create_nonce( 'qtx_admin_debug_info' )", $enqueue );
        self::assertStringContainsString( '_ajax_nonce: qtxAdminOptions.debugNonce', $client );
    }
}

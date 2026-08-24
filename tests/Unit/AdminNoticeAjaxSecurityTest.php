<?php

use PHPUnit\Framework\TestCase;

final class AdminNoticeAjaxSecurityTest extends TestCase {
    public function testDismissEndpointAndCallerRequireAuthorizationAndNonce(): void {
        $root = dirname( __DIR__, 2 );
        $php = file_get_contents( $root . '/src/admin/admin_notices.php' );
        $js = file_get_contents( $root . '/js/notices.js' );

        self::assertStringContainsString( "current_user_can( 'manage_options' )", $php );
        self::assertStringContainsString( "check_ajax_referer( 'qtranslate_admin_notice', 'nonce' )", $php );
        self::assertStringContainsString( "wp_create_nonce( 'qtranslate_admin_notice' )", $php );
        self::assertStringContainsString( "sanitize_key( wp_unslash( \$_POST['notice_id'] ) )", $php );
        self::assertStringContainsString( 'nonce: qtxAdminNotices.nonce', $js );
    }
}

<?php

use PHPUnit\Framework\TestCase;

final class WooCommerceOrderLanguageCrudContractTest extends TestCase {
    public function test_front_module_uses_order_crud_for_hpos_compatibility(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/woo-commerce/front.php' );

        self::assertStringContainsString( "add_action( 'woocommerce_checkout_order_created'", $source );
        self::assertStringContainsString( "->update_meta_data( '_user_language'", $source );
        self::assertStringContainsString( '->save_meta_data()', $source );
        self::assertStringNotContainsString( "add_post_meta( \$order_id, '_user_language'", $source );
    }

    public function test_admin_module_reads_order_language_through_crud(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/woo-commerce/admin.php' );

        self::assertStringContainsString( 'wc_get_order( $order_id )', $source );
        self::assertStringContainsString( "->get_meta( '_user_language', true )", $source );
        self::assertStringNotContainsString( "get_post_meta( \$order_id, '_user_language'", $source );
    }
}

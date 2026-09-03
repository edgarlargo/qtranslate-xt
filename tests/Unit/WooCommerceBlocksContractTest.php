<?php

use PHPUnit\Framework\TestCase;

final class WooCommerceBlocksContractTest extends TestCase {
    public function testStoreApiRouteLoadsFrontendPresentationFiltersOnlyForStoreNamespace(): void {
        $loader = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/woo-commerce/loader.php' );

        self::assertStringContainsString( "add_filter( 'rest_pre_dispatch', 'qtranxf_wc_prepare_store_api_request', 5, 3 )", $loader );
        self::assertStringContainsString( "strpos( \$route, '/wc/store/' ) !== 0", $loader );
        self::assertStringContainsString( "require_once __DIR__ . '/front.php'", $loader );
        self::assertStringNotContainsString( "'/wc/v3/'", $loader );
    }

    public function testBlockAdapterIsTextOnlyAndCarriesExplicitLanguageConfiguration(): void {
        $front = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/woo-commerce/front.php' );
        $script = file_get_contents( dirname( __DIR__, 2 ) . '/js/woocommerce-blocks/index.js' );

        self::assertStringContainsString( "plugins_url( 'dist/woocommerce-blocks.js'", $front );
        self::assertStringContainsString( "array( 'wp-hooks' )", $front );
        self::assertStringContainsString( "'languageCodePattern' => QTX_LANG_CODE_FORMAT", $front );
        self::assertStringContainsString( 'node.nodeValue = translated', $script );
        self::assertStringNotContainsString( 'innerHTML', $script );
    }
}

<?php

use PHPUnit\Framework\TestCase;
use QTX\Integration\WooCommerce\WooCommerceBlocksAdapter;

final class WooCommerceBlocksContractTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig = $q_config;
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
        unset( $GLOBALS['qtx_test_post_id'], $GLOBALS['qtx_test_wc_page_ids'] );
    }

    public function testStoreApiRouteLoadsFrontendPresentationFiltersOnlyForStoreNamespace(): void {
        $activated = 0;
        $adapter = new WooCommerceBlocksAdapter( static function () use ( &$activated ): void {
            ++$activated;
        } );
        $request = static function ( string $route ): object {
            return new class( $route ) {
                private string $route;

                public function __construct( string $route ) {
                    $this->route = $route;
                }

                public function get_route(): string {
                    return $this->route;
                }
            };
        };

        self::assertSame( 'response', $adapter->prepareStoreApiRequest( 'response', null, $request( '/wc/v3/products' ) ) );
        self::assertSame( 0, $activated );
        self::assertSame( 'response', $adapter->prepareStoreApiRequest( 'response', null, $request( '/wc/store/v1/cart' ) ) );
        self::assertSame( 1, $activated );
    }

    public function testConfiguredSystemPagesUseStructuralDefaultContentWithoutUnavailableNotice(): void {
        global $q_config;
        $q_config['language'] = 'ru';
        $GLOBALS['qtx_test_wc_page_ids'] = array(
            'cart'      => 101,
            'checkout'  => 102,
            'myaccount' => 103,
        );
        $raw = '[:en]<!-- wp:woocommerce/cart /--><p>QTX_CART_STRUCTURE</p>[:]';
        $adapter = new WooCommerceBlocksAdapter();

        foreach ( array( 101, 102, 103 ) as $postId ) {
            $GLOBALS['qtx_test_post_id'] = $postId;
            self::assertSame(
                '<!-- wp:woocommerce/cart /--><p>QTX_CART_STRUCTURE</p>',
                $adapter->translateSystemPageContent( $raw )
            );
        }

        $GLOBALS['qtx_test_post_id'] = 999;
        self::assertSame( $raw, $adapter->translateSystemPageContent( $raw ) );
        self::assertSame( array( 'not', 'text' ), $adapter->translateSystemPageContent( array( 'not', 'text' ) ) );

        $cart = (object) array( 'ID' => 101, 'post_title' => '[:en]Cart[:]', 'post_content' => $raw );
        $ordinary = (object) array( 'ID' => 999, 'post_content' => $raw );
        self::assertSame(
            array( $cart, $ordinary ),
            $adapter->translateSystemPagePosts( array( $cart, $ordinary ), null )
        );
        self::assertSame( '<!-- wp:woocommerce/cart /--><p>QTX_CART_STRUCTURE</p>', $cart->post_content );
        self::assertSame( '[:en]Cart[:]', $cart->post_title );
        self::assertSame( $raw, $ordinary->post_content );
    }

    public function testCoreRegistrationIsIndependentFromLegacyWooModuleState(): void {
        $root = dirname( __DIR__, 2 );
        $source = file_get_contents( $root . '/src/Integration/WooCommerce/WooCommerceBlocksAdapter.php' );
        $init = file_get_contents( $root . '/src/init.php' );

        self::assertStringContainsString( "add_filter( 'rest_pre_dispatch', array( \$this, 'prepareStoreApiRequest' ), 5, 3 )", $source );
        self::assertStringContainsString( "add_filter( 'the_posts', array( \$this, 'translateSystemPagePosts' ), 4, 2 )", $source );
        self::assertStringContainsString( "add_filter( 'the_content', array( \$this, 'translateSystemPageContent' ), 99 )", $source );
        self::assertStringContainsString( "array( 'cart', 'checkout', 'myaccount' )", $source );
        self::assertStringContainsString( "strpos( \$route, '/wc/store/' ) !== 0", $source );
        self::assertStringContainsString( "QTRANSLATE_DIR . '/src/modules/woo-commerce/front.php'", $source );
        self::assertStringContainsString( 'qtranxf_wc_add_filters_front();', $source );
        self::assertStringContainsString( 'new \\QTX\\Integration\\WooCommerce\\WooCommerceBlocksAdapter()', $init );
        self::assertStringNotContainsString( 'QTX_OPTIONS_MODULES_STATE', $source );
        self::assertStringNotContainsString( 'active_plugins', $source );
        self::assertStringNotContainsString( "'/wc/v3/'", $source );
    }

    public function testBlockAdapterIsTextOnlyAndCarriesExplicitLanguageConfiguration(): void {
        $adapter = file_get_contents( dirname( __DIR__, 2 ) . '/src/Integration/WooCommerce/WooCommerceBlocksAdapter.php' );
        $script = file_get_contents( dirname( __DIR__, 2 ) . '/js/woocommerce-blocks/index.js' );

        self::assertStringContainsString( "plugins_url( 'dist/woocommerce-blocks.js'", $adapter );
        self::assertStringContainsString( "array( 'wp-hooks' )", $adapter );
        self::assertStringContainsString( "'languageCodePattern' => QTX_LANG_CODE_FORMAT", $adapter );
        self::assertStringContainsString( 'node.nodeValue = translated', $script );
        self::assertStringNotContainsString( 'innerHTML', $script );
    }
}

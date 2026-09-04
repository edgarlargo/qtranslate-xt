<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    fwrite( STDERR, "Run through WP-CLI.\n" );
    exit( 1 );
}

$fixtures = array(
    'cart'      => array( 'woocommerce/cart', 'QTX_WOO_SYSTEM_CART' ),
    'checkout'  => array( 'woocommerce/checkout', 'QTX_WOO_SYSTEM_CHECKOUT' ),
    'myaccount' => array( null, 'QTX_WOO_SYSTEM_MYACCOUNT' ),
);

foreach ( $fixtures as $page => $fixture ) {
    $pageId = wc_get_page_id( $page );
    $raw    = $pageId > 0 ? get_post_field( 'post_content', $pageId, 'raw' ) : '';
    if ( ! is_string( $raw ) || $raw === '' ) {
        WP_CLI::error( "WooCommerce {$page} page content is unavailable." );
    }
    if ( $fixture[0] !== null && ! has_block( $fixture[0], $raw ) ) {
        WP_CLI::error( "WooCommerce {$page} block is unavailable." );
    }

    $marker = '<!-- wp:paragraph --><p>' . $fixture[1] . '</p><!-- /wp:paragraph -->';
    $result = wp_update_post(
        array(
            'ID'           => $pageId,
            'post_content' => '[:en]' . $raw . $marker . '[:]',
        ),
        true
    );
    if ( is_wp_error( $result ) ) {
        WP_CLI::error( $result->get_error_message() );
    }
}

$product = new WC_Product_Simple();
$product->set_name( 'QTX system-page fixture product' );
$product->set_status( 'publish' );
$product->set_regular_price( '1.00' );
$productId = $product->save();
if ( $productId <= 0 ) {
    WP_CLI::error( 'WooCommerce system-page fixture product was not created.' );
}
update_option( 'qtx_system_page_fixture_product_id', $productId, false );

WP_CLI::success( 'WooCommerce system-page fallback fixtures prepared.' );

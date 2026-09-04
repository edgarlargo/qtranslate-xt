<?php
/**
 * Prove Cart/Checkout Blocks translate product names when the legacy Woo module
 * state is inactive. The core adapter must bootstrap only the Store API path.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    throw new RuntimeException( 'This regression must run through WP-CLI.' );
}
if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
    WP_CLI::error( 'WooCommerce is not active.' );
}
if ( QTX_Module_Loader::is_module_active( 'woo-commerce' ) ) {
    WP_CLI::error( 'WooCommerce legacy module must be inactive for this regression.' );
}

global $q_config, $wpdb;
$q_config['language']                    = 'ru';
$q_config['default_language']            = 'en';
$q_config['enabled_languages']           = array( 'lv', 'ru', 'en' );
$q_config['url_info']['language']        = 'ru';
$q_config['url_info']['doing_front_end'] = true;

$raw = '[:en]Set of 250 mockups[:lv]Viss 250 maketu komplekts[:ru]Весь набор 250 мокапов[:]';
$product = new WC_Product_Simple();
$product->set_name( 'Set of 250 mockups' );
$product->set_status( 'publish' );
$product->set_regular_price( '10.00' );
$product->set_price( '10.00' );
$product_id = $product->save();
$wpdb->update(
    $wpdb->posts,
    array( 'post_title' => $raw ),
    array( 'ID' => $product_id ),
    array( '%s' ),
    array( '%d' )
);
clean_post_cache( $product_id );
wc_delete_product_transients( $product_id );

if ( ! WC()->cart ) {
    WC()->cart = new WC_Cart();
}
WC()->cart->empty_cart();
$cart_key = WC()->cart->add_to_cart( $product_id, 2 );
if ( ! is_string( $cart_key ) ) {
    wp_delete_post( $product_id, true );
    WP_CLI::error( 'Core Store API fixture could not be added to cart.' );
}

$response = rest_do_request( new WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );
$data = $response->get_data();
$names = is_array( $data ) && isset( $data['items'] )
    ? array_column( $data['items'], 'name' )
    : array();

WC()->cart->empty_cart();
wp_delete_post( $product_id, true );

if ( $response->get_status() !== 200 || ! in_array( 'Весь набор 250 мокапов', $names, true ) ) {
    WP_CLI::error( 'Core Store API bootstrap exposed a raw or incorrect product title.' );
}
foreach ( $names as $name ) {
    if ( is_string( $name ) && strpos( $name, '[:' ) !== false ) {
        WP_CLI::error( 'Core Store API bootstrap returned multilingual markers.' );
    }
}

WP_CLI::success( 'Core Store API bootstrap passed with the legacy Woo module inactive.' );

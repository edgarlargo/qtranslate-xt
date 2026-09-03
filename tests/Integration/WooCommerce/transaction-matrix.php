<?php
/**
 * Disposable WooCommerce integration matrix. Run only through WP-CLI in the CI lab.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    throw new RuntimeException( 'This integration matrix must run through WP-CLI.' );
}
if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
    throw new RuntimeException( 'WooCommerce is not active.' );
}
if ( ! function_exists( 'qtranxf_wc_add_filters_front' ) ) {
    require_once QTRANSLATE_DIR . '/src/modules/woo-commerce/front.php';
}
qtranxf_wc_add_filters_front();

$failures = array();
$checks   = 0;
$check    = static function ( $condition, string $message ) use ( &$failures, &$checks ): void {
    ++$checks;
    if ( ! $condition ) {
        $failures[] = $message;
        WP_CLI::warning( $message );
    }
};
$ml = static function ( string $lv, string $ru, string $en ): string {
    return '[:lv]' . $lv . '[:ru]' . $ru . '[:en]' . $en . '[:]';
};
$set_language = static function ( string $language ): void {
    global $q_config;
    $q_config['language']                  = $language;
    $q_config['default_language']          = 'en';
    $q_config['enabled_languages']         = array( 'lv', 'ru', 'en' );
    $q_config['url_info']['language']      = $language;
    $q_config['url_info']['doing_front_end'] = true;
};

update_option( 'woocommerce_currency', 'EUR' );
update_option( 'woocommerce_calc_taxes', 'no' );
update_option( 'woocommerce_cod_settings', array( 'enabled' => 'yes', 'title' => $ml( 'Apmaksa saņemot', 'Оплата при получении', 'Cash on delivery' ) ) );

// Never permit an external message. Every Woo email is captured at wp_mail's boundary.
$mail = array();
add_filter( 'pre_wp_mail', static function ( $return, array $attributes ) use ( &$mail ) {
    $mail[] = $attributes;
    return true;
}, PHP_INT_MAX, 2 );

$category = wp_insert_term( $ml( 'Krūzes', 'Кружки', 'Mugs' ), 'product_cat' );
$check( ! is_wp_error( $category ), 'Multilingual product category creation failed.' );
if ( is_wp_error( $category ) ) {
    WP_CLI::error( 'Product category fixture failed: ' . $category->get_error_message() );
}

$attribute_id = wc_create_attribute( array(
    'name' => $ml( 'Izmērs', 'Размер', 'Size' ),
    'slug' => 'size',
    'type' => 'select',
) );
$check( ! is_wp_error( $attribute_id ) && $attribute_id > 0, 'Global attribute creation failed.' );
if ( is_wp_error( $attribute_id ) || $attribute_id <= 0 ) {
    $message = is_wp_error( $attribute_id ) ? $attribute_id->get_error_message() : 'No attribute ID was returned.';
    WP_CLI::error( 'Global attribute fixture failed: ' . $message );
}
delete_transient( 'wc_attribute_taxonomies' );
if ( class_exists( 'WC_Cache_Helper' ) ) {
    WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
}
// WooCommerce returns early when product_type already exists, so force its
// documented taxonomy refresh path after adding the global test attribute.
unregister_taxonomy( 'product_type' );
WC_Post_Types::register_taxonomies();
$small = wp_insert_term( $ml( 'Mazs', 'Малый', 'Small' ), 'pa_size', array( 'slug' => 'small' ) );
$large = wp_insert_term( $ml( 'Liels', 'Большой', 'Large' ), 'pa_size', array( 'slug' => 'large' ) );
$check( ! is_wp_error( $small ) && ! is_wp_error( $large ), 'Variation terms creation failed.' );
if ( is_wp_error( $small ) || is_wp_error( $large ) ) {
    $messages = array();
    if ( is_wp_error( $small ) ) {
        $messages[] = 'small: ' . $small->get_error_message();
    }
    if ( is_wp_error( $large ) ) {
        $messages[] = 'large: ' . $large->get_error_message();
    }
    WP_CLI::error( 'Variation term fixtures failed: ' . implode( '; ', $messages ) );
}

$simple = new WC_Product_Simple();
$simple->set_name( $ml( 'Vienkāršā krūze', 'Простая кружка', 'Simple mug' ) );
$simple->set_description( $ml( 'Pilns apraksts', 'Полное описание', 'Full description' ) );
$simple->set_short_description( $ml( 'Īss apraksts', 'Краткое описание', 'Short description' ) );
$simple->set_sku( 'QTX-SIMPLE-001' );
$simple->set_regular_price( '12.50' );
$simple->set_manage_stock( true );
$simple->set_stock_quantity( 17 );
$simple->set_category_ids( array( (int) $category['term_id'] ) );
$simple_id = $simple->save();
update_post_meta( $simple_id, '_qtx_matrix_serialized_fixture', array( 'technical' => array( 'key' => 'value', 'number' => 42 ) ) );

$variable = new WC_Product_Variable();
$variable->set_name( $ml( 'Maināmā krūze', 'Вариативная кружка', 'Variable mug' ) );
$variable->set_sku( 'QTX-VARIABLE-001' );
$attribute = new WC_Product_Attribute();
$attribute->set_id( (int) $attribute_id );
$attribute->set_name( 'pa_size' );
$attribute->set_options( array( (int) $small['term_id'], (int) $large['term_id'] ) );
$attribute->set_visible( true );
$attribute->set_variation( true );
$variable->set_attributes( array( $attribute ) );
$variable_id = $variable->save();
$variation = new WC_Product_Variation();
$variation->set_parent_id( $variable_id );
$variation->set_attributes( array( 'pa_size' => 'small' ) );
$variation->set_sku( 'QTX-VAR-SMALL-001' );
$variation->set_regular_price( '15.75' );
$variation->set_manage_stock( true );
$variation->set_stock_quantity( 9 );
$variation_id = $variation->save();
WC_Product_Variable::sync( $variable_id );

$baseline = array(
    'simple_id' => $simple_id, 'variable_id' => $variable_id, 'variation_id' => $variation_id,
    'simple_sku' => get_post_meta( $simple_id, '_sku', true ),
    'variation_sku' => get_post_meta( $variation_id, '_sku', true ),
    'simple_price' => get_post_meta( $simple_id, '_price', true ),
    'variation_price' => get_post_meta( $variation_id, '_price', true ),
    'simple_stock' => get_post_meta( $simple_id, '_stock', true ),
    'variation_stock' => get_post_meta( $variation_id, '_stock', true ),
);

if ( ! WC()->session ) {
    WC()->session = new WC_Session_Handler();
    WC()->session->init();
}
if ( ! WC()->customer ) {
    WC()->customer = new WC_Customer( 0, true );
}
if ( ! WC()->cart ) {
    WC()->cart = new WC_Cart();
}

$language_names = array( 'lv' => 'Vienkāršā krūze', 'ru' => 'Простая кружка', 'en' => 'Simple mug' );
$orders = array();
$snapshots = array();
foreach ( $language_names as $language => $expected_name ) {
    $set_language( $language );
    clean_post_cache( $simple_id );
    $product = wc_get_product( $simple_id );
    $check( $product->get_name() === $expected_name, strtoupper( $language ) . ' product title was not translated.' );
    $check( $product->get_sku() === 'QTX-SIMPLE-001' && $product->get_price() === '12.50', strtoupper( $language ) . ' technical product fields changed.' );

    WC()->cart->empty_cart();
    $simple_key = WC()->cart->add_to_cart( $simple_id, 2 );
    $variation_key = WC()->cart->add_to_cart( $variable_id, 1, $variation_id, array( 'attribute_pa_size' => 'small' ) );
    WC()->cart->calculate_totals();
    $check( is_string( $simple_key ) && is_string( $variation_key ) && WC()->cart->get_cart_contents_count() === 3, strtoupper( $language ) . ' cart/AJAX add path failed.' );
    $check( (float) WC()->cart->get_total( 'edit' ) === 40.75, strtoupper( $language ) . ' cart total changed.' );
    $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array( 'div.widget_shopping_cart_content' => '<div>' . WC()->cart->get_cart_contents_count() . '</div>' ) );
    $check( isset( $fragments['div.widget_shopping_cart_content'] ), strtoupper( $language ) . ' cart fragment path failed.' );

    $mail_start = count( $mail );
    $checkout = WC_Checkout::instance();
    $order_id = $checkout->create_order( array(
        'billing_first_name' => 'QTX', 'billing_last_name' => strtoupper( $language ),
        'billing_email' => 'nobody@example.test', 'billing_address_1' => 'Test 1',
        'billing_city' => 'Riga', 'billing_country' => 'LV', 'payment_method' => 'cod',
    ) );
    $check( ! is_wp_error( $order_id ) && $order_id > 0, strtoupper( $language ) . ' checkout/order creation failed.' );
    if ( is_wp_error( $order_id ) ) {
        continue;
    }
    $order = wc_get_order( $order_id );
    $order->set_payment_method( 'cod' );
    $order->calculate_totals();
    $order->save();
    $order->update_meta_data( '_user_language', $language );
    $order->save_meta_data();
    $orders[ $language ] = $order_id;
    $check( (float) $order->get_total() === 40.75 && $order->get_payment_method() === 'cod', strtoupper( $language ) . ' order total/payment identifier changed.' );
    $check( count( $order->get_items() ) === 2, strtoupper( $language ) . ' product/variation snapshot missing.' );
    $snapshots[ $language ] = array_map( static function ( WC_Order_Item_Product $item ): array {
        return array( $item->get_product_id(), $item->get_variation_id(), $item->get_name(), $item->get_quantity(), $item->get_total() );
    }, $order->get_items() );
    $order->update_status( 'processing' );
    $order->update_status( 'completed' );
    $check( $order->get_status() === 'completed', strtoupper( $language ) . ' completed status transition failed.' );
    $language_mail = array_slice( $mail, $mail_start );
    $check( count( $language_mail ) > 0, strtoupper( $language ) . ' processing/completed customer email was not captured.' );
    $mail_text = implode( "\n", array_map( static function ( array $message ): string {
        return (string) ( $message['subject'] ?? '' ) . "\n" . (string) ( $message['message'] ?? '' );
    }, $language_mail ) );
    $check( str_contains( $mail_text, $expected_name ), strtoupper( $language ) . ' email did not use the customer product language.' );
    $check( str_contains( $mail_text, (string) $order_id ), strtoupper( $language ) . ' email did not retain the order identifier.' );
}

// Cancel and refund dedicated orders without an external payment transaction.
$cancelled = wc_create_order();
$cancelled->add_product( wc_get_product( $simple_id ), 1 );
$cancelled->set_payment_method( 'cod' );
$cancelled->calculate_totals();
$cancelled->save();
$cancelled->update_meta_data( '_user_language', 'lv' );
$cancelled->save_meta_data();
$cancelled->update_status( 'cancelled' );
$check( $cancelled->get_status() === 'cancelled', 'Cancelled order transition failed.' );
$refund = wc_create_refund( array( 'order_id' => $orders['en'], 'amount' => '12.50', 'reason' => 'QTX integration fixture', 'refund_payment' => false ) );
$check( ! is_wp_error( $refund ) && $refund instanceof WC_Order_Refund, 'Offline refund creation failed.' );
$check( count( $mail ) >= 3, 'WooCommerce customer emails were not captured.' );
foreach ( $mail as $message ) {
    $recipients = is_array( $message['to'] ) ? $message['to'] : array( $message['to'] );
    foreach ( $recipients as $recipient ) {
        $check( str_ends_with( strtolower( $recipient ), '@example.test' ), 'Mail capture saw a non-test recipient.' );
    }
}

// Authenticated Woo REST: view is translated, edit/raw and all technical fields are stable.
wp_set_current_user( 1 );
$set_language( 'ru' );
$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $simple_id );
$request->set_param( 'context', 'view' );
$response = rest_do_request( $request );
$data = $response->get_data();
$check( $response->get_status() === 200 && $data['name'] === $language_names['ru'], 'Woo REST translated product view failed.' );
$check( $data['id'] === $simple_id && $data['sku'] === 'QTX-SIMPLE-001' && $data['price'] === '12.50', 'Woo REST product technical fields changed.' );
$variation_request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $variable_id . '/variations/' . $variation_id );
$variation_data = rest_do_request( $variation_request )->get_data();
$check( $variation_data['id'] === $variation_id && $variation_data['sku'] === 'QTX-VAR-SMALL-001' && $variation_data['price'] === '15.75', 'Woo REST variation technical fields changed.' );
$order_request = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $orders['ru'] );
$order_data = rest_do_request( $order_request )->get_data();
$check( $order_data['id'] === $orders['ru'] && $order_data['payment_method'] === 'cod' && (float) $order_data['total'] === 40.75, 'Woo REST order technical fields changed.' );
global $wpdb;
$raw_name = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $simple_id ) );
$check( str_contains( $raw_name, '[:lv]' ) && str_contains( $raw_name, '[:ru]' ) && str_contains( $raw_name, '[:en]' ), 'QTX RAW storage policy was not preserved.' );
$check( $data['name'] === $language_names['ru'] && $data['name'] !== $raw_name, 'QTX TRANSLATED REST policy was not explicit.' );

// Persistent object cache isolation and targeted invalidation fixture.
$check( wp_using_ext_object_cache(), 'Persistent Redis object cache is not active.' );
foreach ( $language_names as $language => $name ) {
    wp_cache_set( 'product-' . $simple_id, $name, 'qtx-woo-' . $language, 300 );
}
foreach ( $language_names as $language => $name ) {
    $check( wp_cache_get( 'product-' . $simple_id, 'qtx-woo-' . $language ) === $name, strtoupper( $language ) . ' cache isolation failed.' );
}
$old_ru = wp_cache_get( 'product-' . $simple_id, 'qtx-woo-ru' );
wp_update_post( array( 'ID' => $simple_id, 'post_title' => $ml( 'Jauna krūze', 'Новая кружка', 'New mug' ) ) );
clean_post_cache( $simple_id );
wp_cache_delete( 'product-' . $simple_id, 'qtx-woo-ru' );
$check( $old_ru === $language_names['ru'] && false === wp_cache_get( 'product-' . $simple_id, 'qtx-woo-ru' ), 'Targeted multilingual product cache invalidation failed.' );

// Historical records and every protected identifier/value must survive language switches.
foreach ( array( 'lv', 'ru', 'en' ) as $language ) {
    $set_language( $language );
    $check( get_post_meta( $simple_id, '_sku', true ) === $baseline['simple_sku'], 'Simple SKU mutated after language switch.' );
    $check( get_post_meta( $variation_id, '_sku', true ) === $baseline['variation_sku'], 'Variation SKU mutated after language switch.' );
    $check( get_post_meta( $simple_id, '_price', true ) === $baseline['simple_price'], 'Simple price mutated after language switch.' );
    $check( get_post_meta( $variation_id, '_stock', true ) === $baseline['variation_stock'], 'Variation stock mutated after language switch.' );
    $serialized_fixture = get_metadata_raw( 'post', $simple_id, '_qtx_matrix_serialized_fixture', true );
    $check( $serialized_fixture === array( 'technical' => array( 'key' => 'value', 'number' => 42 ) ), 'Serialized technical metadata mutated after language switch.' );
    foreach ( $orders as $order_language => $order_id ) {
        $historical_order = wc_get_order( $order_id );
        $check( $historical_order->get_meta( '_user_language', true ) === $order_language, 'Historical order language was rewritten.' );
        $check( $historical_order->get_payment_method() === 'cod' && $historical_order->get_transaction_id() === '', 'Historical payment/transaction identifier was rewritten.' );
        $current_snapshot = array_map( static function ( WC_Order_Item_Product $item ): array {
            return array( $item->get_product_id(), $item->get_variation_id(), $item->get_name(), $item->get_quantity(), $item->get_total() );
        }, $historical_order->get_items() );
        $check( $current_snapshot === $snapshots[ $order_language ], 'Historical order product/variation snapshot was rewritten.' );
    }
}

if ( $failures ) {
    WP_CLI::error( sprintf( 'WooCommerce matrix failed: %d/%d assertions failed.', count( $failures ), $checks ) );
}
WP_CLI::success( sprintf( 'WooCommerce matrix passed: %d assertions; LV/RU/EN, transactions, mail capture, REST and Redis.', $checks ) );

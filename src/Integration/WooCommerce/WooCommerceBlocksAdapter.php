<?php

namespace QTX\Integration\WooCommerce;

/**
 * Core bootstrap for WooCommerce Cart/Checkout blocks.
 *
 * This adapter intentionally does not depend on the legacy WooCommerce module
 * state. Store API product presentation and the text-only block fallback must
 * remain available on upgraded sites with stale or missing module options.
 */
final class WooCommerceBlocksAdapter {
    /** @var callable */
    private $activateFrontend;
    private bool $registered = false;

    public function __construct( ?callable $activateFrontend = null ) {
        $this->activateFrontend = $activateFrontend ?? static function (): void {
            require_once QTRANSLATE_DIR . '/src/modules/woo-commerce/front.php';
            // Store API requests may arrive after another integration removed
            // the presentation graph. Re-adding WordPress filters is
            // idempotent and guarantees the response boundary is translated.
            qtranxf_wc_add_filters_front();
        };
    }

    public function register(): void {
        if ( $this->registered ) {
            return;
        }

        add_filter( 'rest_pre_dispatch', array( $this, 'prepareStoreApiRequest' ), 5, 3 );
        add_filter( 'the_content', array( $this, 'translateSystemPageContent' ), 99 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueueFrontend' ), 20 );
        $this->registered = true;
    }

    /**
     * Keep WooCommerce structural pages available in every language.
     *
     * Cart, Checkout and My Account are single configured WordPress pages.
     * Their block/shortcode document is structural rather than a language-
     * specific article. If an upgraded site stored that document only in one
     * qTranslate language, use the normal default-language fallback without
     * emitting the public "translation not available" notice. Product and
     * interface strings are translated later by their own Woo/QTX adapters.
     *
     * @param mixed $content
     * @return mixed
     */
    public function translateSystemPageContent( $content ) {
        if ( ! is_string( $content ) || ! function_exists( 'wc_get_page_id' ) || ! function_exists( 'get_the_ID' ) ) {
            return $content;
        }

        $postId = (int) get_the_ID();
        if ( $postId <= 0 ) {
            return $content;
        }

        $isSystemPage = false;
        foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
            if ( (int) wc_get_page_id( $page ) === $postId ) {
                $isSystemPage = true;
                break;
            }
        }
        if ( ! $isSystemPage ) {
            return $content;
        }

        global $q_config;

        return qtranxf_use( $q_config['language'], $content, false, false );
    }

    /**
     * @param mixed $response
     * @param mixed $server
     * @param mixed $request
     * @return mixed
     */
    public function prepareStoreApiRequest( $response, $server, $request ) {
        if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) {
            return $response;
        }

        $route = $request->get_route();
        if ( ! is_string( $route ) || strpos( $route, '/wc/store/' ) !== 0 ) {
            return $response;
        }

        ( $this->activateFrontend )();

        return $response;
    }

    public function enqueueFrontend(): void {
        $isCart     = function_exists( 'is_cart' ) && is_cart();
        $isCheckout = function_exists( 'is_checkout' ) && is_checkout();
        $hasBlocks  = function_exists( 'has_block' ) && (
            has_block( 'woocommerce/cart' )
            || has_block( 'woocommerce/checkout' )
            || has_block( 'woocommerce/mini-cart' )
        );
        if ( ! $isCart && ! $isCheckout && ! $hasBlocks ) {
            return;
        }

        global $q_config;
        wp_register_script(
            'qtx-woocommerce-blocks',
            plugins_url( 'dist/woocommerce-blocks.js', QTRANSLATE_FILE ),
            array( 'wp-hooks' ),
            QTX_VERSION,
            true
        );
        wp_localize_script(
            'qtx-woocommerce-blocks',
            'qtxWooBlocks',
            array(
                'language'            => $q_config['language'],
                'defaultLanguage'     => $q_config['default_language'],
                'enabledLanguages'    => array_values( $q_config['enabled_languages'] ),
                'languageCodePattern' => QTX_LANG_CODE_FORMAT,
            )
        );
        wp_enqueue_script( 'qtx-woocommerce-blocks' );
    }
}

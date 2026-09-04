<?php

use PHPUnit\Framework\TestCase;

final class WooCommerceWorkflowContractTest extends TestCase {
    private function workflow(): string {
        return file_get_contents( dirname( __DIR__, 2 ) . '/.github/workflows/woocommerce-integration.yml' );
    }

    public function test_workflow_pins_the_disposable_integration_stack(): void {
        $workflow = $this->workflow();

        self::assertStringContainsString( 'mysql:8.4', $workflow );
        self::assertStringContainsString( 'redis:7.4-alpine', $workflow );
        self::assertStringContainsString( "php-version: '8.4'", $workflow );
        self::assertStringContainsString( 'core download --version=7.1', $workflow );
        self::assertStringContainsString( 'plugin install woocommerce --version=11.0.1', $workflow );
        self::assertStringContainsString( 'plugin install redis-cache --version=2.8.0', $workflow );
        self::assertStringContainsString( 'wp config set WP_REDIS_PREFIX "qtx-woo-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}:"', $workflow );
    }

    public function test_wp_cli_download_is_official_and_fail_closed(): void {
        $workflow = $this->workflow();

        self::assertStringContainsString( 'raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar', $workflow );
        self::assertStringContainsString( 'ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c', $workflow );
        self::assertStringContainsString( 'sha256sum --check --strict', $workflow );
        self::assertStringNotContainsString( 'releases/latest/download/wp-cli.phar', $workflow );
    }

    public function test_runner_uses_only_disposable_credentials_mail_and_payment(): void {
        $root = dirname( __DIR__, 2 );
        $workflow = $this->workflow();
        $runner = file_get_contents( $root . '/tests/Integration/WooCommerce/transaction-matrix.php' );

        self::assertStringContainsString( 'openssl rand -hex 24', $workflow );
        self::assertStringContainsString( '--skip-email', $workflow );
        self::assertStringContainsString( "add_filter( 'pre_wp_mail'", $runner );
        self::assertStringContainsString( "'payment_method' => 'cod'", $runner );
        self::assertStringContainsString( '@example.test', $runner );
        self::assertStringContainsString( "unregister_taxonomy( 'product_type' )", $runner );
        self::assertStringContainsString( 'Variation term fixtures failed:', $runner );
        self::assertStringContainsString( "checkout did not persist its language through Woo order CRUD", $runner );
        self::assertStringNotContainsString( "\$order->update_meta_data( '_user_language', \$language )", $runner );
        self::assertStringContainsString( 'qtranxf_maybe_unserialize_safe( $serialized_raw )', $runner );
        self::assertStringContainsString( 'long description was not translated', $runner );
        self::assertStringContainsString( 'qtranxf_get_front_page_config()', $runner );
        self::assertStringContainsString( 'qtx_get_term_translation_repository()->store(', $runner );
        self::assertStringContainsString( 'cart variation labels were not translated', $runner );
        self::assertStringContainsString( 'checkout payment label was not translated', $runner );
        self::assertStringContainsString( 'new WC_Gateway_COD()', $runner );
        self::assertStringContainsString( "'/wc/store/v1/cart'", $runner );
        self::assertStringContainsString( 'Store API cart product name was not translated for the block UI', $runner );
        self::assertStringContainsString( 'Store API block translation changed product ID, quantity or price data', $runner );

        $coreRunner = file_get_contents( dirname( __DIR__, 2 ) . '/tests/Integration/WooCommerce/store-api-core-bootstrap.php' );
        self::assertStringContainsString( 'WooCommerce legacy module must be inactive', $coreRunner );
        self::assertStringContainsString( 'Весь набор 250 мокапов', $coreRunner );
        self::assertStringContainsString( 'store-api-core-bootstrap.php', $workflow );
        self::assertStringContainsString( 'qtranslate_modules_state woo-commerce 2', $workflow );
        self::assertStringContainsString( 'Cancelled order email was not captured in its stored LV context', $runner );
        self::assertStringContainsString( 'Refund email was not captured in its stored EN context', $runner );
        self::assertStringContainsString( 'Redis direct cache-group flush control failed', $runner );
        self::assertStringContainsString( 'Webhook did not invoke every required cache-group flush', $runner );
        self::assertStringContainsString( 'Webhook performed an unnecessary global cache flush', $runner );
    }

    public function test_exact_archive_is_exercised_through_http_language_and_rest_routes(): void {
        $workflow = $this->workflow();
        $router = file_get_contents( dirname( __DIR__ ) . '/Integration/http-router.php' );

        self::assertStringContainsString( 'Exercise exact-ZIP HTTP language and REST routes', $workflow );
        self::assertStringContainsString( 'tests/Integration/http-router.php', $workflow );
        self::assertStringContainsString( "check_language_route '/lv/' 'QTX_HTTP_LV'", $workflow );
        self::assertStringContainsString( "check_language_route '/ru/' 'QTX_HTTP_RU'", $workflow );
        self::assertStringContainsString( "check_language_route '/en/' 'QTX_HTTP_EN'", $workflow );
        self::assertStringContainsString( 'WooCommerce/system-page-fallback.php', $workflow );
        self::assertStringContainsString( "check_language_route '/ru/cart/' 'QTX_WOO_SYSTEM_CART'", $workflow );
        self::assertStringContainsString( "check_language_route '/lv/checkout/' 'QTX_WOO_SYSTEM_CHECKOUT'", $workflow );
        self::assertStringContainsString( "check_language_route '/ru/my-account/' 'QTX_WOO_SYSTEM_MYACCOUNT'", $workflow );
        self::assertStringContainsString( "wp option update home 'http://qtx.test:8097'", $workflow );
        self::assertStringContainsString( "wp option update siteurl 'http://qtx.test:8097'", $workflow );
        self::assertStringContainsString( "curl_resolve='qtx.test:8097:127.0.0.1'", $workflow );
        self::assertStringContainsString( '--resolve "$curl_resolve"', $workflow );
        self::assertStringContainsString( '/wp-json/wc/store/v1/cart', $workflow );
        self::assertStringContainsString( "grep --fixed-strings '[:lv]'", $workflow );
        self::assertStringContainsString( "require rtrim( \$document_root, '/\\\\' ) . '/index.php';", $router );
    }
}

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
    }
}

<?php

use PHPUnit\Framework\TestCase;

final class CiSupplyChainContractTest extends TestCase {
    /** @return array<string, string> */
    private function workflows(): array {
        $root = dirname( __DIR__, 2 ) . '/.github/workflows/';

        return array(
            'php-tests.yml'                  => file_get_contents( $root . 'php-tests.yml' ),
            'woocommerce-integration.yml'    => file_get_contents( $root . 'woocommerce-integration.yml' ),
        );
    }

    public function testThirdPartyActionsUseImmutableCommitShas(): void {
        foreach ( $this->workflows() as $name => $workflow ) {
            self::assertDoesNotMatchRegularExpression( '/uses:\s+[^\s@]+@v\d+/i', $workflow, $name );
            preg_match_all( '/uses:\s+[^\s@]+@([0-9a-f]{40})(?:\s|$)/i', $workflow, $matches );
            self::assertNotEmpty( $matches[1], $name );
            self::assertStringContainsString( 'persist-credentials: false', $workflow, $name );
        }
    }

    public function testServiceImagesUseImmutableDigests(): void {
        $workflow = $this->workflows()['woocommerce-integration.yml'];

        self::assertMatchesRegularExpression( '/image:\s+mysql:8\.4@sha256:[0-9a-f]{64}/', $workflow );
        self::assertMatchesRegularExpression( '/image:\s+redis:7\.4-alpine@sha256:[0-9a-f]{64}/', $workflow );
    }

    public function testHostedRunnerVersionIsExplicit(): void {
        foreach ( $this->workflows() as $name => $workflow ) {
            self::assertStringContainsString( 'runs-on: ubuntu-24.04', $workflow, $name );
            self::assertStringNotContainsString( 'runs-on: ubuntu-latest', $workflow, $name );
        }
    }

    public function testJavascriptBuildIsReproduciblyVerified(): void {
        $workflow = $this->workflows()['php-tests.yml'];

        self::assertStringContainsString( "node-version: '24.11.1'", $workflow );
        self::assertStringContainsString( 'npm ci --ignore-scripts', $workflow );
        self::assertStringContainsString( 'npm audit --audit-level=low', $workflow );
        self::assertStringContainsString( 'git diff --exit-code -- dist', $workflow );
    }
}

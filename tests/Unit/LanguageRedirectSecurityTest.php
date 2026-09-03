<?php

use PHPUnit\Framework\TestCase;

final class LanguageRedirectSecurityTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['qtx_test_home_url'] = 'https://www.example.test/base';
        $GLOBALS['qtx_test_site_url'] = 'https://admin.example.test/wp';
        $GLOBALS['qtx_test_is_multisite'] = false;
        $GLOBALS['q_config']['domains'] = array(
            'lv' => 'lv.example.test',
            'ru' => 'https://RU.example.test/path',
        );
        $GLOBALS['qtx_test_filters'] = array();
        $GLOBALS['qtx_test_removed_filters'] = array();
        $GLOBALS['qtx_test_safe_redirects'] = array();
    }

    public function testAllowlistContainsOnlyCanonicalConfiguredHosts(): void {
        self::assertSame(
            array( 'preexisting.test', 'www.example.test', 'admin.example.test', 'lv.example.test', 'ru.example.test' ),
            qtranxf_redirect_allowed_hosts( array( 'preexisting.test' ) )
        );
        self::assertNotContains( 'attacker.example', qtranxf_redirect_allowed_hosts( array() ) );
    }

    public function testMultisiteNetworkHostIsExplicitlyAllowed(): void {
        $GLOBALS['qtx_test_is_multisite'] = true;
        $GLOBALS['qtx_test_network_home_url'] = 'https://network.example.test/subsite';

        self::assertContains( 'network.example.test', qtranxf_redirect_allowed_hosts( array() ) );
    }

    public function testMalformedAndNonStringDomainConfigurationIsIgnored(): void {
        $GLOBALS['q_config']['domains'] = array( 'lv' => "\r\n", 'ru' => array( 'invalid' ) );
        $hosts = qtranxf_redirect_allowed_hosts( array() );

        self::assertSame( array( 'www.example.test', 'admin.example.test' ), $hosts );
    }

    public function testRedirectUsesWordPressSafeSinkWithScopedAllowlistFilter(): void {
        self::assertTrue( qtranxf_safe_language_redirect( 'https://lv.example.test/path', 301 ) );
        self::assertSame(
            array( array( 'https://lv.example.test/path', 301, 'qTranslate-XT' ) ),
            $GLOBALS['qtx_test_safe_redirects']
        );
        self::assertSame( 'allowed_redirect_hosts', $GLOBALS['qtx_test_filters'][0][0] );
        self::assertSame( 'allowed_redirect_hosts', $GLOBALS['qtx_test_removed_filters'][0][0] );
    }

    public function testLanguageDetectionContainsNoDirectRedirectSink(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/language_detect.php' );

        self::assertIsString( $source );
        self::assertStringNotContainsString( 'wp_redirect( $target )', $source );
        self::assertStringContainsString( 'qtranxf_safe_language_redirect( $target, 302 )', $source );
    }
}

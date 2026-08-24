<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AcfRuntimeSmokeScriptContractTest extends TestCase {
    public function testRunnerUsesRealApisAndAlwaysCleansFixtureValues(): void {
        $source = file_get_contents( dirname( __DIR__ ) . '/Integration/acf-native-runtime-smoke.php' );

        self::assertStringContainsString( "function_exists( 'acf' )", $source );
        self::assertStringContainsString( 'acf_add_local_field_group', $source );
        self::assertStringContainsString( 'update_field(', $source );
        self::assertStringContainsString( 'get_field(', $source );
        self::assertStringContainsString( '} finally {', $source );
        self::assertStringContainsString( 'delete_field(', $source );
        self::assertStringContainsString( 'delete_option(', $source );
        self::assertStringNotContainsString( 'active_plugins', $source );
        self::assertStringNotContainsString( 'unserialize(', $source );
    }

    public function testRunnerContainsEveryRequiredFieldFamilyAndFixture(): void {
        $source = file_get_contents( dirname( __DIR__ ) . '/Integration/acf-native-runtime-smoke.php' );

        foreach ( array( 'text', 'textarea', 'wysiwyg', 'group', 'repeater', 'flexible_content', 'url' ) as $type ) {
            self::assertStringContainsString( "'type' => '" . $type . "'", $source );
        }
        foreach ( array( 'Sazināties ar mums', 'Nosūti mums ziņu!', 'Связаться с нами', 'Отправьте нам сообщение!', 'Contact us', 'Send us a message!' ) as $fixture ) {
            self::assertStringContainsString( $fixture, $source );
        }
    }
}

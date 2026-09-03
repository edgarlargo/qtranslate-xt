<?php

use PHPUnit\Framework\TestCase;

final class AcfOptionsBridgeContractTest extends TestCase {
    public function testNativeBridgeCoversOptionsPagesWithoutPluginStateMutation(): void {
        $root = dirname( __DIR__, 2 );
        $admin = file_get_contents( $root . '/src/modules/acf/admin.php' );
        $loader = file_get_contents( $root . '/src/modules/acf/loader.php' );
        $bridge = file_get_contents( $root . '/js/acf/options-bridge.js' );

        self::assertStringContainsString( "'admin.php'     => ''", $admin );
        self::assertStringContainsString( "'show_language_tabs' => true", $admin );
        self::assertStringContainsString( 'qtranxf_acf_initialize_value_adapter();', $loader );
        self::assertStringContainsString( 'attachSafeOptionsTabs', $bridge );
        self::assertStringContainsString( 'switchActiveLanguage', $bridge );
        self::assertStringContainsString( 'textContent', $bridge );
        self::assertStringNotContainsString( 'active_plugins', $loader . $bridge );
        self::assertStringNotContainsString( 'innerHTML', $bridge );
    }

    public function testInitialAndDynamicStandardFieldsReceiveTheBridge(): void {
        $load = file_get_contents( dirname( __DIR__, 2 ) . '/js/acf/load.js' );

        self::assertStringContainsString( 'acf.findFields({type: fieldType})', $load );
        self::assertStringContainsString( "acf.addAction('new_field/type=' + fieldType", $load );
        self::assertStringContainsString( 'hasTranslatableInput', $load );
        self::assertStringContainsString( 'attachSafeOptionsTabs(fieldElement);', $load );
    }
}

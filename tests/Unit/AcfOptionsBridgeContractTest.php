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
        self::assertStringContainsString( 'array_replace_recursive( self::default_options(), $acf_options )', $admin );
        self::assertStringContainsString( 'qtranxf_acf_initialize_value_adapter();', $loader );
        self::assertStringContainsString( 'attachSafeOptionsTabs', $bridge );
        self::assertStringContainsString( 'serializeBridgeValue', $bridge );
        self::assertStringContainsString( 'qtx-acf-options-original', $bridge );
        self::assertStringContainsString( "fieldType !== 'text' && fieldType !== 'textarea'", $bridge );
        self::assertStringContainsString( "field.setAttribute('data-qtx-safe', '1')", $bridge );
        self::assertStringContainsString( 'field.qtxAcfOptionsBridgeAttached = true', $bridge );
        self::assertStringContainsString( "field.getAttribute(fieldMarker) === '1'", $bridge );
        self::assertStringContainsString( 'fieldElement.find(selector).first()[0]', $bridge );
        self::assertStringNotContainsString( 'querySelector(selector)', $bridge );
        self::assertStringContainsString( 'textContent', $bridge );
        self::assertStringNotContainsString( 'active_plugins', $loader . $bridge );
        self::assertStringNotContainsString( 'innerHTML', $bridge );
    }

    public function testInitialAndDynamicStandardFieldsReceiveTheBridge(): void {
        $load = file_get_contents( dirname( __DIR__, 2 ) . '/js/acf/load.js' );

        self::assertStringContainsString( 'acf.findFields({type: fieldType})', $load );
        self::assertStringContainsString( "acf.addAction('new_field/type=' + fieldType", $load );
        self::assertStringContainsString( "acf.addAction('append'", $load );
        self::assertStringContainsString( 'attachSafeOptionsTabs(fieldElement, fieldType, selector)', $load );
        self::assertStringContainsString( 'isTranslatableElementForPostType(bridgeInput, postType)', $load );
        self::assertStringContainsString( 'hasContentHook(this.id)', $load );
    }
}

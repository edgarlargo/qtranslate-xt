<?php

/**
 * Standalone regression tests for QTX-SEC-005.
 *
 * Run: php tests/security/test-module-loader.php
 */

define( 'QTRANSLATE_DIR', dirname( __DIR__, 2 ) );
define( 'QTX_OPTIONS_MODULES_STATE', 'qtranslate_modules_state' );

$qtx_test_option = array();
$q_config = array( 'admin_enabled_modules' => array( 'acf' => false ) );

function get_option( $name, $default = false ) {
    global $qtx_test_option;

    return $name === QTX_OPTIONS_MODULES_STATE ? $qtx_test_option : $default;
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ): bool {
    return true;
}

require_once QTRANSLATE_DIR . '/src/Core/Storage/FieldDefinition.php';
require_once QTRANSLATE_DIR . '/src/Core/Storage/FieldRegistry.php';
require_once QTRANSLATE_DIR . '/src/Core/Integration/IntegrationDefinition.php';
require_once QTRANSLATE_DIR . '/src/Core/Integration/IntegrationRegistry.php';
require_once QTRANSLATE_DIR . '/src/Core/Integration/BuiltinModuleProvider.php';
require_once QTRANSLATE_DIR . '/src/modules/module_loader.php';

function qtx_test_assert( $condition, string $message ): void {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
}

function qtx_test_active_loaders( $option ): array {
    global $qtx_test_option;
    $qtx_test_option = $option;

    return QTX_Module_Loader::get_active_module_loaders();
}

$registry = QTX_Module_Loader::get_registered_module_loaders();
$expected = array(
    'acf',
    'all-in-one-seo-pack',
    'events-made-easy',
    'jetpack',
    'google-site-kit',
    'gravity-forms',
    'woo-commerce',
    'wp-seo',
    'slugs',
);

qtx_test_assert( array_keys( $registry ) === $expected, 'The built-in registry or loader set changed unexpectedly.' );
foreach ( $registry as $module_id => $loader ) {
    qtx_test_assert( realpath( $loader ) === $loader, "Loader for {$module_id} is not canonical." );
    qtx_test_assert( basename( $loader ) === 'loader.php', "Loader for {$module_id} has an unexpected filename." );
    qtx_test_assert( strpos( $loader, realpath( QTRANSLATE_DIR . '/src/modules' ) . DIRECTORY_SEPARATOR ) === 0, "Loader for {$module_id} escaped the module directory." );
}

$active = qtx_test_active_loaders( array( 'acf' => QTX_MODULE_STATE_ACTIVE ) );
qtx_test_assert( array_keys( $active ) === array( 'acf' ), 'A valid registered active module was not selected.' );

$active = qtx_test_active_loaders( array( 'acf' => QTX_MODULE_STATE_INACTIVE ) );
qtx_test_assert( $active === array(), 'An inactive registered module was selected.' );

$rejected_ids = array(
    'unknown-module',
    '../',
    '../../plugin',
    '..\\plugin',
    '....\\plugin',
    '/path',
    'C:\\path',
    'php://filter',
    'phar://archive',
    'acf-malicious',
    'acf/../jetpack',
);
foreach ( $rejected_ids as $module_id ) {
    $active = qtx_test_active_loaders( array( $module_id => QTX_MODULE_STATE_ACTIVE ) );
    qtx_test_assert( $active === array(), "Unregistered module id was selected: {$module_id}" );
    QTX_Module_Loader::load_active_modules();
}

qtx_test_assert( qtx_test_active_loaders( 'corrupted' ) === array(), 'A corrupted scalar option was not ignored.' );
qtx_test_assert( qtx_test_active_loaders( null ) === array(), 'A corrupted null option was not ignored.' );
qtx_test_assert( qtx_test_active_loaders( array() ) === array(), 'An empty option was not handled safely.' );

$mixed = array(
    'jetpack'         => QTX_MODULE_STATE_ACTIVE,
    '../../plugin'    => QTX_MODULE_STATE_ACTIVE,
    'unknown-module'  => QTX_MODULE_STATE_ACTIVE,
    'acf'             => QTX_MODULE_STATE_INACTIVE,
);
qtx_test_assert( array_keys( qtx_test_active_loaders( $mixed ) ) === array( 'jetpack' ), 'Unknown or inactive entries affected the selected loader set.' );

echo "QTX module loader security tests passed.\n";

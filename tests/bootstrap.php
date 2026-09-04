<?php

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'QTRANSLATE_DIR', dirname( __DIR__ ) );
define( 'QTX_LANG_CODE_FORMAT', '[a-z]{2,3}' );

$GLOBALS['q_config'] = array(
    'enabled_languages'                 => array( 'lv', 'ru', 'en' ),
    'language'                          => 'lv',
    'default_language'                  => 'en',
    'language_name'                     => array(
        'lv' => 'Latviešu',
        'ru' => 'Русский',
        'en' => 'English',
    ),
    'not_available'                     => array(
        'lv' => 'Available: %LANG:, : and %',
        'ru' => 'Available: %LANG:, : and %',
        'en' => 'Available: %LANG:, : and %',
    ),
    'show_displayed_language_prefix'    => false,
    'show_alternative_content'          => true,
);

function apply_filters( $hook_name, $value ) {
    return $value;
}

function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ): bool {
    $GLOBALS['qtx_test_filters'][] = array( $hook_name, $callback, $priority, $accepted_args );
    return true;
}

function remove_filter( $hook_name, $callback, $priority = 10 ): bool {
    $GLOBALS['qtx_test_removed_filters'][] = array( $hook_name, $callback, $priority );
    return true;
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ): bool {
    $GLOBALS['qtx_test_actions'][] = array( $hook_name, $callback, $priority, $accepted_args );
    return true;
}

function remove_action( $hook_name, $callback, $priority = 10 ): bool {
    $GLOBALS['qtx_test_removed_actions'][] = array( $hook_name, $callback, $priority );
    return true;
}

function home_url(): string { return $GLOBALS['qtx_test_home_url'] ?? 'https://example.test'; }
function site_url(): string { return $GLOBALS['qtx_test_site_url'] ?? 'https://example.test/wp'; }
function is_multisite(): bool { return $GLOBALS['qtx_test_is_multisite'] ?? false; }
function network_home_url(): string { return $GLOBALS['qtx_test_network_home_url'] ?? 'https://network.example.test'; }
function wp_safe_redirect( string $location, int $status = 302, string $x_redirect_by = 'WordPress' ): bool {
    $GLOBALS['qtx_test_safe_redirects'][] = array( $location, $status, $x_redirect_by );
    return true;
}

function is_serialized( $value ): bool {
    if ( ! is_string( $value ) ) return false;
    $value = trim( $value );
    if ( $value === 'N;' ) return true;
    return preg_match( '/^(?:a|O|C|s|b|i|d):(?:\d+|[+-]?(?:\d*\.)?\d+)(?::|;)/s', $value ) === 1;
}

function do_action( $hook_name, ...$args ): void {
    $GLOBALS['qtx_test_fired_actions'][] = array( $hook_name, $args );
}

function did_action( string $hook_name ): int {
    return (int) ( $GLOBALS['qtx_test_did_actions'][ $hook_name ] ?? 0 );
}

function get_term_meta( int $term_id, string $key, bool $single = false ) {
    $values = $GLOBALS['qtx_test_term_meta'][ $term_id ][ $key ] ?? array();
    return $single ? ( $values[0] ?? '' ) : $values;
}

function update_term_meta( int $term_id, string $key, $value ) {
    $GLOBALS['qtx_test_term_meta'][ $term_id ][ $key ] = array( $value );
    return true;
}

function delete_term_meta( int $term_id, string $key ): bool {
    unset( $GLOBALS['qtx_test_term_meta'][ $term_id ][ $key ] );
    return true;
}

function update_option( string $name, $value ): bool {
    $GLOBALS['qtx_test_options'][ $name ] = $value;
    return true;
}

function get_option( string $name, $default = false ) {
    return $GLOBALS['qtx_test_options'][ $name ] ?? $default;
}

function acf() {
    return $GLOBALS['qtx_test_acf_instance'] ?? null;
}

function acf_get_setting( string $name ) {
    return $GLOBALS['qtx_test_acf_settings'][ $name ] ?? null;
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public string $code;
        public string $message;
        public array $data;
        public function __construct( string $code, string $message = '', array $data = array() ) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }
        public function get_error_code(): string { return $this->code; }
        public function get_error_data(): array { return $this->data; }
    }
}

function register_rest_field( $object_type, string $attribute, array $args ): void {
    $GLOBALS['qtx_test_rest_fields'][] = array( $object_type, $attribute, $args );
}

function apply_filters_deprecated( $hook_name, array $args ) {
    return $args[0];
}

function _deprecated_function( $function_name, $version, $replacement = null ): void {
    $GLOBALS['qtx_test_deprecated_functions'][] = array( $function_name, $version, $replacement );
}

function qtranxf_getLanguageName( string $lang = '' ): string {
    global $q_config;

    return $q_config['language_name'][ $lang ] ?? $lang;
}

function qtranxf_convertURL( string $url = '', string $lang = '', bool $forceadmin = false, bool $showDefaultLanguage = false ): string {
    return '/?lang=' . $lang;
}

require_once dirname( __DIR__ ) . '/src/Core/Multilingual/MultilingualEntry.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/MultilingualValue.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/MultilingualDetector.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/MultilingualParser.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/MultilingualBuilder.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/TranslationResult.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/TranslationService.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/LanguageCatalog.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/LanguageContext.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/FallbackPolicy.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/LanguageRequest.php';
require_once dirname( __DIR__ ) . '/src/Core/Multilingual/LanguageResolver.php';
require_once dirname( __DIR__ ) . '/src/Core/Storage/FieldDefinition.php';
require_once dirname( __DIR__ ) . '/src/Core/Storage/FieldRegistry.php';
require_once dirname( __DIR__ ) . '/src/Core/Storage/RegisteredValueAdapter.php';
require_once dirname( __DIR__ ) . '/src/Core/Storage/MetadataValue.php';
require_once dirname( __DIR__ ) . '/src/Core/Integration/IntegrationDefinition.php';
require_once dirname( __DIR__ ) . '/src/Core/Integration/IntegrationRegistry.php';
require_once dirname( __DIR__ ) . '/src/Core/Integration/BuiltinModuleProvider.php';
require_once dirname( __DIR__ ) . '/src/Core/Config/I18nConfigFilePolicy.php';
require_once dirname( __DIR__ ) . '/src/Core/Config/I18nConfigPathMigration.php';
require_once dirname( __DIR__ ) . '/src/Core/Config/LocalSqlFilePolicy.php';
require_once dirname( __DIR__ ) . '/src/Core/Rest/RestTranslationContext.php';
require_once dirname( __DIR__ ) . '/src/Core/Rest/RestLanguagePolicy.php';
require_once dirname( __DIR__ ) . '/src/Core/Rest/RestRouteDefinition.php';
require_once dirname( __DIR__ ) . '/src/Core/Rest/RestRouteRegistry.php';
require_once dirname( __DIR__ ) . '/src/Core/Rest/RestRoutePolicyAdapter.php';
require_once dirname( __DIR__ ) . '/src/Core/Rest/EditorFieldState.php';
require_once dirname( __DIR__ ) . '/src/Core/Rest/EditorMergeResult.php';
require_once dirname( __DIR__ ) . '/src/Core/Rest/EditorFieldMergeService.php';
require_once dirname( __DIR__ ) . '/src/integration_api.php';
require_once dirname( __DIR__ ) . '/src/date_time.php';
require_once dirname( __DIR__ ) . '/src/deprecated.php';
require_once dirname( __DIR__ ) . '/src/language_blocks.php';
require_once dirname( __DIR__ ) . '/src/language_detect.php';
require_once dirname( __DIR__ ) . '/src/hooks.php';
require_once dirname( __DIR__ ) . '/src/class_translator.php';
require_once dirname( __DIR__ ) . '/src/Integration/WordPress/FrontendTranslationAdapter.php';
require_once dirname( __DIR__ ) . '/src/Integration/WordPress/RegisteredOptionAdapter.php';
require_once dirname( __DIR__ ) . '/src/Integration/WordPress/RegisteredMetadataAdapter.php';
require_once dirname( __DIR__ ) . '/src/Integration/WordPress/TermTranslationRepository.php';
require_once dirname( __DIR__ ) . '/src/Integration/WordPress/RegisteredPostRestFieldAdapter.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfRuntime.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfRuntimeDetector.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfRuntimeBootstrap.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfValueContext.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfFieldDefinition.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfFieldSchema.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfValueProjector.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfLifecycleAdapter.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfScalarTranslator.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfAdminEditingService.php';
require_once dirname( __DIR__ ) . '/src/Integration/Acf/AcfSafeBridgeValueAdapter.php';
require_once dirname( __DIR__ ) . '/src/Integration/WooCommerce/WooCommerceDataPolicy.php';
require_once dirname( __DIR__ ) . '/src/modules/module_loader.php';
require_once dirname( __DIR__ ) . '/src/utils.php';
require_once dirname( __DIR__ ) . '/src/taxonomy.php';
require_once dirname( __DIR__ ) . '/src/frontend.php';

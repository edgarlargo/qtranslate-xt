<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once QTRANSLATE_DIR . '/src/class_translator.php';
require_once QTRANSLATE_DIR . '/src/deprecated.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/MultilingualEntry.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/MultilingualValue.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/MultilingualDetector.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/MultilingualParser.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/MultilingualBuilder.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/TranslationResult.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/TranslationService.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/LanguageCatalog.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/LanguageContext.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/FallbackPolicy.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/LanguageRequest.php';
require_once QTRANSLATE_DIR . '/src/Core/Multilingual/LanguageResolver.php';
require_once QTRANSLATE_DIR . '/src/Core/Storage/FieldDefinition.php';
require_once QTRANSLATE_DIR . '/src/Core/Storage/FieldRegistry.php';
require_once QTRANSLATE_DIR . '/src/Core/Storage/RegisteredValueAdapter.php';
require_once QTRANSLATE_DIR . '/src/Core/Storage/MetadataValue.php';
require_once QTRANSLATE_DIR . '/src/Core/Integration/IntegrationDefinition.php';
require_once QTRANSLATE_DIR . '/src/Core/Integration/IntegrationRegistry.php';
require_once QTRANSLATE_DIR . '/src/Core/Integration/BuiltinModuleProvider.php';
require_once QTRANSLATE_DIR . '/src/Core/Config/I18nConfigFilePolicy.php';
require_once QTRANSLATE_DIR . '/src/Core/Config/I18nConfigPathMigration.php';
require_once QTRANSLATE_DIR . '/src/Core/Config/LocalSqlFilePolicy.php';
require_once QTRANSLATE_DIR . '/src/Core/Rest/RestTranslationContext.php';
require_once QTRANSLATE_DIR . '/src/Core/Rest/RestLanguagePolicy.php';
require_once QTRANSLATE_DIR . '/src/Core/Rest/RestRouteDefinition.php';
require_once QTRANSLATE_DIR . '/src/Core/Rest/RestRouteRegistry.php';
require_once QTRANSLATE_DIR . '/src/Core/Rest/RestRoutePolicyAdapter.php';
require_once QTRANSLATE_DIR . '/src/Core/Rest/EditorFieldState.php';
require_once QTRANSLATE_DIR . '/src/Core/Rest/EditorMergeResult.php';
require_once QTRANSLATE_DIR . '/src/Core/Rest/EditorFieldMergeService.php';
require_once QTRANSLATE_DIR . '/src/integration_api.php';
require_once QTRANSLATE_DIR . '/src/Integration/WordPress/FrontendTranslationAdapter.php';
require_once QTRANSLATE_DIR . '/src/Integration/WordPress/RegisteredOptionAdapter.php';
require_once QTRANSLATE_DIR . '/src/Integration/WordPress/RegisteredMetadataAdapter.php';
require_once QTRANSLATE_DIR . '/src/Integration/WordPress/TermTranslationRepository.php';
require_once QTRANSLATE_DIR . '/src/Integration/WordPress/RegisteredPostRestFieldAdapter.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfRuntime.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfRuntimeDetector.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfRuntimeBootstrap.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfValueContext.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfFieldDefinition.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfFieldSchema.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfValueProjector.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfLifecycleAdapter.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfScalarTranslator.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfAdminEditingService.php';
require_once QTRANSLATE_DIR . '/src/Integration/Acf/AcfSafeBridgeValueAdapter.php';
require_once QTRANSLATE_DIR . '/src/Integration/WooCommerce/WooCommerceDataPolicy.php';
require_once QTRANSLATE_DIR . '/src/Integration/WooCommerce/WooCommerceBlocksAdapter.php';
require_once QTRANSLATE_DIR . '/src/language_blocks.php';
require_once QTRANSLATE_DIR . '/src/language_config.php';
require_once QTRANSLATE_DIR . '/src/language_detect.php';
require_once QTRANSLATE_DIR . '/src/options.php';
require_once QTRANSLATE_DIR . '/src/url.php';
require_once QTRANSLATE_DIR . '/src/utils.php';
require_once QTRANSLATE_DIR . '/src/taxonomy.php';
require_once QTRANSLATE_DIR . '/src/modules/module_loader.php';

/**
 * Main initialization of qTranslate-XT for language detection and plugin loading.
 *
 * Redirect to a canonical URL if it doesn't match the detected language.
 * @see https://github.com/qtranslate/qtranslate-xt/wiki/Browser-redirection
 * Load configuration, common hooks, detect and load front/admin configuration, load modules.
 *
 * @return void
 */
function qtranxf_init_language(): void {
    global $q_config, $pagenow;

    qtranxf_load_config();
    qtx_boot_integration_registry();

    // 'url_info' hash is not for external use, it is subject to change at any time.
    // 'url_info' is preserved on reloadConfig
    if ( ! isset( $q_config['url_info'] ) ) {
        $q_config['url_info'] = array();
    }

    $url_info = &$q_config['url_info'];

    // TODO clarify url_info fields that are exposed in API
    if ( ! $q_config['disable_client_cookies'] && isset( $_COOKIE[ QTX_COOKIE_NAME_FRONT ] ) ) {
        $url_info['cookie_lang_front'] = $_COOKIE[ QTX_COOKIE_NAME_FRONT ];
    }
    if ( isset( $_COOKIE[ QTX_COOKIE_NAME_ADMIN ] ) ) {
        $url_info['cookie_lang_admin'] = $_COOKIE[ QTX_COOKIE_NAME_ADMIN ];
    }
    // TODO this field should be removed, to be avoided as much as possible!
    $url_info['cookie_front_or_admin_found'] = isset ( $url_info['cookie_lang_front'] ) || isset( $url_info['cookie_lang_admin'] );

    if ( WP_DEBUG ) {
        $url_info['pagenow']        = $pagenow;
        $url_info['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? '';
        if ( is_admin() ) {
            $url_info['WP_ADMIN'] = true;
        }
        if ( wp_doing_ajax() ) {
            $url_info['WP_DOING_AJAX_POST'] = $_POST;
        }
        if ( wp_doing_cron() ) {
            $url_info['WP_DOING_CRON_POST'] = $_POST;
        }
    }

    // fill url_info similarly to qtranxf_parseURL
    $url_info['scheme'] = is_ssl() ? 'https' : 'http';
    // see https://wordpress.org/support/topic/messy-wp-cronphp-command-line-output
    $url_info['host'] = $_SERVER['HTTP_HOST'] ?? '';
    $url_info['path'] = strtok( $_SERVER['REQUEST_URI'], '?' );
    if ( ! empty ( $_SERVER['QUERY_STRING'] ) ) {
        $url_info['query'] = qtranxf_sanitize_url( $_SERVER['QUERY_STRING'] ); // to prevent xss

        if ( isset( $_GET['qtranslate-mode'] ) && $_GET['qtranslate-mode'] == 'raw' ) {
            $url_info['qtranslate-mode']      = 'raw';
            $url_info['doing_front_end']      = true;
            $q_config['url_info']             = $url_info;
            $q_config['url_info']['language'] = $q_config['default_language'];
            $q_config['language']             = $q_config['default_language'];

            return;
        }
    }

    $url_info['language'] = qtranxf_detect_language( $url_info );
    $q_config['language'] = apply_filters( 'qtranslate_language', $url_info['language'], $url_info );

    assert( isset( $q_config['url_info']['doing_front_end'] ) );
    if ( $q_config['url_info']['doing_front_end'] && qtranxf_can_redirect() ) {
        qtranxf_check_url_maybe_redirect( $url_info );
    } elseif ( isset( $url_info['doredirect'] ) ) {
        // This should not happen!
        // We are possibly in a bad state as the specified language is missing in the request.
        // But we can't redirect (e.g. AJAX request or CLI command), or the request is detected as doing_admin.
        // So we leave a potential bug but we avoid any HTTP interference that could break some functionalities.
        // TODO log these events for the admin (with url_info dump), they should not be left unnoticed.
        $url_info['doredirect'] .= ' - cancelled by can_redirect';
    }

    // TODO clarify fix url to prevent xss - how does this prevents xss?
    // $q_config['url_info']['url'] = qtranxf_convertURL(add_query_arg('lang',$q_config['default_language'],$q_config['url_info']['url']));

    require_once QTRANSLATE_DIR . '/src/rest_api.php';
    add_action( 'init', 'qtranxf_rest_api_register_rewrites', 11 );

    require_once QTRANSLATE_DIR . '/src/widget.php';
    add_action( 'widgets_init', 'qtranxf_widget_init' );

    require_once QTRANSLATE_DIR . '/src/hooks.php';  // Common hooks need language already detected.
    qtranxf_add_main_filters();

    // Keep the proven Safe Bridge frontend path available independently from
    // legacy module/plugin state. This is required for theme-embedded ACF and
    // older Options Pages whose field metadata is not visible to the module.
    $acf_safe_bridge = new \QTX\Integration\Acf\AcfSafeBridgeValueAdapter();
    $acf_safe_bridge->register();
    $GLOBALS['qtx_acf_safe_bridge_value_adapter'] = $acf_safe_bridge;

    // Gutenberg Cart/Checkout product presentation cannot depend on a legacy
    // module-state option: upgraded sites may otherwise expose raw markers.
    $woo_blocks = new \QTX\Integration\WooCommerce\WooCommerceBlocksAdapter();
    $woo_blocks->register();
    $GLOBALS['qtx_woocommerce_blocks_adapter'] = $woo_blocks;

    require_once QTRANSLATE_DIR . '/src/date_time.php';
    qtranxf_add_date_time_filters();

    // See https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
    add_action( 'init', 'qtranxf_load_plugin_textdomain' );

    /**
     * allow other plugins to initialize whatever they need before the fork between front and admin.
     */
    do_action( 'qtranslate_load_front_admin', $url_info );

    if ( $q_config['url_info']['doing_front_end'] ) {
        require_once QTRANSLATE_DIR . '/src/frontend.php';
        qtranxf_add_front_filters();
    } else {
        require_once QTRANSLATE_DIR . '/src/admin/admin.php';
        qtranxf_admin_load();
    }
    QTX_Translator::get_translator();
    apply_filters_deprecated( 'wp_translator', array( null ), '3.14.0', '', 'This filter will be removed in next major release. Open a ticket on https://github.com/qtranslate/qtranslate-xt/issues if you need this!' );

    QTX_Module_Loader::load_active_modules();

    qtranxf_load_option_qtrans_compatibility();

    /**
     * allow other plugins and modules to initialize whatever they need for language
     */
    do_action( 'qtranslate_init_language', $url_info );
}

<?php

use QTX\Core\Multilingual\FallbackPolicy;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Multilingual\TranslationService;
use QTX\Integration\Acf\AcfFieldSchema;
use QTX\Integration\Acf\AcfLifecycleAdapter;
use QTX\Integration\Acf\AcfRuntime;
use QTX\Integration\Acf\AcfRuntimeBootstrap;
use QTX\Integration\Acf\AcfRuntimeDetector;
use QTX\Integration\Acf\AcfScalarTranslator;
use QTX\Integration\Acf\AcfValueContext;

/** Runtime predicate consumed by the generic module/provider contract. */
function qtranxf_acf_runtime_available( bool $available = false ): bool {
    return ( new AcfRuntimeDetector( '5.6.0' ) )->detect()->isAvailable();
}

add_filter( 'qtx_module_runtime_available_acf', 'qtranxf_acf_runtime_available' );

/** Resolve the explicit ACF value mode for this request. */
function qtranxf_acf_value_context(): AcfValueContext {
    global $q_config;

    $mode = ! empty( $q_config['url_info']['doing_front_end'] )
        ? AcfValueContext::TRANSLATED
        : AcfValueContext::RAW;
    $mode = apply_filters( 'qtx_acf_value_context', $mode );
    if ( ! in_array( $mode, array( AcfValueContext::RAW, AcfValueContext::TRANSLATED ), true ) ) {
        $mode = AcfValueContext::RAW;
    }

    return new AcfValueContext( $mode );
}

/**
 * Register admin-page integration before qtranslate_init_language is emitted.
 *
 * ACF's own init hook is intentionally used for value-pipeline services, but it
 * fires too late for qtranslate_admin_config. Keep this hook-only registration
 * independent from ACF runtime initialization so the qTranslate admin assets
 * can be selected during the normal admin-page configuration pass.
 */
function qtranxf_acf_register_admin_hooks(): void {
    static $registered = false;

    if ( $registered || qtranxf_acf_value_context()->mode() !== AcfValueContext::RAW ) {
        return;
    }

    require_once __DIR__ . '/admin.php';
    $GLOBALS['qtx_acf_admin_module'] = new QTX_Module_Acf_Admin();
    $registered = true;
}

/**
 * Register the value pipeline as soon as qTranslate has resolved its language.
 *
 * WordPress filters do not require ACF to exist when they are registered. This
 * intentionally closes the theme-embedded ACF race where an Options value can
 * be formatted before the late runtime has emitted acf/init.
 */
function qtranxf_acf_initialize_value_adapter(): ?AcfLifecycleAdapter {
    global $q_config;

    static $adapter;
    if ( isset( $adapter ) ) {
        return $adapter;
    }

    $languages = isset( $q_config['enabled_languages'] ) && is_array( $q_config['enabled_languages'] )
        ? array_values( array_filter( $q_config['enabled_languages'], 'is_string' ) )
        : array();
    if ( $languages === array() ) {
        return null;
    }
    $default = isset( $q_config['default_language'] ) && in_array( $q_config['default_language'], $languages, true )
        ? $q_config['default_language']
        : $languages[0];
    $current = isset( $q_config['language'] ) && in_array( $q_config['language'], $languages, true )
        ? $q_config['language']
        : $default;

    $catalog = new LanguageCatalog( $languages, $default );
    $context = new LanguageContext( $catalog, $current );
    $translator = new AcfScalarTranslator(
        new MultilingualParser( $languages, $default ),
        new TranslationService(),
        new LanguageRequest( $current, FallbackPolicy::legacy() ),
        $context
    );
    $value_context = qtranxf_acf_value_context();
    $adapter = new AcfLifecycleAdapter( new AcfFieldSchema(), $translator, $value_context );
    $adapter->register();

    // Keep the adapter alive and available before a theme-bundled ACF starts.
    $GLOBALS['qtx_acf_runtime_services'] = array(
        'adapter' => $adapter,
        'context' => $value_context,
    );

    return $adapter;
}

/** Initialize native ACF services after the official runtime is ready. */
function qtranxf_acf_initialize_runtime( AcfRuntime $runtime ): void {
    $adapter = qtranxf_acf_initialize_value_adapter();
    if ( ! $adapter ) {
        return;
    }

    $GLOBALS['qtx_acf_runtime_services']['runtime'] = $runtime;

    require_once __DIR__ . '/extended.php';
    new QTX_Module_Acf_Extended();

}

/** Register lifecycle callbacks before a theme can load an embedded ACF. */
function qtranxf_acf_bootstrap(): AcfRuntimeBootstrap {
    static $bootstrap;

    if ( ! isset( $bootstrap ) ) {
        $bootstrap = new AcfRuntimeBootstrap(
            new AcfRuntimeDetector( '5.6.0' ),
            'qtranxf_acf_initialize_runtime'
        );
        $bootstrap->register();
    }

    return $bootstrap;
}

/** Backward-compatible entry point retained for integrations. */
function qtranxf_acf_init(): void {
    qtranxf_acf_bootstrap()->initialize();
}

qtranxf_acf_register_admin_hooks();
qtranxf_acf_initialize_value_adapter();
qtranxf_acf_bootstrap();

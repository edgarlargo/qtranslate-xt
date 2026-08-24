<?php

require_once QTRANSLATE_DIR . '/src/modules/module_state.php';
require_once QTRANSLATE_DIR . '/src/modules/admin_module.php';

/**
 * Provide the ability to load the modules and check the stored state, with a minimal overhead for the front-side.
 *
 * @see QTX_Admin_Module_Manager::update_modules_state() for state updates. No state change is done here.
 */
class QTX_Module_Loader {
    /** @return \QTX\Core\Integration\BuiltinModuleProvider[] */
    public static function get_registered_module_providers(): array {
        static $providers;
        if ( isset( $providers ) ) {
            return $providers;
        }
        $providers = array();
        foreach ( self::get_registered_module_loaders() as $module_id => $loader ) {
            $providers[ $module_id ] = new \QTX\Core\Integration\BuiltinModuleProvider( $module_id, $loader );
        }

        return $providers;
    }

    /** @return string[] map of module id to lifecycle hook */
    public static function get_runtime_discovery_modules(): array {
        $modules = array();
        foreach ( QTX_Admin_Module::get_modules() as $module ) {
            if ( is_string( $module->runtime_hook ) && $module->runtime_hook !== '' ) {
                $modules[ $module->id ] = $module->runtime_hook;
            }
        }

        return $modules;
    }

    /**
     * Return canonical loaders for modules registered by qTranslate-XT.
     *
     * Module identifiers come exclusively from QTX_Admin_Module. The stored
     * module-state option is deliberately not consulted while building paths.
     *
     * @return string[] map of registered module id to canonical loader path
     */
    public static function get_registered_module_loaders(): array {
        static $loaders;
        if ( isset( $loaders ) ) {
            return $loaders;
        }

        $loaders     = array();
        $modules_dir = realpath( QTRANSLATE_DIR . '/src/modules' );
        if ( $modules_dir === false || ! is_dir( $modules_dir ) ) {
            return $loaders;
        }

        $modules_prefix = rtrim( $modules_dir, '/\\' ) . DIRECTORY_SEPARATOR;
        foreach ( QTX_Admin_Module::get_modules() as $module ) {
            $loader = realpath( $modules_prefix . $module->id . DIRECTORY_SEPARATOR . 'loader.php' );
            if ( $loader === false || ! is_file( $loader ) || ! self::path_is_within( $loader, $modules_prefix ) ) {
                continue;
            }
            $loaders[ $module->id ] = $loader;
        }

        return $loaders;
    }

    /**
     * Return canonical active loaders selected by the state option.
     *
     * Unknown option keys never participate in filesystem path construction.
     * This method is public to keep the security boundary independently
     * testable without executing integration module code.
     *
     * @return string[] map of registered active module id to loader path
     */
    public static function get_active_module_loaders(): array {
        $modules_state = get_option( QTX_OPTIONS_MODULES_STATE, array() );
        if ( ! is_array( $modules_state ) ) {
            return array();
        }

        $active_loaders = array();
        foreach ( self::get_registered_module_loaders() as $module_id => $loader ) {
            if ( isset( $modules_state[ $module_id ] ) && $modules_state[ $module_id ] === QTX_MODULE_STATE_ACTIVE ) {
                $active_loaders[ $module_id ] = $loader;
            }
        }

        return $active_loaders;
    }

    public static function register_integrations( \QTX\Core\Integration\IntegrationRegistry $registry ): void {
        $version = defined( 'QTX_VERSION' ) ? QTX_VERSION : 'builtin';
        foreach ( self::get_registered_module_providers() as $module_id => $provider ) {
            $registry->registerIntegration( new \QTX\Core\Integration\IntegrationDefinition(
                'module-' . $module_id,
                $version,
                static fn (): bool => self::is_module_available( $module_id ),
                array( 'module' => $provider )
            ) );
        }
    }

    /**
     * Check a canonical path against the canonical module-directory prefix.
     */
    private static function path_is_within( string $path, string $directory_prefix ): bool {
        if ( DIRECTORY_SEPARATOR === '\\' ) {
            return strncasecmp( $path, $directory_prefix, strlen( $directory_prefix ) ) === 0;
        }

        return strncmp( $path, $directory_prefix, strlen( $directory_prefix ) ) === 0;
    }

    /**
     * Check if a module is active, by reading the state from the options.
     *
     * @param string $module_id
     *
     * @return bool true if module active.
     */
    public static function is_module_active( string $module_id ): bool {
        return isset( self::get_active_module_loaders()[ $module_id ] );
    }

    /**
     * Resolve configured module availability, including late runtime providers.
     */
    public static function is_module_available( string $module_id ): bool {
        if ( ! self::is_module_enabled_by_configuration( $module_id ) ) {
            return false;
        }
        if ( self::is_module_active( $module_id ) ) {
            return true;
        }
        if ( ! isset( self::get_runtime_discovery_modules()[ $module_id ] ) ) {
            return false;
        }

        return (bool) apply_filters( 'qtx_module_runtime_available_' . $module_id, false );
    }

    /**
     * Loads modules previously activated in the options.
     *
     * The state option can only activate identifiers from the built-in module
     * registry. Canonical loaders are also restricted to the module directory.
     *
     * Note also the modules should be loaded before "qtranslate_init_language" is triggered.
     */
    public static function load_active_modules(): void {
        $providers = self::get_registered_module_providers();
        $registered = self::get_registered_module_loaders();
        $loaders = self::get_active_module_loaders();
        foreach ( self::get_runtime_discovery_modules() as $module_id => $runtime_hook ) {
            if ( self::is_module_enabled_by_configuration( $module_id ) && isset( $registered[ $module_id ] ) ) {
                $loaders[ $module_id ] = $registered[ $module_id ];
            }
        }
        foreach ( $loaders as $module_id => $loader ) {
            $providers[ $module_id ]->load();
        }
    }

    private static function is_module_enabled_by_configuration( string $module_id ): bool {
        global $q_config;

        return ! isset( $q_config['admin_enabled_modules'][ $module_id ] )
            || (bool) $q_config['admin_enabled_modules'][ $module_id ];
    }
}

add_action( 'qtx_register_integrations', array( QTX_Module_Loader::class, 'register_integrations' ) );

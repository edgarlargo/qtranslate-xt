<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AcfNativeProductionContractTest extends TestCase {
    public function testLoaderUsesOfficialLifecycleAndExplicitValueContext(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/acf/loader.php' );

        self::assertStringContainsString( "add_filter( 'qtx_module_runtime_available_acf'", $source );
        self::assertStringContainsString( 'AcfRuntimeBootstrap', $source );
        self::assertStringContainsString( 'qtranxf_acf_initialize_value_adapter()', $source );
        self::assertStringContainsString( 'qtranxf_acf_value_context()', $source );
        self::assertStringContainsString( "apply_filters( 'qtx_acf_value_context'", $source );
        self::assertStringNotContainsString( 'is_plugin_active(', $source );
        self::assertStringNotContainsString( 'active_plugins', $source );
        self::assertStringNotContainsString( 'is_admin()', $source );
    }

    public function testAdminHooksAreRegisteredBeforeTheAcfRuntimeBootstrap(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/acf/loader.php' );
        $admin_registration = strrpos( $source, 'qtranxf_acf_register_admin_hooks();' );
        $runtime_bootstrap = strrpos( $source, 'qtranxf_acf_bootstrap();' );

        self::assertNotFalse( $admin_registration );
        self::assertNotFalse( $runtime_bootstrap );
        self::assertLessThan( $runtime_bootstrap, $admin_registration );
        self::assertSame( 1, substr_count( $source, 'new QTX_Module_Acf_Admin()' ) );
        self::assertStringContainsString( "qtranxf_acf_value_context()->mode() !== AcfValueContext::RAW", $source );
    }

    public function testValueAdapterRegistersBeforeLateAcfRuntimeBootstrap(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/acf/loader.php' );
        $adapter_registration = strrpos( $source, 'qtranxf_acf_initialize_value_adapter();' );
        $runtime_bootstrap = strrpos( $source, 'qtranxf_acf_bootstrap();' );

        self::assertNotFalse( $adapter_registration );
        self::assertNotFalse( $runtime_bootstrap );
        self::assertLessThan( $runtime_bootstrap, $adapter_registration );
        self::assertStringContainsString( "'acf/format_value/type='", file_get_contents(
            dirname( __DIR__, 2 ) . '/src/Integration/Acf/AcfLifecycleAdapter.php'
        ) );
    }

    public function testFormatPipelineIsWhitelistedAndNotGlobal(): void {
        $adapter = file_get_contents( dirname( __DIR__, 2 ) . '/src/Integration/Acf/AcfLifecycleAdapter.php' );
        $legacy = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/acf/extended.php' );

        self::assertStringContainsString( "'text'", $adapter );
        self::assertStringContainsString( "'textarea'", $adapter );
        self::assertStringContainsString( "'wysiwyg'", $adapter );
        self::assertStringContainsString( "'acf/format_value/type='", $adapter );
        self::assertStringNotContainsString( "add_filter( 'acf/format_value',", $legacy );
    }

    public function testSafeBridgeFallbackIsIndependentFromLegacyAcfModuleState(): void {
        $root = dirname( __DIR__, 2 );
        $init = file_get_contents( $root . '/src/init.php' );
        $bridge = file_get_contents( $root . '/src/Integration/Acf/AcfSafeBridgeValueAdapter.php' );

        self::assertStringContainsString( 'AcfSafeBridgeValueAdapter.php', $init );
        self::assertStringContainsString( 'new \\QTX\\Integration\\Acf\\AcfSafeBridgeValueAdapter()', $init );
        self::assertStringContainsString( "'acf/format_value/type='", $bridge );
        self::assertStringContainsString( ', 99, 3', $bridge );
        self::assertStringContainsString( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $bridge );
        self::assertStringNotContainsString( 'active_plugins', $bridge );
        self::assertStringNotContainsString( 'QTX_OPTIONS_MODULES_STATE', $bridge );
    }

    public function testRuntimeDiscoveryIsGenericAndAcfUsesOfficialInitHook(): void {
        $module = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/admin_module.php' );
        $loader = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/module_loader.php' );

        self::assertStringContainsString( "'runtime_hook' => 'acf/init'", $module );
        self::assertStringContainsString( 'get_runtime_discovery_modules', $loader );
        self::assertStringContainsString( "'qtx_module_runtime_available_' . \$module_id", $loader );
    }

    public function testNoFakeAcfPluginEntryMutationExistsInProduction(): void {
        $root = dirname( __DIR__, 2 );
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $root . '/src', \FilesystemIterator::SKIP_DOTS )
        );
        foreach ( $iterator as $file ) {
            if ( $file->getExtension() !== 'php' ) {
                continue;
            }
            $source = file_get_contents( $file->getPathname() );
            if ( strpos( $source, 'active_plugins' ) === false ) {
                continue;
            }
            self::assertStringNotContainsString( 'advanced-custom-fields/acf.php', $source, $file->getPathname() );
            self::assertStringNotContainsString( 'advanced-custom-fields-pro/acf.php', $source, $file->getPathname() );
        }
        self::addToAssertionCount( 1 );
    }
}

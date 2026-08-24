<?php

use PHPUnit\Framework\TestCase;

final class AcfModuleRuntimeDetectionContractTest extends TestCase {
    public function testModuleManagerUsesRuntimeCapabilityBeforePluginBasenames(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/admin_module_manager.php' );
        self::assertStringContainsString( '$module->runtime_hook', $source );
        self::assertStringContainsString( 'QTX_Module_Loader::is_module_available( $module->id )', $source );
        self::assertLessThan(
            strpos( $source, 'foreach ( $module->plugins as $plugin )' ),
            strpos( $source, 'QTX_Module_Loader::is_module_available( $module->id )' )
        );
    }
}

<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Integration\BuiltinModuleProvider;
use QTX\Core\Integration\IntegrationRegistry;

final class BuiltinModuleProviderTest extends TestCase {
    public function testProvidersReuseAuthoritativeBuiltinRegistryAndCanonicalLoaders(): void {
        $loaders = \QTX_Module_Loader::get_registered_module_loaders();
        $providers = \QTX_Module_Loader::get_registered_module_providers();

        self::assertSame( array_keys( $loaders ), array_keys( $providers ) );
        self::assertCount( 9, $providers );
        foreach ( $providers as $id => $provider ) {
            self::assertInstanceOf( BuiltinModuleProvider::class, $provider );
            self::assertSame( $id, $provider->moduleId() );
            self::assertSame( $loaders[ $id ], $provider->loader() );
            self::assertSame( realpath( $provider->loader() ), $provider->loader() );
        }
    }

    public function testModuleDescriptorsUseTheSameProviderObjects(): void {
        $registry = new IntegrationRegistry();
        \QTX_Module_Loader::register_integrations( $registry );

        foreach ( \QTX_Module_Loader::get_registered_module_providers() as $id => $provider ) {
            $integration = $registry->integration( 'module-' . $id );
            self::assertNotNull( $integration );
            self::assertSame( $provider, $integration->services()['module'] );
        }
    }

    public function testAcfIsTheRegisteredRuntimeDiscoveredModule(): void {
        self::assertSame( array( 'acf' => 'acf/init' ), \QTX_Module_Loader::get_runtime_discovery_modules() );
    }
}

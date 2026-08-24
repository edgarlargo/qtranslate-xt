<?php

namespace QTX\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QTX\Core\Integration\IntegrationDefinition;
use QTX\Core\Integration\IntegrationRegistry;
use QTX\Core\Storage\FieldDefinition;

final class IntegrationApiTest extends TestCase {
    public function testPublicFacadesShareOneTrustedRegistry(): void {
        $suffix = str_replace( '.', '-', uniqid( 'test-', true ) );
        $integrationId = 'integration-' . $suffix;
        $fieldKey = 'field-' . $suffix;
        $adapterId = 'adapter.' . $suffix;
        $adapter = new \stdClass();

        qtx_register_integration( new IntegrationDefinition( $integrationId, '1.0' ) );
        qtx_register_multilingual_field( new FieldDefinition( 'post', $fieldKey ) );
        qtx_register_value_adapter( $adapterId, $adapter );

        $registry = qtx_get_integration_registry();
        self::assertInstanceOf( IntegrationRegistry::class, $registry );
        self::assertSame( $integrationId, $registry->integration( $integrationId )->id() );
        self::assertTrue( $registry->fields()->has( 'post', $fieldKey ) );
        self::assertSame( $adapter, $registry->valueAdapter( $adapterId ) );
    }

    public function testRegistrationHookBootsOnceWithRegistryArgument(): void {
        $GLOBALS['qtx_test_fired_actions'] = array();

        qtx_boot_integration_registry();
        qtx_boot_integration_registry();

        self::assertCount( 1, $GLOBALS['qtx_test_fired_actions'] );
        self::assertSame( 'qtx_register_integrations', $GLOBALS['qtx_test_fired_actions'][0][0] );
        self::assertSame( qtx_get_integration_registry(), $GLOBALS['qtx_test_fired_actions'][0][1][0] );
    }

    public function testFacadePreservesDuplicateDiagnostics(): void {
        $id = 'duplicate-' . str_replace( '.', '-', uniqid( '', true ) );
        qtx_register_integration( new IntegrationDefinition( $id, '1' ) );

        $this->expectException( InvalidArgumentException::class );
        qtx_register_integration( new IntegrationDefinition( $id, '2' ) );
    }
}

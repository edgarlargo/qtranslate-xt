<?php

namespace QTX\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QTX\Core\Integration\IntegrationDefinition;
use QTX\Core\Integration\IntegrationRegistry;
use QTX\Core\Storage\FieldDefinition;

final class IntegrationRegistryTest extends TestCase {
    public function testCollectsAvailableIntegrationsFieldsAndObjectAdapters(): void {
        $registry = new IntegrationRegistry();
        $service = new \stdClass();
        $adapter = new \stdClass();
        $registry->registerIntegration( new IntegrationDefinition( 'acf', '1.0', static fn (): bool => true, array( 'fields' => $service ) ) );
        $registry->registerIntegration( new IntegrationDefinition( 'missing', '1.0', static fn (): bool => false ) );
        $registry->registerField( new FieldDefinition( 'post', 'hero_copy', 'html' ) );
        $registry->registerValueAdapter( 'acf.text', $adapter );

        self::assertSame( '1.0', $registry->integration( 'acf' )->version() );
        self::assertSame( array( 'fields' => $service ), $registry->integration( 'acf' )->services() );
        self::assertSame( array( 'acf' ), array_keys( $registry->availableIntegrations() ) );
        self::assertTrue( $registry->fields()->has( 'post', 'hero_copy' ) );
        self::assertSame( $adapter, $registry->valueAdapter( 'acf.text' ) );
    }

    public function testRejectsInvalidIdentifiersAndNonObjectServices(): void {
        foreach ( array(
            static fn () => new IntegrationDefinition( '../acf', '1' ),
            static fn () => new IntegrationDefinition( 'acf', '' ),
            static fn () => new IntegrationDefinition( 'acf', '1', null, array( 'loader' => '/tmp/loader.php' ) ),
        ) as $factory ) {
            try {
                $factory();
                self::fail( 'Invalid integration definition was accepted.' );
            } catch ( InvalidArgumentException $exception ) {
                self::assertNotSame( '', $exception->getMessage() );
            }
        }
    }

    public function testDuplicateIdentifiersFailDeterministically(): void {
        $registry = new IntegrationRegistry();
        $registry->registerIntegration( new IntegrationDefinition( 'acf', '1' ) );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'acf' );
        $registry->registerIntegration( new IntegrationDefinition( 'acf', '2' ) );
    }
}

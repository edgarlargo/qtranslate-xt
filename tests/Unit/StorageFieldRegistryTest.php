<?php

namespace QTX\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QTX\Core\Storage\FieldDefinition;
use QTX\Core\Storage\FieldRegistry;

final class StorageFieldRegistryTest extends TestCase {
    public function testSupportsExplicitOptionAndMetadataRegistrations(): void {
        $registry = new FieldRegistry();
        $option = new FieldDefinition( FieldDefinition::STORAGE_OPTION, 'site_tagline' );
        $post = new FieldDefinition( FieldDefinition::STORAGE_POST_META, 'hero_copy', FieldDefinition::VALUE_HTML );
        $term = new FieldDefinition( FieldDefinition::STORAGE_TERM_META, 'summary' );
        $user = new FieldDefinition( FieldDefinition::STORAGE_USER_META, 'biography' );

        foreach ( array( $option, $post, $term, $user ) as $definition ) {
            $registry->register( $definition );
        }

        self::assertSame( $post, $registry->get( 'post', 'hero_copy' ) );
        self::assertTrue( $registry->has( 'option', 'site_tagline' ) );
        self::assertFalse( $registry->has( 'post', 'unregistered' ) );
        self::assertCount( 1, $registry->forStorage( 'term' ) );
        self::assertCount( 4, $registry->all() );
    }

    public function testRejectsUnknownStorageValueTypesAndInvalidKeys(): void {
        foreach ( array(
            static fn () => new FieldDefinition( 'filesystem', 'value' ),
            static fn () => new FieldDefinition( 'post', '', FieldDefinition::VALUE_TEXT ),
            static fn () => new FieldDefinition( 'post', "bad\0key", FieldDefinition::VALUE_TEXT ),
            static fn () => new FieldDefinition( 'post', 'value', 'object' ),
        ) as $factory ) {
            try {
                $factory();
                self::fail( 'Invalid field definition was accepted.' );
            } catch ( InvalidArgumentException $exception ) {
                self::assertNotSame( '', $exception->getMessage() );
            }
        }
    }

    public function testDuplicateRegistrationFailsDeterministically(): void {
        $registry = new FieldRegistry();
        $registry->register( new FieldDefinition( 'post', 'subtitle' ) );

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'post:subtitle' );
        $registry->register( new FieldDefinition( 'post', 'subtitle', FieldDefinition::VALUE_HTML ) );
    }
}

<?php

namespace QTX\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Rest\RestLanguagePolicy;
use QTX\Core\Rest\RestRouteDefinition;
use QTX\Core\Rest\RestRoutePolicyAdapter;
use QTX\Core\Rest\RestRouteRegistry;
use QTX\Core\Rest\RestTranslationContext;

final class RestRoutePolicyAdapterTest extends TestCase {
    public function testOnlyRegisteredEntityRouteInvokesObjectCapability(): void {
        $checked = array();
        $adapter = $this->adapter( function ( int $objectId ) use ( &$checked ): bool {
            $checked[] = $objectId;
            return $objectId === 42;
        } );

        self::assertNull( $adapter->resolve( '/third-party/v1/items/42', 'POST', 'ru', 'edit', true ) );
        self::assertSame( array(), $checked );

        $context = $adapter->resolve( '/wp/v2/posts/42', 'POST', 'ru', 'edit', true );
        self::assertTrue( $context->isAllowed() );
        self::assertSame( RestTranslationContext::MODE_RAW, $context->mode() );
        self::assertSame( array( 42 ), $checked );
    }

    public function testRejectsWrongMethodsMalformedIdsAndMissingCapability(): void {
        $adapter = $this->adapter( static fn (): bool => false );
        self::assertNull( $adapter->resolve( '/wp/v2/posts/42', 'DELETE', 'ru', 'edit', true ) );
        self::assertNull( $adapter->resolve( '/wp/v2/posts/../42', 'POST', 'ru', 'edit', true ) );
        self::assertNull( $adapter->resolve( '/wp/v2/posts/0', 'POST', 'ru', 'edit', true ) );
        self::assertSame( 'edit_forbidden', $adapter->resolve( '/wp/v2/posts/41', 'POST', 'ru', 'edit', true )->error() );
    }

    public function testPublicViewDoesNotRequireEditCapability(): void {
        $adapter = $this->adapter( static function (): bool {
            throw new \RuntimeException( 'View must not call edit capability.' );
        } );
        $context = $adapter->resolve( '/wp/v2/posts/42', 'GET', 'lv', 'view', false );
        self::assertTrue( $context->isAllowed() );
        self::assertSame( RestTranslationContext::MODE_TRANSLATED, $context->mode() );
    }

    public function testRouteDefinitionsRejectTraversalAndDuplicates(): void {
        $registry = new RestRouteRegistry();
        $registry->register( new RestRouteDefinition( 'posts', '/wp/v2/posts', array( 'GET' ), static fn (): bool => true ) );
        $this->expectException( InvalidArgumentException::class );
        $registry->register( new RestRouteDefinition( 'posts', '/wp/v2/posts', array( 'GET' ), static fn (): bool => true ) );
    }

    public function testRouteDefinitionRejectsTraversalBasePath(): void {
        $this->expectException( InvalidArgumentException::class );
        new RestRouteDefinition( 'posts', '/wp/v2/../posts', array( 'GET' ), static fn (): bool => true );
    }

    private function adapter( callable $canEdit ): RestRoutePolicyAdapter {
        $routes = new RestRouteRegistry();
        $routes->register( new RestRouteDefinition( 'posts', '/wp/v2/posts', array( 'GET', 'POST', 'PUT', 'PATCH' ), $canEdit ) );
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'lv' );
        $policy = new RestLanguagePolicy( new LanguageContext( $catalog, 'lv' ) );

        return new RestRoutePolicyAdapter( $routes, $policy );
    }
}

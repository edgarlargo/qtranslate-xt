<?php

namespace QTX\Core\Rest;

use InvalidArgumentException;

final class RestRouteDefinition {
    private string $id;
    private string $basePath;
    /** @var string[] */
    private array $methods;
    /** @var callable */
    private $canEdit;

    /** @param string[] $methods */
    public function __construct( string $id, string $basePath, array $methods, callable $canEdit ) {
        if ( preg_match( '/^[a-z][a-z0-9_-]*$/', $id ) !== 1 ) {
            throw new InvalidArgumentException( 'Invalid REST route definition identifier.' );
        }
        if ( preg_match( '#^/[A-Za-z0-9_-]+/v[0-9]+/[A-Za-z0-9_-]+$#', $basePath ) !== 1 ) {
            throw new InvalidArgumentException( 'REST entity base path must contain namespace and collection only.' );
        }
        $normalizedMethods = array_values( array_unique( array_map( 'strtoupper', $methods ) ) );
        if ( $normalizedMethods === array() || array_diff( $normalizedMethods, array( 'GET', 'POST', 'PUT', 'PATCH' ) ) !== array() ) {
            throw new InvalidArgumentException( 'Unsupported REST route method.' );
        }
        $this->id = $id;
        $this->basePath = $basePath;
        $this->methods = $normalizedMethods;
        $this->canEdit = $canEdit;
    }

    public function id(): string {
        return $this->id;
    }

    public function objectId( string $route, string $method ): ?int {
        if ( ! in_array( strtoupper( $method ), $this->methods, true ) ) {
            return null;
        }
        if ( preg_match( '#^' . preg_quote( $this->basePath, '#' ) . '/([1-9][0-9]*)/?$#', $route, $matches ) !== 1 ) {
            return null;
        }

        return (int) $matches[1];
    }

    public function canEdit( int $objectId ): bool {
        return (bool) ( $this->canEdit )( $objectId );
    }
}

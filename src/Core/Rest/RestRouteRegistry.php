<?php

namespace QTX\Core\Rest;

use InvalidArgumentException;

final class RestRouteRegistry {
    /** @var array<string, RestRouteDefinition> */
    private array $definitions = array();

    public function register( RestRouteDefinition $definition ): void {
        if ( isset( $this->definitions[ $definition->id() ] ) ) {
            throw new InvalidArgumentException( 'REST route definition is already registered: ' . $definition->id() );
        }
        $this->definitions[ $definition->id() ] = $definition;
    }

    /** @return array{definition: RestRouteDefinition, object_id: int}|null */
    public function match( string $route, string $method ): ?array {
        foreach ( $this->definitions as $definition ) {
            $objectId = $definition->objectId( $route, $method );
            if ( $objectId !== null ) {
                return array( 'definition' => $definition, 'object_id' => $objectId );
            }
        }

        return null;
    }
}

<?php

namespace QTX\Core\Integration;

use InvalidArgumentException;

final class IntegrationDefinition {
    private string $id;
    private string $version;
    /** @var callable|null */
    private $runtimePredicate;
    /** @var array<string, object> */
    private array $services;

    /** @param array<string, object> $services */
    public function __construct( string $id, string $version, ?callable $runtimePredicate = null, array $services = array() ) {
        if ( preg_match( '/^[a-z][a-z0-9_-]*$/', $id ) !== 1 ) {
            throw new InvalidArgumentException( 'Invalid integration identifier: ' . $id );
        }
        if ( trim( $version ) === '' ) {
            throw new InvalidArgumentException( 'Integration version is required.' );
        }
        foreach ( $services as $serviceId => $service ) {
            if ( preg_match( '/^[a-z][a-z0-9_.-]*$/', (string) $serviceId ) !== 1 || ! is_object( $service ) ) {
                throw new InvalidArgumentException( 'Integration services require valid identifiers and object instances.' );
            }
        }

        $this->id = $id;
        $this->version = $version;
        $this->runtimePredicate = $runtimePredicate;
        $this->services = $services;
    }

    public function id(): string {
        return $this->id;
    }

    public function version(): string {
        return $this->version;
    }

    public function isAvailable(): bool {
        return $this->runtimePredicate === null || (bool) ( $this->runtimePredicate )();
    }

    /** @return array<string, object> */
    public function services(): array {
        return $this->services;
    }
}

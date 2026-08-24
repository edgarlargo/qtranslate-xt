<?php

namespace QTX\Core\Integration;

use InvalidArgumentException;
use QTX\Core\Storage\FieldDefinition;
use QTX\Core\Storage\FieldRegistry;

final class IntegrationRegistry {
    /** @var array<string, IntegrationDefinition> */
    private array $integrations = array();
    /** @var array<string, object> */
    private array $valueAdapters = array();
    private FieldRegistry $fields;

    public function __construct( ?FieldRegistry $fields = null ) {
        $this->fields = $fields ?? new FieldRegistry();
    }

    public function registerIntegration( IntegrationDefinition $integration ): void {
        $id = $integration->id();
        if ( isset( $this->integrations[ $id ] ) ) {
            throw new InvalidArgumentException( 'Integration is already registered: ' . $id );
        }
        $this->integrations[ $id ] = $integration;
    }

    public function registerField( FieldDefinition $field ): void {
        $this->fields->register( $field );
    }

    public function registerValueAdapter( string $id, object $adapter ): void {
        if ( preg_match( '/^[a-z][a-z0-9_.-]*$/', $id ) !== 1 ) {
            throw new InvalidArgumentException( 'Invalid value adapter identifier: ' . $id );
        }
        if ( isset( $this->valueAdapters[ $id ] ) ) {
            throw new InvalidArgumentException( 'Value adapter is already registered: ' . $id );
        }
        $this->valueAdapters[ $id ] = $adapter;
    }

    public function integration( string $id ): ?IntegrationDefinition {
        return $this->integrations[ $id ] ?? null;
    }

    /** @return array<string, IntegrationDefinition> */
    public function availableIntegrations(): array {
        return array_filter(
            $this->integrations,
            static fn ( IntegrationDefinition $integration ): bool => $integration->isAvailable()
        );
    }

    public function fields(): FieldRegistry {
        return $this->fields;
    }

    public function valueAdapter( string $id ): ?object {
        return $this->valueAdapters[ $id ] ?? null;
    }
}

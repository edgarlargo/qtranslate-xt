<?php

namespace QTX\Core\Storage;

use InvalidArgumentException;

final class FieldRegistry {
    /** @var array<string, FieldDefinition> */
    private array $definitions = array();

    public function register( FieldDefinition $definition ): void {
        $identifier = $definition->identifier();
        if ( isset( $this->definitions[ $identifier ] ) ) {
            throw new InvalidArgumentException( 'Multilingual field is already registered: ' . $identifier );
        }

        $this->definitions[ $identifier ] = $definition;
    }

    public function has( string $storage, string $key ): bool {
        return isset( $this->definitions[ $storage . ':' . $key ] );
    }

    public function get( string $storage, string $key ): ?FieldDefinition {
        return $this->definitions[ $storage . ':' . $key ] ?? null;
    }

    /** @return array<string, FieldDefinition> */
    public function all(): array {
        return $this->definitions;
    }

    /** @return array<string, FieldDefinition> */
    public function forStorage( string $storage ): array {
        return array_filter(
            $this->definitions,
            static fn ( FieldDefinition $definition ): bool => $definition->storage() === $storage
        );
    }
}

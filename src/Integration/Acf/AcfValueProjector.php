<?php

namespace QTX\Integration\Acf;

use InvalidArgumentException;

final class AcfValueProjector {
    /** @var array<string, AcfFieldDefinition> */
    private array $definitions;
    /** @var callable */
    private $translateScalar;
    private int $maximumDepth;

    /** @param array<string, AcfFieldDefinition> $definitions */
    public function __construct( array $definitions, callable $translateScalar, int $maximumDepth = 32 ) {
        if ( $maximumDepth < 1 ) {
            throw new InvalidArgumentException( 'ACF value projection depth must be positive.' );
        }
        $this->definitions = $definitions;
        $this->translateScalar = $translateScalar;
        $this->maximumDepth = $maximumDepth;
    }

    /** @param array<string, mixed> $field */
    public function project( array $field, $value ) {
        return $this->projectField( $field, $value, 0 );
    }

    /** @param array<string, mixed> $field */
    private function projectField( array $field, $value, int $depth ) {
        if ( $depth >= $this->maximumDepth ) {
            throw new InvalidArgumentException( 'ACF value nesting exceeds the configured limit.' );
        }
        $type = isset( $field['type'] ) && is_string( $field['type'] ) ? $field['type'] : '';
        $key = isset( $field['key'] ) && is_string( $field['key'] ) ? $field['key'] : '';
        if ( isset( $this->definitions[ $key ] ) ) {
            if ( ! is_string( $value ) || $this->looksSerialized( $value ) ) {
                return $value;
            }

            return ( $this->translateScalar )( $value, $this->definitions[ $key ] );
        }
        if ( ! is_array( $value ) ) {
            return $value;
        }
        if ( $type === 'group' ) {
            return $this->projectSubFields( $field['sub_fields'] ?? array(), $value, $depth + 1 );
        }
        if ( $type === 'repeater' ) {
            foreach ( $value as $rowIndex => $row ) {
                if ( is_array( $row ) ) {
                    $value[ $rowIndex ] = $this->projectSubFields( $field['sub_fields'] ?? array(), $row, $depth + 1 );
                }
            }

            return $value;
        }
        if ( $type === 'flexible_content' ) {
            return $this->projectFlexibleRows( $field['layouts'] ?? array(), $value, $depth + 1 );
        }

        return $value;
    }

    /** @param array<int, array<string, mixed>> $subFields */
    private function projectSubFields( array $subFields, array $value, int $depth ): array {
        foreach ( $subFields as $subField ) {
            if ( ! is_array( $subField ) ) {
                continue;
            }
            $valueKey = $subField['name'] ?? ( $subField['key'] ?? null );
            if ( ! is_string( $valueKey ) || ! array_key_exists( $valueKey, $value ) ) {
                continue;
            }
            $value[ $valueKey ] = $this->projectField( $subField, $value[ $valueKey ], $depth );
        }

        return $value;
    }

    /** @param array<int, array<string, mixed>> $layouts */
    private function projectFlexibleRows( array $layouts, array $rows, int $depth ): array {
        $byName = array();
        foreach ( $layouts as $layout ) {
            if ( is_array( $layout ) && isset( $layout['name'] ) && is_string( $layout['name'] ) ) {
                $byName[ $layout['name'] ] = $layout;
            }
        }
        foreach ( $rows as $rowIndex => $row ) {
            if ( ! is_array( $row ) || ! isset( $row['acf_fc_layout'] ) || ! isset( $byName[ $row['acf_fc_layout'] ] ) ) {
                continue;
            }
            $layout = $byName[ $row['acf_fc_layout'] ];
            $rows[ $rowIndex ] = $this->projectSubFields( $layout['sub_fields'] ?? array(), $row, $depth );
        }

        return $rows;
    }

    private function looksSerialized( string $value ): bool {
        $trimmed = trim( $value );
        return $trimmed === 'N;' || preg_match( '/^(?:a|b|d|i|O|C|s):\d+(?::|;)/', $trimmed ) === 1;
    }
}

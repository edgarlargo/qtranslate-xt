<?php

namespace QTX\Integration\Acf;

use InvalidArgumentException;

final class AcfFieldSchema {
    private const LEAF_TYPES = array(
        'text' => 'text',
        'textarea' => 'text',
        'wysiwyg' => 'html',
        // Deprecated qTranslate field types remain readable for compatibility.
        'qtranslate_text' => 'text',
        'qtranslate_textarea' => 'text',
        'qtranslate_wysiwyg' => 'html',
    );

    private const COMPOUND_TYPES = array( 'group', 'repeater', 'flexible_content' );
    private int $maximumDepth;

    public function __construct( int $maximumDepth = 32 ) {
        if ( $maximumDepth < 1 ) {
            throw new InvalidArgumentException( 'ACF field schema depth must be positive.' );
        }
        $this->maximumDepth = $maximumDepth;
    }

    /** @param array<int, array<string, mixed>> $fields
     *  @return array<string, AcfFieldDefinition>
     */
    public function discover( array $fields ): array {
        $definitions = array();
        $this->walk( $fields, $definitions, 0 );

        return $definitions;
    }

    /** @param array<int, array<string, mixed>> $fields
     *  @param array<string, AcfFieldDefinition> $definitions
     */
    private function walk( array $fields, array &$definitions, int $depth ): void {
        if ( $depth >= $this->maximumDepth ) {
            throw new InvalidArgumentException( 'ACF compound field nesting exceeds the configured limit.' );
        }
        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) || ! isset( $field['type'] ) || ! is_string( $field['type'] ) ) {
                continue;
            }
            $type = $field['type'];
            if ( isset( self::LEAF_TYPES[ $type ] ) ) {
                $key = $field['key'] ?? '';
                // ACF accepts developer-defined keys containing Unicode letters.
                // Keep the stable field_ prefix and a deliberately narrow body:
                // letters, decimal numbers, underscore and hyphen only.
                if ( ! is_string( $key ) || preg_match( '/^field_[\p{L}\p{N}_-]+$/u', $key ) !== 1 ) {
                    continue;
                }
                if ( isset( $definitions[ $key ] ) ) {
                    if ( $definitions[ $key ]->type() !== $type ) {
                        throw new InvalidArgumentException( 'Conflicting ACF field key: ' . $key );
                    }
                    continue;
                }
                $definitions[ $key ] = new AcfFieldDefinition( $key, $type, self::LEAF_TYPES[ $type ] );
                continue;
            }
            if ( ! in_array( $type, self::COMPOUND_TYPES, true ) ) {
                continue;
            }
            if ( $type === 'flexible_content' ) {
                foreach ( $field['layouts'] ?? array() as $layout ) {
                    if ( is_array( $layout ) && isset( $layout['sub_fields'] ) && is_array( $layout['sub_fields'] ) ) {
                        $this->walk( $layout['sub_fields'], $definitions, $depth + 1 );
                    }
                }
            } elseif ( isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
                $this->walk( $field['sub_fields'], $definitions, $depth + 1 );
            }
        }
    }
}

<?php

namespace QTX\Integration\Acf;

use QTX\Core\Rest\EditorFieldMergeService;
use QTX\Core\Rest\EditorFieldState;
use QTX\Core\Rest\EditorMergeResult;

final class AcfAdminEditingService {
    private AcfFieldSchema $schema;
    private EditorFieldMergeService $mergeService;

    public function __construct( AcfFieldSchema $schema, EditorFieldMergeService $mergeService ) {
        $this->schema = $schema;
        $this->mergeService = $mergeService;
    }

    /** @param array<string, mixed> $field */
    public function project( array $field, $value ): ?EditorFieldState {
        if ( ! $this->isEditableLeaf( $field ) || ! is_string( $value ) || $this->looksSerialized( $value ) ) {
            return null;
        }

        return $this->mergeService->project( $value );
    }

    /** @param array<string, mixed> $field */
    public function merge(
        array $field,
        $currentValue,
        string $expectedRevision,
        string $language,
        string $newValue
    ): ?EditorMergeResult {
        if ( ! $this->isEditableLeaf( $field ) || ! is_string( $currentValue ) || $this->looksSerialized( $currentValue ) ) {
            return null;
        }

        return $this->mergeService->merge( $currentValue, $expectedRevision, $language, $newValue );
    }

    /** @param array<string, mixed> $field */
    private function isEditableLeaf( array $field ): bool {
        $key = isset( $field['key'] ) && is_string( $field['key'] ) ? $field['key'] : '';
        if ( $key === '' ) {
            return false;
        }

        return isset( $this->schema->discover( array( $field ) )[ $key ] );
    }

    private function looksSerialized( string $value ): bool {
        $trimmed = trim( $value );

        return $trimmed === 'N;' || preg_match( '/^(?:a|b|d|i|O|C|s):\d+(?::|;)/', $trimmed ) === 1;
    }
}

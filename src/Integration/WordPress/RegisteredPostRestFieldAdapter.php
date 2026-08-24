<?php

namespace QTX\Integration\WordPress;

use QTX\Core\Rest\EditorFieldMergeService;

final class RegisteredPostRestFieldAdapter {
    private const FIELDS = array( 'title', 'content', 'excerpt' );

    private EditorFieldMergeService $mergeService;
    /** @var callable */
    private $canEdit;
    /** @var callable */
    private $readRaw;
    /** @var callable */
    private $writeRaw;
    /** @var string[] */
    private array $registeredPostTypes = array();

    public function __construct( EditorFieldMergeService $mergeService, callable $canEdit, callable $readRaw, callable $writeRaw ) {
        $this->mergeService = $mergeService;
        $this->canEdit = $canEdit;
        $this->readRaw = $readRaw;
        $this->writeRaw = $writeRaw;
    }

    /** @param string[] $postTypes */
    public function register( array $postTypes ): void {
        foreach ( array_values( array_unique( $postTypes ) ) as $postType ) {
            if ( ! is_string( $postType ) || preg_match( '/^[a-z][a-z0-9_-]*$/', $postType ) !== 1 ) {
                continue;
            }
            if ( in_array( $postType, $this->registeredPostTypes, true ) ) {
                continue;
            }
            register_rest_field( $postType, 'qtx', array(
                'get_callback' => array( $this, 'getField' ),
                'update_callback' => array( $this, 'updateField' ),
                'schema' => $this->schema(),
            ) );
            $this->registeredPostTypes[] = $postType;
        }
    }

    /** @param mixed $object
     *  @param mixed $request
     */
    public function getField( $object, string $fieldName = 'qtx', $request = null ) {
        $objectId = $this->objectId( $object );
        if ( $objectId < 1 || ! ( $this->canEdit )( $objectId ) ) {
            return new \WP_Error( 'qtx_rest_forbidden', 'Multilingual raw fields require edit permission.', array( 'status' => 403 ) );
        }
        $fields = array();
        foreach ( self::FIELDS as $field ) {
            $state = $this->mergeService->project( (string) ( $this->readRaw )( $objectId, $field ) );
            $fields[ $field ] = array(
                'raw' => $state->raw(),
                'translations' => $state->translations(),
                'syntax' => $state->syntax(),
                'revision' => $state->revision(),
                'diagnostics' => $state->diagnostics(),
            );
        }

        return array( 'fields' => $fields );
    }

    /** @param mixed $value
     *  @param mixed $object
     */
    public function updateField( $value, $object, string $fieldName = 'qtx' ) {
        $objectId = $this->objectId( $object );
        if ( $objectId < 1 || ! ( $this->canEdit )( $objectId ) ) {
            return new \WP_Error( 'qtx_rest_forbidden', 'Multilingual updates require edit permission.', array( 'status' => 403 ) );
        }
        if ( ! is_array( $value ) || ! isset( $value['language'], $value['fields'], $value['revisions'] )
             || ! is_string( $value['language'] ) || ! is_array( $value['fields'] ) || ! is_array( $value['revisions'] ) ) {
            return new \WP_Error( 'qtx_invalid_editor_payload', 'Invalid multilingual editor payload.', array( 'status' => 400 ) );
        }
        if ( $value['fields'] === array() || array_diff( array_keys( $value['fields'] ), self::FIELDS ) !== array() ) {
            return new \WP_Error( 'qtx_invalid_editor_fields', 'Only registered multilingual post fields may be updated.', array( 'status' => 400 ) );
        }

        $writes = array();
        foreach ( $value['fields'] as $field => $newValue ) {
            if ( ! is_string( $newValue ) || ! isset( $value['revisions'][ $field ] ) || ! is_string( $value['revisions'][ $field ] ) ) {
                return new \WP_Error( 'qtx_invalid_editor_payload', 'Each field requires a scalar value and revision.', array( 'status' => 400 ) );
            }
            $currentRaw = (string) ( $this->readRaw )( $objectId, $field );
            $result = $this->mergeService->merge( $currentRaw, $value['revisions'][ $field ], $value['language'], $newValue );
            if ( ! $result->isMerged() ) {
                $status = $result->status() === 'conflict' ? 409 : ( $result->status() === 'unsupported_source' ? 422 : 400 );
                return new \WP_Error(
                    'qtx_editor_' . $result->status(),
                    'Multilingual editor update was not applied.',
                    array( 'status' => $status, 'field' => $field, 'revision' => $result->revision() )
                );
            }
            $writes[ $field ] = $result->raw();
        }

        if ( ! ( $this->writeRaw )( $objectId, $writes ) ) {
            return new \WP_Error( 'qtx_editor_write_failed', 'Multilingual editor update failed.', array( 'status' => 500 ) );
        }

        return true;
    }

    public function schema(): array {
        return array(
            'description' => 'Privileged qTranslate-XT multilingual editor state.',
            'type' => 'object',
            'context' => array( 'edit' ),
            'properties' => array(
                'language' => array( 'type' => 'string' ),
                'fields' => array( 'type' => 'object' ),
                'revisions' => array( 'type' => 'object' ),
            ),
        );
    }

    private function objectId( $object ): int {
        if ( is_array( $object ) ) {
            return (int) ( $object['id'] ?? ( $object['ID'] ?? 0 ) );
        }
        if ( is_object( $object ) ) {
            return (int) ( $object->id ?? ( $object->ID ?? 0 ) );
        }

        return 0;
    }
}

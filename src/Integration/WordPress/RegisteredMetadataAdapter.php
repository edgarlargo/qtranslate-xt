<?php

namespace QTX\Integration\WordPress;

use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Storage\FieldDefinition;
use QTX\Core\Storage\FieldRegistry;
use QTX\Core\Storage\MetadataValue;
use QTX\Core\Storage\RegisteredValueAdapter;

final class RegisteredMetadataAdapter {
    private const HOOKS = array(
        FieldDefinition::STORAGE_POST_META => array( 'get_post_metadata', 'updated_post_meta' ),
        FieldDefinition::STORAGE_TERM_META => array( 'get_term_metadata', 'updated_term_meta' ),
        FieldDefinition::STORAGE_USER_META => array( 'get_user_metadata', 'updated_user_meta' ),
    );

    private FieldRegistry $registry;
    private RegisteredValueAdapter $valueAdapter;
    private LanguageRequest $request;
    private LanguageContext $context;
    /** @var callable */
    private $reader;
    /** @var callable */
    private $invalidator;
    /** @var array<string, array{filter: callable, update: callable}> */
    private array $callbacks = array();

    public function __construct(
        FieldRegistry $registry,
        RegisteredValueAdapter $valueAdapter,
        LanguageRequest $request,
        LanguageContext $context,
        callable $reader,
        callable $invalidator
    ) {
        $this->registry = $registry;
        $this->valueAdapter = $valueAdapter;
        $this->request = $request;
        $this->context = $context;
        $this->reader = $reader;
        $this->invalidator = $invalidator;
    }

    public function register(): void {
        foreach ( self::HOOKS as $storage => $hooks ) {
            if ( $this->registry->forStorage( $storage ) === array() || isset( $this->callbacks[ $storage ] ) ) {
                continue;
            }
            $filter = function ( $original, int $objectId, string $key = '', bool $single = false ) use ( $storage ) {
                return $this->filter( $storage, $original, $objectId, $key, $single );
            };
            $update = function ( int $metaId, int $objectId, string $key, $value ) use ( $storage ): void {
                if ( $this->registry->has( $storage, $key ) ) {
                    ( $this->invalidator )( $storage, $objectId, $key );
                }
            };
            $this->callbacks[ $storage ] = array( 'filter' => $filter, 'update' => $update );
            add_filter( $hooks[0], $filter, 5, 4 );
            add_action( $hooks[1], $update, 5, 4 );
        }
    }

    public function unregister(): void {
        foreach ( $this->callbacks as $storage => $callbacks ) {
            $hooks = self::HOOKS[ $storage ];
            remove_filter( $hooks[0], $callbacks['filter'], 5 );
            remove_action( $hooks[1], $callbacks['update'], 5 );
        }
        $this->callbacks = array();
    }

    public function filter( string $storage, $original, int $objectId, string $key = '', bool $single = false ) {
        if ( $original !== null || ! $single || ! $this->registry->has( $storage, $key ) ) {
            return $original;
        }

        $metadata = ( $this->reader )( $storage, $objectId, $key );
        if ( ! $metadata instanceof MetadataValue || ! $metadata->isSupported() ) {
            return null;
        }

        return $this->valueAdapter->translate(
            $storage,
            $key,
            $metadata->value(),
            $this->request,
            $this->context
        );
    }
}

<?php

namespace QTX\Integration\WordPress;

use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Storage\FieldDefinition;
use QTX\Core\Storage\FieldRegistry;
use QTX\Core\Storage\RegisteredValueAdapter;

final class RegisteredOptionAdapter {
    private FieldRegistry $registry;
    private RegisteredValueAdapter $valueAdapter;
    private LanguageRequest $request;
    private LanguageContext $context;
    /** @var array<string, callable> */
    private array $callbacks = array();

    public function __construct(
        FieldRegistry $registry,
        RegisteredValueAdapter $valueAdapter,
        LanguageRequest $request,
        LanguageContext $context
    ) {
        $this->registry = $registry;
        $this->valueAdapter = $valueAdapter;
        $this->request = $request;
        $this->context = $context;
    }

    public function register(): void {
        foreach ( $this->registry->forStorage( FieldDefinition::STORAGE_OPTION ) as $definition ) {
            $key = $definition->key();
            if ( isset( $this->callbacks[ $key ] ) ) {
                continue;
            }
            $callback = function ( $value ) use ( $key ) {
                return $this->valueAdapter->translate(
                    FieldDefinition::STORAGE_OPTION,
                    $key,
                    $value,
                    $this->request,
                    $this->context
                );
            };
            $this->callbacks[ $key ] = $callback;
            add_filter( 'option_' . $key, $callback, 5, 1 );
        }
    }

    public function unregister(): void {
        foreach ( $this->callbacks as $key => $callback ) {
            remove_filter( 'option_' . $key, $callback, 5 );
        }
        $this->callbacks = array();
    }
}

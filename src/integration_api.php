<?php

use QTX\Core\Integration\IntegrationDefinition;
use QTX\Core\Integration\IntegrationRegistry;
use QTX\Core\Storage\FieldDefinition;
use QTX\Integration\WordPress\TermTranslationRepository;

function qtx_get_integration_registry(): IntegrationRegistry {
    static $registry;
    if ( ! isset( $registry ) ) {
        $registry = new IntegrationRegistry();
    }

    return $registry;
}

function qtx_register_integration( IntegrationDefinition $integration ): void {
    qtx_get_integration_registry()->registerIntegration( $integration );
}

function qtx_register_multilingual_field( FieldDefinition $field ): void {
    qtx_get_integration_registry()->registerField( $field );
}

function qtx_register_value_adapter( string $id, object $adapter ): void {
    qtx_get_integration_registry()->registerValueAdapter( $id, $adapter );
}

function qtx_boot_integration_registry(): void {
    static $booted = false;
    if ( $booted ) {
        return;
    }
    $booted = true;

    /**
     * Register QTX 4 integrations, multilingual fields and value adapters.
     *
     * Registration originates from executing trusted PHP. Stored options are
     * never interpreted as service definitions or executable paths.
     *
     * @param IntegrationRegistry $registry
     */
    do_action( 'qtx_register_integrations', qtx_get_integration_registry() );
}

function qtx_get_term_translation_repository(): TermTranslationRepository {
    static $repository;
    if ( ! isset( $repository ) ) {
        $repository = new TermTranslationRepository();
    }

    return $repository;
}

<?php

namespace QTX\Integration\WordPress;

final class TermTranslationRepository {
    public const META_KEY = '_qtx_term_name_translations';
    public const LEGACY_OPTION = 'qtranslate_term_name';

    public function translations( $term ): array {
        global $q_config;
        $termId = $this->termId( $term );
        if ( $termId > 0 ) {
            $stored = get_term_meta( $termId, self::META_KEY, true );
            if ( $this->isTranslationMap( $stored ) ) {
                return $stored;
            }
        }

        $name = is_object( $term ) && isset( $term->name ) ? (string) $term->name : '';
        $legacy = $q_config['term_name'][ $name ] ?? null;

        return $this->isTranslationMap( $legacy ) ? $legacy : array();
    }

    public function store( int $termId, string $defaultName, array $translations ): void {
        global $q_config;
        update_term_meta( $termId, self::META_KEY, $translations );
        $q_config['term_name'][ $defaultName ] = $translations;
        update_option( self::LEGACY_OPTION, $q_config['term_name'] );
    }

    /** @param string[] $legacyNames */
    public function delete( int $termId, array $legacyNames ): void {
        global $q_config;
        delete_term_meta( $termId, self::META_KEY );
        $changed = false;
        foreach ( $legacyNames as $name ) {
            if ( isset( $q_config['term_name'][ $name ] ) ) {
                unset( $q_config['term_name'][ $name ] );
                $changed = true;
            }
        }
        if ( $changed ) {
            update_option( self::LEGACY_OPTION, $q_config['term_name'] );
        }
    }

    private function termId( $term ): int {
        if ( ! is_object( $term ) ) {
            return 0;
        }
        if ( isset( $term->term_id ) ) {
            return (int) $term->term_id;
        }
        if ( isset( $term->ID ) ) {
            return (int) $term->ID;
        }

        return 0;
    }

    private function isTranslationMap( $value ): bool {
        if ( ! is_array( $value ) || $value === array() ) {
            return false;
        }
        foreach ( $value as $language => $translation ) {
            if ( ! is_string( $language ) || ! is_string( $translation ) ) {
                return false;
            }
        }

        return true;
    }
}

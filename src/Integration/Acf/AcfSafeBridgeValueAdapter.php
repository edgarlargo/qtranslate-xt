<?php

namespace QTX\Integration\Acf;

/**
 * Standalone-compatible ACF scalar fallback.
 *
 * This intentionally does not depend on the legacy ACF integration module
 * state. Theme-embedded ACF and older Options Pages can therefore use the same
 * late frontend projection as qTranslate-XT ACF Options Bridge Safe 0.4.0.
 */
final class AcfSafeBridgeValueAdapter {
    /** @var callable */
    private $shouldTranslate;
    /** @var callable */
    private $translate;
    private bool $registered = false;

    public function __construct( ?callable $shouldTranslate = null, ?callable $translate = null ) {
        $this->shouldTranslate = $shouldTranslate ?? static function (): bool {
            return ! ( is_admin() && ! wp_doing_ajax() );
        };
        $this->translate = $translate ?? static function ( string $value ): string {
            return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
        };
    }

    public function register(): void {
        if ( $this->registered ) {
            return;
        }
        foreach ( array( 'text', 'textarea', 'wysiwyg' ) as $type ) {
            add_filter( 'acf/format_value/type=' . $type, array( $this, 'formatValue' ), 99, 3 );
        }
        $this->registered = true;
    }

    /** @param mixed $value
     *  @param mixed $postId
     *  @param mixed $field
     *  @return mixed
     */
    public function formatValue( $value, $postId = null, $field = null ) {
        if ( ! ( $this->shouldTranslate )() || ! is_string( $value ) || strpos( $value, '[:' ) === false ) {
            return $value;
        }

        return ( $this->translate )( $value );
    }
}

<?php

namespace QTX\Integration\Acf;

final class AcfRuntimeDetector {
    private string $minimumVersion;
    /** @var callable|null */
    private $proPredicate;

    public function __construct( string $minimumVersion = '5.6.0', ?callable $proPredicate = null ) {
        $this->minimumVersion = $minimumVersion;
        $this->proPredicate = $proPredicate;
    }

    public function detect(): AcfRuntime {
        if ( ! function_exists( 'acf' ) ) {
            return new AcfRuntime( false, null, false );
        }
        $instance = acf();
        if ( ! is_object( $instance ) ) {
            return new AcfRuntime( false, null, false );
        }

        $version = $this->version( $instance );
        $available = $version !== null && version_compare( $version, $this->minimumVersion, '>=' );
        $pro = $this->proPredicate !== null
            ? (bool) ( $this->proPredicate )()
            : ( ( defined( 'ACF_PRO' ) && ACF_PRO ) || function_exists( 'acf_pro_get_license' ) );

        return new AcfRuntime( $available, $version, $pro );
    }

    private function version( object $instance ): ?string {
        $version = function_exists( 'acf_get_setting' ) ? acf_get_setting( 'version' ) : null;
        if ( ! is_string( $version ) || $version === '' ) {
            $version = isset( $instance->settings['version'] ) ? $instance->settings['version'] : null;
        }
        if ( ( ! is_string( $version ) || $version === '' ) && defined( 'ACF_VERSION' ) ) {
            $version = ACF_VERSION;
        }

        return is_string( $version ) && $version !== '' ? $version : null;
    }
}

<?php

namespace QTX\Integration\Acf;

/**
 * Defers the ACF adapter until the official ACF runtime is ready.
 *
 * Registration intentionally happens before themes are loaded. This lets a
 * theme-bundled or custom-path ACF instance announce itself through acf/init
 * without qTranslate-XT guessing its filesystem location or plugin basename.
 */
final class AcfRuntimeBootstrap {
    private AcfRuntimeDetector $detector;
    /** @var callable */
    private $initializer;
    private bool $registered = false;
    private bool $initialized = false;

    public function __construct( AcfRuntimeDetector $detector, callable $initializer ) {
        $this->detector = $detector;
        $this->initializer = $initializer;
    }

    public function register(): void {
        if ( $this->registered ) {
            return;
        }
        $this->registered = true;

        add_action( 'acf/init', array( $this, 'initialize' ), 5 );
        // Compatibility fallback for ACF embedded after normal plugin loading.
        add_action( 'after_setup_theme', array( $this, 'initialize' ), 20 );
        add_action( 'init', array( $this, 'initialize' ), 20 );

        if ( function_exists( 'did_action' ) && did_action( 'acf/init' ) > 0 ) {
            $this->initialize();
        }
    }

    public function initialize(): void {
        if ( $this->initialized ) {
            return;
        }

        $runtime = $this->detector->detect();
        if ( ! $runtime->isAvailable() ) {
            return;
        }

        $this->initialized = true;
        ( $this->initializer )( $runtime );
    }

    public function isInitialized(): bool {
        return $this->initialized;
    }
}

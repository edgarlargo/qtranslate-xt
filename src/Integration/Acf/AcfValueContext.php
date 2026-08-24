<?php

namespace QTX\Integration\Acf;

use InvalidArgumentException;

/** Explicit raw/translated policy for one ACF request. */
final class AcfValueContext {
    public const RAW = 'raw';
    public const TRANSLATED = 'translated';

    private string $mode;

    public function __construct( string $mode ) {
        if ( ! in_array( $mode, array( self::RAW, self::TRANSLATED ), true ) ) {
            throw new InvalidArgumentException( 'Invalid ACF value context.' );
        }
        $this->mode = $mode;
    }

    public function mode(): string {
        return $this->mode;
    }

    public function shouldTranslate(): bool {
        return $this->mode === self::TRANSLATED;
    }
}

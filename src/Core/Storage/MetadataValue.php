<?php

namespace QTX\Core\Storage;

final class MetadataValue {
    private bool $supported;
    /** @var mixed */
    private $value;

    private function __construct( bool $supported, $value ) {
        $this->supported = $supported;
        $this->value = $value;
    }

    public static function scalar( string $value ): self {
        return new self( true, $value );
    }

    public static function unsupported(): self {
        return new self( false, null );
    }

    public function isSupported(): bool {
        return $this->supported;
    }

    public function value(): ?string {
        return $this->supported ? $this->value : null;
    }
}

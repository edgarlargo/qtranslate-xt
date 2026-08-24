<?php

namespace QTX\Core\Multilingual;

final class FallbackPolicy {
    /** @var bool */
    private $showEmpty;
    /** @var bool */
    private $useDefaultFirst;
    /** @var bool */
    private $useFirstAvailable;

    public function __construct( bool $showEmpty, bool $useDefaultFirst, bool $useFirstAvailable ) {
        $this->showEmpty         = $showEmpty;
        $this->useDefaultFirst   = $useDefaultFirst;
        $this->useFirstAvailable = $useFirstAvailable;
    }

    public static function legacy( bool $showEmpty = false ): self {
        return new self( $showEmpty, false, true );
    }

    public function showEmpty(): bool {
        return $this->showEmpty;
    }

    public function useDefaultFirst(): bool {
        return $this->useDefaultFirst;
    }

    public function useFirstAvailable(): bool {
        return $this->useFirstAvailable;
    }
}

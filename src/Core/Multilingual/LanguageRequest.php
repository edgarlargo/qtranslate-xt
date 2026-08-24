<?php

namespace QTX\Core\Multilingual;

final class LanguageRequest {
    /** @var string */
    private $language;
    /** @var FallbackPolicy */
    private $fallbackPolicy;

    public function __construct( string $language, FallbackPolicy $fallbackPolicy ) {
        $this->language       = $language;
        $this->fallbackPolicy = $fallbackPolicy;
    }

    public function language(): string {
        return $this->language;
    }

    public function fallbackPolicy(): FallbackPolicy {
        return $this->fallbackPolicy;
    }
}

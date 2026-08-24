<?php

namespace QTX\Core\Multilingual;

final class MultilingualEntry {
    public const OPENING = 'opening';
    public const CLOSING = 'closing';
    public const TEXT    = 'text';

    /** @var string */
    private $kind;
    /** @var string */
    private $raw;
    /** @var string|null */
    private $language;
    /** @var string|null */
    private $syntax;

    public function __construct( string $kind, string $raw, ?string $language = null, ?string $syntax = null ) {
        $this->kind     = $kind;
        $this->raw      = $raw;
        $this->language = $language;
        $this->syntax   = $syntax;
    }

    public function kind(): string {
        return $this->kind;
    }

    public function raw(): string {
        return $this->raw;
    }

    public function language(): ?string {
        return $this->language;
    }

    public function syntax(): ?string {
        return $this->syntax;
    }
}

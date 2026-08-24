<?php

namespace QTX\Core\Multilingual;

final class TranslationResult {
    /** @var string */
    private $text;
    /** @var string|null */
    private $language;
    /** @var string */
    private $reason;
    /** @var string[] */
    private $availableLanguages;

    /** @param string[] $availableLanguages */
    public function __construct( string $text, ?string $language, string $reason, array $availableLanguages ) {
        $this->text               = $text;
        $this->language           = $language;
        $this->reason             = $reason;
        $this->availableLanguages = array_values( $availableLanguages );
    }

    public function text(): string {
        return $this->text;
    }

    public function language(): ?string {
        return $this->language;
    }

    public function reason(): string {
        return $this->reason;
    }

    /** @return string[] */
    public function availableLanguages(): array {
        return $this->availableLanguages;
    }
}

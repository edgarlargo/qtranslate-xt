<?php

namespace QTX\Core\Multilingual;

final class MultilingualValue {
    /** @var string */
    private $raw;
    /** @var string */
    private $syntax;
    /** @var MultilingualEntry[] */
    private $entries;
    /** @var string[] */
    private $translations;
    /** @var string[] */
    private $encodedTranslations;
    /** @var string[] */
    private $availableLanguages;
    /** @var string[] */
    private $diagnostics;
    /** @var bool */
    private $multilingual;
    /** @var bool */
    private $changed;

    /**
     * @param MultilingualEntry[] $entries
     * @param string[]            $translations
     * @param string[]            $encodedTranslations
     * @param string[]            $availableLanguages
     * @param string[]            $diagnostics
     */
    public function __construct(
        string $raw,
        string $syntax,
        array $entries,
        array $translations,
        array $encodedTranslations,
        array $availableLanguages,
        array $diagnostics,
        bool $multilingual,
        bool $changed = false
    ) {
        $this->raw                 = $raw;
        $this->syntax              = $syntax;
        $this->entries             = array_values( $entries );
        $this->translations        = $translations;
        $this->encodedTranslations = $encodedTranslations;
        $this->availableLanguages  = array_values( $availableLanguages );
        $this->diagnostics         = array_values( array_unique( $diagnostics ) );
        $this->multilingual        = $multilingual;
        $this->changed             = $changed;
    }

    public function raw(): string {
        return $this->raw;
    }

    public function syntax(): string {
        return $this->syntax;
    }

    /** @return MultilingualEntry[] */
    public function entries(): array {
        return $this->entries;
    }

    /** @return string[] */
    public function translations(): array {
        return $this->translations;
    }

    /** @return string[] */
    public function encodedTranslations(): array {
        return $this->encodedTranslations;
    }

    /** @return string[] */
    public function availableLanguages(): array {
        return $this->availableLanguages;
    }

    /** @return string[] */
    public function diagnostics(): array {
        return $this->diagnostics;
    }

    public function isMultilingual(): bool {
        return $this->multilingual;
    }

    public function isChanged(): bool {
        return $this->changed;
    }
}

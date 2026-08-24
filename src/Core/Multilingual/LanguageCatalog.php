<?php

namespace QTX\Core\Multilingual;

final class LanguageCatalog {
    /** @var string[] */
    private $codes;
    /** @var string */
    private $defaultLanguage;

    /** @param string[] $codes */
    public function __construct( array $codes, string $defaultLanguage ) {
        $normalized = array_values( array_unique( $codes ) );
        if ( $normalized === array() || ! in_array( $defaultLanguage, $normalized, true ) ) {
            throw new \InvalidArgumentException( 'Default language must belong to the language catalog.' );
        }
        foreach ( $normalized as $code ) {
            if ( ! is_string( $code ) || preg_match( '/^[a-z]{2,3}$/i', $code ) !== 1 ) {
                throw new \InvalidArgumentException( 'Invalid language code in catalog.' );
            }
        }
        $this->codes           = $normalized;
        $this->defaultLanguage = $defaultLanguage;
    }

    /** @return string[] */
    public function codes(): array {
        return $this->codes;
    }

    public function defaultLanguage(): string {
        return $this->defaultLanguage;
    }

    public function contains( string $language ): bool {
        return in_array( $language, $this->codes, true );
    }
}

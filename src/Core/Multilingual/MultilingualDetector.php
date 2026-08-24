<?php

namespace QTX\Core\Multilingual;

final class MultilingualDetector {
    /** @var string */
    private $languageCodePattern;

    public function __construct( string $languageCodePattern = '[a-z]{2,3}' ) {
        $this->languageCodePattern = $languageCodePattern;
    }

    public function isMultilingual( ?string $raw ): bool {
        if ( $raw === null || $raw === '' ) {
            return false;
        }

        $language = $this->languageCodePattern;

        return preg_match( "/<!--:$language-->|\[:$language]|{:$language}/im", $raw ) === 1;
    }
}

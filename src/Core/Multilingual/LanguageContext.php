<?php

namespace QTX\Core\Multilingual;

final class LanguageContext {
    /** @var LanguageCatalog */
    private $catalog;
    /** @var string */
    private $currentLanguage;

    public function __construct( LanguageCatalog $catalog, string $currentLanguage ) {
        if ( ! $catalog->contains( $currentLanguage ) ) {
            throw new \InvalidArgumentException( 'Current language must belong to the language catalog.' );
        }
        $this->catalog         = $catalog;
        $this->currentLanguage = $currentLanguage;
    }

    public function catalog(): LanguageCatalog {
        return $this->catalog;
    }

    public function current(): string {
        return $this->currentLanguage;
    }

    public function default(): string {
        return $this->catalog->defaultLanguage();
    }
}

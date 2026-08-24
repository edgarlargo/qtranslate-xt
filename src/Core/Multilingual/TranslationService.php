<?php

namespace QTX\Core\Multilingual;

final class TranslationService {
    /** @var LanguageResolver */
    private $resolver;

    public function __construct( ?LanguageResolver $resolver = null ) {
        $this->resolver = $resolver ?? new LanguageResolver();
    }

    public function get( MultilingualValue $value, LanguageRequest $request, LanguageContext $context ): TranslationResult {
        if ( count( $value->entries() ) <= 1 ) {
            return new TranslationResult( $value->raw(), null, 'plain', array() );
        }

        $available = array();
        foreach ( array_keys( $value->encodedTranslations() ) as $language ) {
            $available[ $language ] = true;
        }

        return $this->resolver->resolve( $value->translations(), $available, $request, $context );
    }

    /**
     * @param string[] $translations
     * @param bool[]   $available
     * @param string[] $enabledLanguages
     */
    public function select(
        array $translations,
        array $available,
        string $requestedLanguage,
        array $enabledLanguages,
        bool $showEmpty
    ): TranslationResult {
        if ( $enabledLanguages === array() ) {
            return new TranslationResult( '', null, 'unavailable', array() );
        }

        $catalog = new LanguageCatalog( $enabledLanguages, $enabledLanguages[0] );
        $context = new LanguageContext( $catalog, $enabledLanguages[0] );
        $request = new LanguageRequest( $requestedLanguage, FallbackPolicy::legacy( $showEmpty ) );

        return $this->resolver->resolve( $translations, $available, $request, $context );
    }
}

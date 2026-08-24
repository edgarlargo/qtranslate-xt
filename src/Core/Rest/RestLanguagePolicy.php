<?php

namespace QTX\Core\Rest;

use QTX\Core\Multilingual\LanguageContext;

final class RestLanguagePolicy {
    private LanguageContext $languageContext;

    public function __construct( LanguageContext $languageContext ) {
        $this->languageContext = $languageContext;
    }

    public function resolve( ?string $requestedLanguage, string $restContext, bool $rawRequested, bool $canEdit ): RestTranslationContext {
        if ( $restContext !== 'view' && $restContext !== 'edit' ) {
            return new RestTranslationContext( false, null, RestTranslationContext::MODE_TRANSLATED, 'invalid_context' );
        }
        $language = $requestedLanguage === null || $requestedLanguage === ''
            ? $this->languageContext->current()
            : $requestedLanguage;
        if ( ! $this->languageContext->catalog()->contains( $language ) ) {
            return new RestTranslationContext( false, null, RestTranslationContext::MODE_TRANSLATED, 'invalid_language' );
        }
        if ( $restContext === 'edit' && ! $canEdit ) {
            return new RestTranslationContext( false, $language, RestTranslationContext::MODE_TRANSLATED, 'edit_forbidden' );
        }
        if ( $rawRequested ) {
            if ( $restContext !== 'edit' || ! $canEdit ) {
                return new RestTranslationContext( false, $language, RestTranslationContext::MODE_TRANSLATED, 'raw_forbidden' );
            }

            return new RestTranslationContext( true, $language, RestTranslationContext::MODE_RAW );
        }

        return new RestTranslationContext( true, $language, RestTranslationContext::MODE_TRANSLATED );
    }
}

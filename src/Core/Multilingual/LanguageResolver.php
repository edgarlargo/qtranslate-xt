<?php

namespace QTX\Core\Multilingual;

final class LanguageResolver {
    /**
     * @param string[] $translations
     * @param bool[]   $available
     */
    public function resolve(
        array $translations,
        array $available,
        LanguageRequest $request,
        LanguageContext $context
    ): TranslationResult {
        $language = $request->language();
        $policy   = $request->fallbackPolicy();
        if ( ! empty( $available[ $language ] ) ) {
            return new TranslationResult( $translations[ $language ], $language, 'exact', array( $language ) );
        }
        if ( $policy->showEmpty() ) {
            return new TranslationResult( '', $language, 'empty', array() );
        }

        $ordered = array();
        if ( $policy->useDefaultFirst() && ! empty( $available[ $context->default() ] ) ) {
            $ordered[] = $context->default();
        }
        if ( $policy->useFirstAvailable() ) {
            foreach ( $context->catalog()->codes() as $candidate ) {
                if ( ! empty( $available[ $candidate ] ) && ! in_array( $candidate, $ordered, true ) ) {
                    $ordered[] = $candidate;
                }
            }
        }
        if ( $ordered === array() ) {
            return new TranslationResult( '', null, 'unavailable', array() );
        }

        $selected = $ordered[0];
        $reason   = $policy->useDefaultFirst() && $selected === $context->default() ? 'default' : 'first-available';

        return new TranslationResult( $translations[ $selected ], $selected, $reason, $ordered );
    }
}

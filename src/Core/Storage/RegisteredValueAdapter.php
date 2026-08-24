<?php

namespace QTX\Core\Storage;

use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Multilingual\TranslationService;

final class RegisteredValueAdapter {
    private FieldRegistry $registry;
    private MultilingualParser $parser;
    private TranslationService $translationService;

    public function __construct(
        FieldRegistry $registry,
        MultilingualParser $parser,
        ?TranslationService $translationService = null
    ) {
        $this->registry = $registry;
        $this->parser = $parser;
        $this->translationService = $translationService ?? new TranslationService();
    }

    public function translate(
        string $storage,
        string $key,
        $value,
        LanguageRequest $request,
        LanguageContext $context
    ) {
        if ( ! $this->registry->has( $storage, $key ) || ! is_string( $value ) ) {
            return $value;
        }
        if ( $this->looksSerialized( $value ) ) {
            return $value;
        }

        $parsed = $this->parser->parse( $value );

        return $this->translationService->get( $parsed, $request, $context )->text();
    }

    private function looksSerialized( string $value ): bool {
        $trimmed = trim( $value );
        if ( $trimmed === 'N;' ) {
            return true;
        }

        return preg_match( '/^(?:a|b|d|i|O|C|s):\d+(?::|;)/', $trimmed ) === 1;
    }
}

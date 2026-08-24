<?php

namespace QTX\Integration\Acf;

use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Multilingual\TranslationService;

final class AcfScalarTranslator {
    private MultilingualParser $parser;
    private TranslationService $service;
    private LanguageRequest $request;
    private LanguageContext $context;

    public function __construct(
        MultilingualParser $parser,
        TranslationService $service,
        LanguageRequest $request,
        LanguageContext $context
    ) {
        $this->parser = $parser;
        $this->service = $service;
        $this->request = $request;
        $this->context = $context;
    }

    public function __invoke( string $raw, AcfFieldDefinition $field ): string {
        return $this->service->get( $this->parser->parse( $raw ), $this->request, $this->context )->text();
    }
}

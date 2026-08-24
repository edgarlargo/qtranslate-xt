<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Rest\RestLanguagePolicy;
use QTX\Core\Rest\RestTranslationContext;

final class RestLanguagePolicyTest extends TestCase {
    private RestLanguagePolicy $policy;

    protected function setUp(): void {
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'lv' );
        $this->policy = new RestLanguagePolicy( new LanguageContext( $catalog, 'ru' ) );
    }

    public function testPublicViewIsTranslatedAndDefaultsToCurrentLanguage(): void {
        $context = $this->policy->resolve( null, 'view', false, false );
        self::assertTrue( $context->isAllowed() );
        self::assertSame( 'ru', $context->language() );
        self::assertSame( RestTranslationContext::MODE_TRANSLATED, $context->mode() );
    }

    public function testRequestedLanguageMustBelongToConfiguredCatalog(): void {
        $context = $this->policy->resolve( '../raw', 'view', false, false );
        self::assertFalse( $context->isAllowed() );
        self::assertSame( 'invalid_language', $context->error() );
        self::assertNull( $context->language() );
    }

    public function testRawRequiresEditContextAndCapability(): void {
        foreach ( array(
            array( 'view', false, false, 'raw_forbidden' ),
            array( 'view', true, false, 'raw_forbidden' ),
            array( 'edit', false, false, 'edit_forbidden' ),
        ) as $case ) {
            $context = $this->policy->resolve( 'lv', $case[0], true, $case[1] );
            self::assertFalse( $context->isAllowed() );
            self::assertSame( $case[3], $context->error() );
        }

        $context = $this->policy->resolve( 'en', 'edit', true, true );
        self::assertTrue( $context->isAllowed() );
        self::assertSame( RestTranslationContext::MODE_RAW, $context->mode() );
    }

    public function testEditRepresentationRequiresCapabilityEvenWhenTranslated(): void {
        self::assertFalse( $this->policy->resolve( 'ru', 'edit', false, false )->isAllowed() );
        self::assertTrue( $this->policy->resolve( 'ru', 'edit', false, true )->isAllowed() );
        self::assertSame( 'invalid_context', $this->policy->resolve( 'ru', 'embed', false, true )->error() );
    }
}

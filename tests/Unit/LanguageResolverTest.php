<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\FallbackPolicy;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Multilingual\LanguageResolver;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Multilingual\TranslationService;

final class LanguageResolverTest extends TestCase {
    public function testCatalogAndContextExposeDeterministicLanguages(): void {
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en', 'lv' ), 'en' );
        $context = new LanguageContext( $catalog, 'lv' );

        self::assertSame( array( 'lv', 'ru', 'en' ), $catalog->codes() );
        self::assertTrue( $catalog->contains( 'ru' ) );
        self::assertFalse( $catalog->contains( 'zz' ) );
        self::assertSame( 'lv', $context->current() );
        self::assertSame( 'en', $context->default() );
    }

    public function testInvalidCatalogAndContextAreRejected(): void {
        $this->expectException( \InvalidArgumentException::class );
        new LanguageCatalog( array( 'lv', 'ru' ), 'en' );
    }

    public function testInvalidCurrentContextIsRejected(): void {
        $catalog = new LanguageCatalog( array( 'lv', 'ru' ), 'lv' );
        $this->expectException( \InvalidArgumentException::class );
        new LanguageContext( $catalog, 'en' );
    }

    public function testResolverReasonsAndPoliciesAreExplicit(): void {
        $catalog      = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'en' );
        $context      = new LanguageContext( $catalog, 'lv' );
        $resolver     = new LanguageResolver();
        $translations = array( 'lv' => 'LV', 'ru' => 'RU', 'en' => 'EN' );
        $available    = array( 'lv' => true, 'ru' => true, 'en' => true );

        $exact = $resolver->resolve( $translations, $available, new LanguageRequest( 'ru', FallbackPolicy::legacy() ), $context );
        self::assertSame( 'RU', $exact->text() );
        self::assertSame( 'ru', $exact->language() );
        self::assertSame( 'exact', $exact->reason() );

        $default = $resolver->resolve(
            $translations,
            array( 'lv' => true, 'en' => true ),
            new LanguageRequest( 'ru', new FallbackPolicy( false, true, true ) ),
            $context
        );
        self::assertSame( 'EN', $default->text() );
        self::assertSame( 'default', $default->reason() );

        $empty = $resolver->resolve( $translations, array( 'lv' => true ), new LanguageRequest( 'ru', FallbackPolicy::legacy( true ) ), $context );
        self::assertSame( '', $empty->text() );
        self::assertSame( 'empty', $empty->reason() );
    }

    public function testTranslationServiceAcceptsParsedValueAndReturnsPlainReason(): void {
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'en' );
        $context = new LanguageContext( $catalog, 'lv' );
        $parser  = new MultilingualParser( $catalog->codes(), $context->current() );
        $service = new TranslationService();

        $plain = $service->get( $parser->parse( '<b>plain</b>' ), new LanguageRequest( 'lv', FallbackPolicy::legacy() ), $context );
        self::assertSame( '<b>plain</b>', $plain->text() );
        self::assertSame( 'plain', $plain->reason() );

        $translated = $service->get( $parser->parse( '[:lv]A[:ru]Б[:]' ), new LanguageRequest( 'ru', FallbackPolicy::legacy() ), $context );
        self::assertSame( 'Б', $translated->text() );
        self::assertSame( 'exact', $translated->reason() );
    }
}

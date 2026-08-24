<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\FallbackPolicy;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\LanguageContext;
use QTX\Core\Multilingual\LanguageRequest;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Storage\FieldDefinition;
use QTX\Core\Storage\FieldRegistry;
use QTX\Core\Storage\RegisteredValueAdapter;

final class RegisteredValueAdapterTest extends TestCase {
    private FieldRegistry $registry;
    private RegisteredValueAdapter $adapter;
    private LanguageRequest $request;
    private LanguageContext $context;

    protected function setUp(): void {
        $this->registry = new FieldRegistry();
        $this->registry->register( new FieldDefinition( 'option', 'tagline' ) );
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'lv' );
        $this->context = new LanguageContext( $catalog, 'ru' );
        $this->request = new LanguageRequest( 'ru', FallbackPolicy::legacy() );
        $this->adapter = new RegisteredValueAdapter(
            $this->registry,
            new MultilingualParser( $catalog->codes(), $this->context->current() )
        );
    }

    public function testTranslatesOnlyExplicitlyRegisteredScalarString(): void {
        $raw = '[:lv]Vietnes apraksts[:ru]Описание сайта[:en]Site description[:]';

        self::assertSame( 'Описание сайта', $this->adapter->translate( 'option', 'tagline', $raw, $this->request, $this->context ) );
        self::assertSame( $raw, $this->adapter->translate( 'option', 'unregistered', $raw, $this->request, $this->context ) );
        self::assertSame( $raw, $this->adapter->translate( 'post', 'tagline', $raw, $this->request, $this->context ) );
    }

    public function testDoesNotTraverseArraysOrObjects(): void {
        $array = array( 'nested' => '[:lv]Jā[:ru]Да[:]' );
        $object = (object) $array;

        self::assertSame( $array, $this->adapter->translate( 'option', 'tagline', $array, $this->request, $this->context ) );
        self::assertSame( $object, $this->adapter->translate( 'option', 'tagline', $object, $this->request, $this->context ) );
    }

    public function testDoesNotDeserializeOrRewriteSerializedLookingStrings(): void {
        $serialized = 'a:1:{s:4:"text";s:20:"[:lv]Jā[:ru]Да[:]";}';

        self::assertSame(
            $serialized,
            $this->adapter->translate( 'option', 'tagline', $serialized, $this->request, $this->context )
        );
    }

    public function testPlainRegisteredStringRemainsUnchanged(): void {
        self::assertSame( 'Plain value', $this->adapter->translate( 'option', 'tagline', 'Plain value', $this->request, $this->context ) );
    }
}

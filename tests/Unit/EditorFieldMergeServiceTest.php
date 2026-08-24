<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Rest\EditorFieldMergeService;

final class EditorFieldMergeServiceTest extends TestCase {
    private EditorFieldMergeService $service;

    protected function setUp(): void {
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'lv' );
        $this->service = new EditorFieldMergeService( $catalog, new MultilingualParser( $catalog->codes(), 'lv' ) );
    }

    public function testProjectionIsLosslessAndCarriesRevision(): void {
        $raw = '<!--:lv-->Sveiki<!--:--><!--:ru-->Привет<!--:-->';
        $state = $this->service->project( $raw );

        self::assertSame( $raw, $state->raw() );
        self::assertSame( 'Привет', $state->translations()['ru'] );
        self::assertSame( 'comment', $state->syntax() );
        self::assertSame( hash( 'sha256', $raw ), $state->revision() );
    }

    public function testMergePreservesSyntaxAndOtherLanguages(): void {
        $raw = '<!--:lv-->Sveiki<!--:--><!--:ru-->Привет<!--:-->';
        $result = $this->service->merge( $raw, hash( 'sha256', $raw ), 'ru', 'Здравствуйте' );

        self::assertTrue( $result->isMerged() );
        self::assertStringContainsString( '<!--:lv-->Sveiki<!--:-->', $result->raw() );
        self::assertStringContainsString( '<!--:ru-->Здравствуйте<!--:-->', $result->raw() );
        self::assertSame( hash( 'sha256', $result->raw() ), $result->revision() );
    }

    public function testStaleRevisionCannotOverwriteCurrentRaw(): void {
        $raw = '[:lv]Jauns[:ru]Новое[:]';
        $result = $this->service->merge( $raw, hash( 'sha256', 'older value' ), 'ru', 'Потеряно' );

        self::assertSame( 'conflict', $result->status() );
        self::assertSame( $raw, $result->raw() );
    }

    public function testInvalidLanguageMalformedAndDuplicateSourcesAreNotRebuilt(): void {
        $raw = '[:lv]Sveiki[:ru]Привет[:]';
        self::assertSame( 'invalid_language', $this->service->merge( $raw, hash( 'sha256', $raw ), '../', 'x' )->status() );

        $duplicate = '[:lv]Viens[:lv]Divi[:]';
        self::assertSame( 'unsupported_source', $this->service->merge( $duplicate, hash( 'sha256', $duplicate ), 'lv', 'x' )->status() );

        $malformed = '[:lv]Unclosed';
        self::assertSame( 'unsupported_source', $this->service->merge( $malformed, hash( 'sha256', $malformed ), 'lv', 'x' )->status() );
    }
}

<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Rest\EditorFieldMergeService;
use QTX\Integration\Acf\AcfAdminEditingService;
use QTX\Integration\Acf\AcfFieldSchema;

final class AcfAdminEditingServiceTest extends TestCase {
    private AcfAdminEditingService $service;

    protected function setUp(): void {
        $catalog = new LanguageCatalog( array( 'lv', 'ru', 'en' ), 'lv' );
        $this->service = new AcfAdminEditingService(
            new AcfFieldSchema(),
            new EditorFieldMergeService( $catalog, new MultilingualParser( $catalog->codes(), 'lv' ) )
        );
    }

    public function testProjectsAndReloadsStableWhitelistedLeafLosslessly(): void {
        $field = array( 'key' => 'field_heading', 'name' => 'heading', 'type' => 'text' );
        $raw = '<!--:lv-->Kontakti<!--:--><!--:ru-->Контакты<!--:--><!--:en-->Contacts<!--:-->';
        $state = $this->service->project( $field, $raw );

        self::assertNotNull( $state );
        self::assertSame( $raw, $state->raw() );
        self::assertSame( 'Контакты', $state->translations()['ru'] );
        self::assertSame( hash( 'sha256', $raw ), $state->revision() );
    }

    public function testEditSaveEmptyTranslationAndReloadPreserveOtherLanguagesAndSyntax(): void {
        $field = array( 'key' => 'field_copy', 'name' => 'copy', 'type' => 'wysiwyg' );
        $raw = '{:lv}<b>Saturs</b>{:}{:ru}<i>Текст</i>{:}{:en}<p>Copy</p>{:}';
        $state = $this->service->project( $field, $raw );
        $result = $this->service->merge( $field, $raw, $state->revision(), 'ru', '' );

        self::assertNotNull( $result );
        self::assertTrue( $result->isMerged() );
        self::assertSame( '{:lv}<b>Saturs</b>{:en}<p>Copy</p>{:}', $result->raw() );
        $reloaded = $this->service->project( $field, $result->raw() );
        self::assertSame( '<b>Saturs</b>', $reloaded->translations()['lv'] );
        self::assertSame( '<p>Copy</p>', $reloaded->translations()['en'] );
        self::assertSame( '', $reloaded->translations()['ru'] );
    }

    public function testStaleSaveConflictsForOptionsPageValue(): void {
        $field = array( 'key' => 'field_options_heading', 'name' => 'heading', 'type' => 'textarea' );
        $current = '[:lv]Jauns[:ru]Новое[:en]New[:]';
        $result = $this->service->merge( $field, $current, hash( 'sha256', 'old options value' ), 'en', 'Lost' );

        self::assertNotNull( $result );
        self::assertSame( 'conflict', $result->status() );
        self::assertSame( $current, $result->raw() );
    }

    public function testNestedLeavesUseStableFieldSchemaWithoutChangingRowStructure(): void {
        $group = array(
            'key' => 'field_group',
            'type' => 'group',
            'sub_fields' => array(
                array( 'key' => 'field_nested_text', 'name' => 'text', 'type' => 'text' ),
            ),
        );
        $leaf = $group['sub_fields'][0];
        $raw = '[:lv]Rinda[:ru]Строка[:]';
        $row = array( 'text' => $raw, 'layout' => 'unchanged' );
        $state = $this->service->project( $leaf, $row['text'] );
        $result = $this->service->merge( $leaf, $row['text'], $state->revision(), 'ru', 'Новая строка' );
        $row['text'] = $result->raw();

        self::assertSame( 'Новая строка', $this->service->project( $leaf, $row['text'] )->translations()['ru'] );
        self::assertSame( 'unchanged', $row['layout'] );
    }

    public function testTechnicalUnstableObjectAndSerializedValuesAreNotEditable(): void {
        self::assertNull( $this->service->project( array( 'key' => 'field_image', 'type' => 'image' ), 42 ) );
        self::assertNull( $this->service->project( array( 'key' => 'heading', 'type' => 'text' ), 'raw' ) );
        self::assertNull( $this->service->project( array( 'key' => 'field_text', 'type' => 'text' ), (object) array() ) );
        self::assertNull( $this->service->project( array( 'key' => 'field_text', 'type' => 'text' ), 'a:0:{}' ) );
    }
}

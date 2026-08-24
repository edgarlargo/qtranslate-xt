<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\MultilingualParser;
use QTX\Core\Rest\EditorFieldMergeService;
use QTX\Integration\WordPress\RegisteredPostRestFieldAdapter;

final class RegisteredPostRestFieldAdapterTest extends TestCase {
    private array $raw;
    private array $writes;

    protected function setUp(): void {
        $this->raw = array(
            'title' => '[:lv]Virsraksts[:ru]Заголовок[:]',
            'content' => '[:lv]Saturs[:ru]Содержимое[:]',
            'excerpt' => '[:lv]Izraksts[:ru]Отрывок[:]',
        );
        $this->writes = array();
        $GLOBALS['qtx_test_rest_fields'] = array();
    }

    public function testRegistersExactEditOnlyFieldSchemaIdempotently(): void {
        $adapter = $this->adapter( true );
        $adapter->register( array( 'post', 'page', 'post', '../bad' ) );

        self::assertCount( 2, $GLOBALS['qtx_test_rest_fields'] );
        self::assertSame( array( 'post', 'page' ), array_column( $GLOBALS['qtx_test_rest_fields'], 0 ) );
        self::assertSame( 'qtx', $GLOBALS['qtx_test_rest_fields'][0][1] );
        self::assertSame( array( 'edit' ), $GLOBALS['qtx_test_rest_fields'][0][2]['schema']['context'] );
    }

    public function testPrivilegedGetReturnsRawTranslationsAndRevision(): void {
        $state = $this->adapter( true )->getField( array( 'id' => 42 ) );

        self::assertSame( $this->raw['title'], $state['fields']['title']['raw'] );
        self::assertSame( 'Заголовок', $state['fields']['title']['translations']['ru'] );
        self::assertSame( hash( 'sha256', $this->raw['title'] ), $state['fields']['title']['revision'] );
    }

    public function testCapabilityIsRequiredForRawReadAndUpdate(): void {
        $adapter = $this->adapter( false );
        self::assertSame( 403, $adapter->getField( array( 'id' => 42 ) )->get_error_data()['status'] );
        self::assertSame( 403, $adapter->updateField( array(), array( 'id' => 42 ) )->get_error_data()['status'] );
    }

    public function testConflictReturns409BeforeAnyFieldIsWritten(): void {
        $payload = $this->payload();
        $payload['fields'] = array( 'title' => 'Новый', 'content' => 'Новый текст' );
        $payload['revisions']['content'] = hash( 'sha256', 'stale' );
        $result = $this->adapter( true )->updateField( $payload, array( 'id' => 42 ) );

        self::assertSame( 'qtx_editor_conflict', $result->get_error_code() );
        self::assertSame( 409, $result->get_error_data()['status'] );
        self::assertSame( array(), $this->writes );
    }

    public function testValidFieldsAreMergedAndWrittenTogether(): void {
        $payload = $this->payload();
        $payload['fields'] = array( 'title' => 'Новый', 'excerpt' => 'Новый отрывок' );
        self::assertTrue( $this->adapter( true )->updateField( $payload, (object) array( 'ID' => 42 ) ) );

        self::assertArrayHasKey( 'title', $this->writes );
        self::assertArrayHasKey( 'excerpt', $this->writes );
        self::assertStringContainsString( '[:ru]Новый', $this->writes['title'] );
        self::assertStringContainsString( '[:lv]Virsraksts', $this->writes['title'] );
    }

    private function payload(): array {
        return array(
            'language' => 'ru',
            'fields' => array(),
            'revisions' => array(
                'title' => hash( 'sha256', $this->raw['title'] ),
                'content' => hash( 'sha256', $this->raw['content'] ),
                'excerpt' => hash( 'sha256', $this->raw['excerpt'] ),
            ),
        );
    }

    private function adapter( bool $allowed ): RegisteredPostRestFieldAdapter {
        $catalog = new LanguageCatalog( array( 'lv', 'ru' ), 'lv' );
        $service = new EditorFieldMergeService( $catalog, new MultilingualParser( $catalog->codes(), 'lv' ) );

        return new RegisteredPostRestFieldAdapter(
            $service,
            static fn ( int $objectId ): bool => $allowed && $objectId === 42,
            fn ( int $objectId, string $field ): string => $this->raw[ $field ],
            function ( int $objectId, array $writes ): bool {
                $this->writes = $writes;
                return true;
            }
        );
    }
}

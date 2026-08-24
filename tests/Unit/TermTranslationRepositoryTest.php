<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\WordPress\TermTranslationRepository;

final class TermTranslationRepositoryTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig = $q_config;
        $q_config['term_name'] = array(
            'Legacy' => array( 'lv' => 'Legacy', 'ru' => 'Старое' ),
        );
        $GLOBALS['qtx_test_term_meta'] = array();
        $GLOBALS['qtx_test_options'] = array();
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
    }

    public function testReadsTermScopedValueBeforeLegacyFallback(): void {
        $repository = new TermTranslationRepository();
        $legacyTerm = (object) array( 'term_id' => 10, 'name' => 'Legacy' );
        self::assertSame( 'Старое', $repository->translations( $legacyTerm )['ru'] );

        update_term_meta( 10, TermTranslationRepository::META_KEY, array( 'lv' => 'Jauns', 'ru' => 'Новое' ) );
        self::assertSame( 'Новое', $repository->translations( $legacyTerm )['ru'] );
    }

    public function testIndependentTermWritesSurviveStaleLegacySnapshots(): void {
        $first = new TermTranslationRepository();
        $second = new TermTranslationRepository();
        $first->store( 11, 'First', array( 'lv' => 'First', 'ru' => 'Первый' ) );

        global $q_config;
        $q_config['term_name'] = array( 'Legacy' => array( 'lv' => 'Legacy', 'ru' => 'Старое' ) );
        $second->store( 12, 'Second', array( 'lv' => 'Second', 'ru' => 'Второй' ) );

        self::assertSame( 'Первый', $first->translations( (object) array( 'term_id' => 11, 'name' => 'First' ) )['ru'] );
        self::assertSame( 'Второй', $second->translations( (object) array( 'term_id' => 12, 'name' => 'Second' ) )['ru'] );
        self::assertArrayHasKey( 'Second', $GLOBALS['qtx_test_options'][ TermTranslationRepository::LEGACY_OPTION ] );
    }

    public function testDeleteRemovesTermScopedAndLegacyRepresentations(): void {
        $repository = new TermTranslationRepository();
        $repository->store( 10, 'Legacy', array( 'lv' => 'Legacy', 'ru' => 'Старое' ) );
        $repository->delete( 10, array( 'Legacy' ) );

        self::assertSame( array(), $repository->translations( (object) array( 'term_id' => 10, 'name' => 'Legacy' ) ) );
        self::assertArrayNotHasKey( 'Legacy', $GLOBALS['qtx_test_options'][ TermTranslationRepository::LEGACY_OPTION ] );
    }
}

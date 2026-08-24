<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AcfStorageBoundaryTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['qtx_test_options'] = array();
        $GLOBALS['qtx_test_acf_instance'] = (object) array( 'settings' => array( 'version' => '6.8.8' ) );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['qtx_test_options'], $GLOBALS['qtx_test_acf_instance'] );
    }

    public function testStableAcfOptionReferenceDefersTranslationToAcfPipeline(): void {
        $GLOBALS['qtx_test_options']['_options_contact'] = 'field_contact';

        self::assertTrue( \qtranxf_is_acf_managed_option( 'options_contact' ) );
        self::assertFalse( \qtranxf_is_acf_managed_option( 'ordinary_option' ) );
    }

    public function testInvalidOrUnstableReferenceDoesNotClaimOption(): void {
        $GLOBALS['qtx_test_options']['_options_contact'] = '../field_contact';
        self::assertFalse( \qtranxf_is_acf_managed_option( 'options_contact' ) );

        $GLOBALS['qtx_test_options']['_options_contact'] = 'contact';
        self::assertFalse( \qtranxf_is_acf_managed_option( 'options_contact' ) );
    }
}

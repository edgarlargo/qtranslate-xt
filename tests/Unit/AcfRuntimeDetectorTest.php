<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\Acf\AcfRuntimeDetector;

final class AcfRuntimeDetectorTest extends TestCase {
    protected function tearDown(): void {
        unset( $GLOBALS['qtx_test_acf_instance'], $GLOBALS['qtx_test_acf_settings'] );
    }

    public function testDetectsRuntimeApiWithoutPluginPathAssumptions(): void {
        $GLOBALS['qtx_test_acf_instance'] = (object) array( 'settings' => array() );
        $GLOBALS['qtx_test_acf_settings'] = array( 'version' => '6.3.1' );

        $runtime = ( new AcfRuntimeDetector() )->detect();
        self::assertTrue( $runtime->isAvailable() );
        self::assertSame( '6.3.1', $runtime->version() );
        self::assertFalse( $runtime->isPro() );
    }

    public function testDetectsProThroughRuntimeCapabilityWithoutPluginBasename(): void {
        $GLOBALS['qtx_test_acf_instance'] = (object) array( 'settings' => array( 'version' => '6.8.8' ) );
        $runtime = ( new AcfRuntimeDetector( '5.6.0', static fn (): bool => true ) )->detect();

        self::assertTrue( $runtime->isAvailable() );
        self::assertTrue( $runtime->isPro() );
        self::assertSame( '6.8.8', $runtime->version() );
    }

    public function testFallsBackToRuntimeObjectForThemeBundledAcf(): void {
        $GLOBALS['qtx_test_acf_instance'] = (object) array(
            'settings' => array( 'version' => '5.12.4' ),
        );

        $runtime = ( new AcfRuntimeDetector() )->detect();
        self::assertTrue( $runtime->isAvailable() );
        self::assertSame( '5.12.4', $runtime->version() );
    }

    public function testRejectsMissingInvalidAndUnsupportedRuntime(): void {
        $GLOBALS['qtx_test_acf_instance'] = null;
        self::assertFalse( ( new AcfRuntimeDetector() )->detect()->isAvailable() );

        $GLOBALS['qtx_test_acf_instance'] = (object) array( 'settings' => array( 'version' => '5.5.9' ) );
        self::assertFalse( ( new AcfRuntimeDetector() )->detect()->isAvailable() );

        $GLOBALS['qtx_test_acf_instance'] = (object) array( 'settings' => array() );
        self::assertFalse( ( new AcfRuntimeDetector() )->detect()->isAvailable() );
    }
}

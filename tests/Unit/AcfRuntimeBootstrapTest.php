<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\Acf\AcfRuntimeBootstrap;
use QTX\Integration\Acf\AcfRuntimeDetector;

final class AcfRuntimeBootstrapTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['qtx_test_actions'] = array();
        $GLOBALS['qtx_test_did_actions'] = array();
        unset( $GLOBALS['qtx_test_acf_instance'], $GLOBALS['qtx_test_acf_settings'] );
    }

    public function testLateThemeRuntimeInitializesExactlyOnce(): void {
        $calls = 0;
        $bootstrap = new AcfRuntimeBootstrap(
            new AcfRuntimeDetector(),
            static function () use ( &$calls ): void { ++$calls; }
        );

        $bootstrap->register();
        $bootstrap->register();
        self::assertCount( 3, $GLOBALS['qtx_test_actions'] );
        self::assertSame( 'acf/init', $GLOBALS['qtx_test_actions'][0][0] );
        self::assertSame( 0, $calls );

        $GLOBALS['qtx_test_acf_instance'] = (object) array( 'settings' => array( 'version' => '6.8.8' ) );
        $GLOBALS['qtx_test_acf_settings'] = array( 'version' => '6.8.8' );
        $bootstrap->initialize();
        $bootstrap->initialize();

        self::assertTrue( $bootstrap->isInitialized() );
        self::assertSame( 1, $calls );
    }

    public function testAlreadyFiredAcfInitIsCaughtDuringRegistration(): void {
        $GLOBALS['qtx_test_did_actions']['acf/init'] = 1;
        $GLOBALS['qtx_test_acf_instance'] = (object) array( 'settings' => array( 'version' => '6.8.8' ) );
        $GLOBALS['qtx_test_acf_settings'] = array( 'version' => '6.8.8' );
        $calls = 0;
        $bootstrap = new AcfRuntimeBootstrap(
            new AcfRuntimeDetector(),
            static function () use ( &$calls ): void { ++$calls; }
        );

        $bootstrap->register();

        self::assertTrue( $bootstrap->isInitialized() );
        self::assertSame( 1, $calls );
    }
}

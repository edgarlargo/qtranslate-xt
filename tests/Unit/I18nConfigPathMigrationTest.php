<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Config\I18nConfigPathMigration;

final class I18nConfigPathMigrationTest extends TestCase {
    public function testRepairsLegacyMasterDirectoryAndDeduplicatesBundledPath(): void {
        self::assertSame(
            array( './i18n-config.json', 'themes/site/i18n-config.json' ),
            I18nConfigPathMigration::repairBundledPath( array(
                'plugins/qtranslate-xt-master/i18n-config.json',
                './i18n-config.json',
                'themes/site/i18n-config.json',
            ) )
        );
    }

    public function testAcceptsWindowsSeparatorButDoesNotRewriteUnrelatedPlugins(): void {
        self::assertSame(
            array( './i18n-config.json', 'plugins/vendor/i18n-config.json' ),
            I18nConfigPathMigration::repairBundledPath( array(
                'plugins\\qtranslate-xt-master\\i18n-config.json',
                'plugins/vendor/i18n-config.json',
            ) )
        );
    }
}

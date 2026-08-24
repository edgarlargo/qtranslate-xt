<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\WordPress\FrontendTranslationAdapter;

final class FrontendMenuAdapterTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig = $q_config;
        $q_config['language'] = 'ru';
        $q_config['show_menu_alternative_language'] = false;
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
    }

    public function testMenuAdapterMatchesLegacyForOrdinaryCustomItems(): void {
        $legacyItems = array( $this->menuItem() );
        $adapterItems = array( clone $legacyItems[0] );
        $legacyMenu = (object) array( 'count' => 1 );
        $adapterMenu = clone $legacyMenu;
        $args = (object) array();

        self::assertEquals(
            qtranxf_legacy_wp_get_nav_menu_items( $legacyItems, $legacyMenu, $args ),
            FrontendTranslationAdapter::translateMenuItems( $adapterItems, $adapterMenu, $args )
        );
        self::assertEquals( $legacyMenu, $adapterMenu );
    }

    public function testFrontendRegistrationPreservesMenuHookContract(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/frontend.php' );
        self::assertStringContainsString(
            "add_filter( 'wp_get_nav_menu_items', array( \\QTX\\Integration\\WordPress\\FrontendTranslationAdapter::class, 'translateMenuItems' ), 20, 3 );",
            $source
        );
    }

    private function menuItem(): \stdClass {
        return (object) array(
            'ID'               => 20,
            'item_lang'        => null,
            'menu_item_parent' => 0,
            'menu_order'       => 1,
            'object_id'        => 20,
            'object'           => 'custom',
            'type'             => 'custom',
            'title'            => '[:lv]Sadaļa[:ru]Раздел[:]',
            'post_title'       => '[:lv]Sadaļa[:ru]Раздел[:]',
            'post_content'     => '',
            'post_excerpt'     => '',
            'description'      => '',
            'attr_title'       => '',
            'url'              => '',
        );
    }
}

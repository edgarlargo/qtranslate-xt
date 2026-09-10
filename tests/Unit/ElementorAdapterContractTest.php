<?php

use PHPUnit\Framework\TestCase;
use QTX\Integration\Elementor\ElementorAdapter;

final class ElementorAdapterContractTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig = $q_config;
        $GLOBALS['qtx_test_filters'] = array();
        $GLOBALS['qtx_test_actions'] = array();
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
    }

    public function testRenderedWidgetContentUsesCurrentLanguageWithoutAvailabilityNotice(): void {
        global $q_config;
        $q_config['language'] = 'lv';
        $adapter = new ElementorAdapter( static fn (): bool => true );
        $content = '<section data-id="technical-id">'
            . '<h2>[:en]About[:lv]Par mums[:ru]О нас[:]</h2>'
            . '<a href="https://example.test/fixed">[:en]Read more[:lv]Lasīt vairāk[:ru]Подробнее[:]</a>'
            . '</section>';

        self::assertSame(
            '<section data-id="technical-id"><h2>Par mums</h2><a href="https://example.test/fixed">Lasīt vairāk</a></section>',
            $adapter->translateRenderedContent( $content )
        );
        self::assertSame( '<div>Plain Elementor output</div>', $adapter->translateRenderedContent( '<div>Plain Elementor output</div>' ) );
        self::assertSame(
            '<span>Vēsturiskais formāts</span>',
            $adapter->translateRenderedContent( '<span><!--:en-->Legacy<!--:lv-->Vēsturiskais formāts<!--:--></span>' )
        );
        self::assertSame( array( 'not', 'html' ), $adapter->translateRenderedContent( array( 'not', 'html' ) ) );
    }

    public function testRegistrationUsesElementorOutputAndOfficialEditorBoundaries(): void {
        $adapter = new ElementorAdapter( static fn (): bool => true );
        $adapter->register();
        $adapter->register();

        $filterHooks = array_column( $GLOBALS['qtx_test_filters'], 0 );
        $actionHooks = array_column( $GLOBALS['qtx_test_actions'], 0 );
        self::assertSame( 1, count( array_keys( $filterHooks, 'elementor/widget/render_content', true ) ) );
        self::assertSame( 1, count( array_keys( $filterHooks, 'elementor/frontend/the_content', true ) ) );
        self::assertContains( 'wp_enqueue_scripts', $actionHooks );
        self::assertContains( 'elementor/editor/after_enqueue_scripts', $actionHooks );
    }

    public function testImplementationNeverFiltersOrMutatesPrivateElementorJson(): void {
        $root = dirname( __DIR__, 2 );
        $adapter = file_get_contents( $root . '/src/Integration/Elementor/ElementorAdapter.php' );
        $editor = file_get_contents( $root . '/js/elementor/editor.js' );
        $frontend = file_get_contents( $root . '/js/elementor/frontend.js' );
        $init = file_get_contents( $root . '/src/init.php' );

        self::assertStringContainsString( "elementor/widget/render_content", $adapter );
        self::assertStringContainsString( "elementor/frontend/the_content", $adapter );
        self::assertStringContainsString( "elementor/editor/after_enqueue_scripts", $adapter );
        self::assertStringContainsString( "new \\QTX\\Integration\\Elementor\\ElementorAdapter()", $init );
        self::assertStringNotContainsString( 'get_post_metadata', $adapter );
        self::assertStringNotContainsString( 'update_post_meta', $adapter );
        self::assertStringNotContainsString( 'active_plugins', $adapter );
        self::assertStringContainsString( '.elementor-control-type-wysiwyg textarea[data-setting]', $editor );
        self::assertStringContainsString( 'serializeElementorValue(values, languages)', $editor );
        self::assertStringNotContainsString( 'innerHTML', $editor );
        self::assertStringNotContainsString( 'innerHTML', $frontend );
        self::assertStringNotContainsString( "'href'", $frontend );
        self::assertStringNotContainsString( "'src'", $frontend );
    }
}

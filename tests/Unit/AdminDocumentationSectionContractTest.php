<?php

use PHPUnit\Framework\TestCase;

final class AdminDocumentationSectionContractTest extends TestCase {
    public function testSettingsRegisterAndRenderTheDocumentationTab(): void {
        $settings = file_get_contents( dirname( __DIR__, 2 ) . '/src/admin/admin_settings.php' );

        self::assertStringContainsString( '$admin_sections[\'documentation\']', $settings );
        self::assertStringContainsString( '$this->add_documentation_section();', $settings );
        self::assertStringContainsString( "open_section( 'documentation' )", $settings );
        self::assertStringContainsString( "close_section( 'documentation', false )", $settings );
    }

    public function testDocumentationDescribesTheGutenbergSafetyContractAndMigration(): void {
        $settings = file_get_contents( dirname( __DIR__, 2 ) . '/src/admin/admin_settings.php' );

        self::assertStringContainsString( 'Working with Gutenberg', $settings );
        self::assertStringContainsString( 'Concurrent editing and error 409', $settings );
        self::assertStringContainsString( 'Differences between the legacy and modern versions', $settings );
        self::assertStringContainsString( 'No routine content conversion is required', $settings );
    }

    public function testReadOnlySectionsCanSuppressTheSubmitButton(): void {
        $settings = file_get_contents( dirname( __DIR__, 2 ) . '/src/admin/admin_settings.php' );

        self::assertStringContainsString( 'public static function close_section( string $name, $button_name = null )', $settings );
        self::assertStringContainsString( 'if ( $button_name !== false )', $settings );
    }

    public function testRussianAndLatvianCatalogsContainDocumentationHeadings(): void {
        $root = dirname( __DIR__, 2 );
        $russian = file_get_contents( $root . '/lang/qtranslate-ru_RU.po' );
        $latvian = file_get_contents( $root . '/lang/qtranslate-lv.po' );

        self::assertStringContainsString( 'msgstr "Документация"', $russian );
        self::assertStringContainsString( 'msgstr "Dokumentācija"', $latvian );
        self::assertStringContainsString( 'msgstr "Darbs ar Gutenberg"', $latvian );
        self::assertStringContainsString( 'msgstr "Atšķirības starp veco un jauno versiju"', $latvian );
    }
}

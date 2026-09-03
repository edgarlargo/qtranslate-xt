<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigurationFilesTextareaXssTest extends TestCase {
    public function testConfigurationTextareasUseContextualEscapingAndScalarGuards(): void {
        $root = dirname( __DIR__, 2 );
        $settings = file_get_contents( $root . '/src/admin/admin_settings.php' );
        $update = file_get_contents( $root . '/src/admin/admin_options_update.php' );

        self::assertStringContainsString( 'echo esc_textarea( $config_files_value );', $settings );
        self::assertStringContainsString( 'echo esc_textarea( $custom_i18n_config_value );', $settings );
        self::assertStringNotContainsString( "echo \$_POST['json_config_files'] ??", $settings );
        self::assertStringContainsString(
            "isset( \$_POST['json_config_files'] ) && ! is_string( \$_POST['json_config_files'] )",
            $update
        );
        self::assertStringContainsString(
            "isset( \$_POST['json_custom_i18n_config'] ) && ! is_string( \$_POST['json_custom_i18n_config'] )",
            $update
        );
    }

    #[DataProvider( 'textareaValues' )]
    public function testTextareaEscapingPreservesTextWithoutCreatingMarkup( string $input ): void {
        $escaped = htmlspecialchars( $input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

        self::assertStringNotContainsString( '</textarea', strtolower( $escaped ) );
        self::assertStringNotContainsString( '<svg', strtolower( $escaped ) );
        self::assertStringNotContainsString( '<script', strtolower( $escaped ) );
        self::assertSame( $input, html_entity_decode( $escaped, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
    }

    public static function textareaValues(): array {
        return array(
            'normal path' => array( './i18n-config.json' ),
            'HTML special characters' => array( 'config&name <value> > fallback' ),
            'quotes' => array( '"double" and \'single\'' ),
            'SVG payload' => array( '"></textarea><svg onload=alert(1)>' ),
            'script-like payload' => array( '</textarea><script>window.qtxProof = true</script>' ),
            'Unicode' => array( 'Latviešu / Русский / 日本語' ),
            'multilingual markers' => array( '[:lv]Fails[:ru]Ошибка[:en]Error[:]' ),
            'URL-encoded payload' => array( '%3C%2Ftextarea%3E%3Csvg%20onload%3Dalert(1)%3E' ),
        );
    }
}

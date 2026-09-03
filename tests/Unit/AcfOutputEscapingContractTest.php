<?php

use PHPUnit\Framework\TestCase;

final class AcfOutputEscapingContractTest extends TestCase {
    public function testFileFieldEscapesAttachmentMetadataAtOutput(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/acf/fields/file.php' );

        self::assertStringContainsString( "esc_url( \$atts['icon'] )", $source );
        self::assertStringContainsString( "esc_html( \$atts['title'] )", $source );
        self::assertStringContainsString( "esc_url( \$atts['url'] )", $source );
        self::assertStringContainsString( "esc_html( \$atts['filename'] )", $source );
        self::assertStringContainsString( 'rel="noopener noreferrer"', $source );
    }

    public function testWysiwygFieldCannotCloseItsTextareaEarly(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/modules/acf/fields/wysiwyg.php' );

        self::assertStringContainsString( "preg_replace( '#</textarea#i', '&lt;/textarea'", $source );
        self::assertStringContainsString( "esc_attr( \$name )", $source );
        self::assertStringContainsString( "esc_attr( \$id )", $source );
    }

    public function testAllCustomFieldLanguageTabsEscapeLabelsAndAttributes(): void {
        $directory = dirname( __DIR__, 2 ) . '/src/modules/acf/fields';
        foreach ( array( 'file', 'image', 'post_object', 'text', 'textarea', 'url', 'wysiwyg' ) as $field ) {
            $source = file_get_contents( $directory . '/' . $field . '.php' );
            self::assertStringContainsString( 'esc_attr( $language )', $source, $field );
            self::assertStringContainsString( "esc_html( \$q_config['language_name'][ \$language ] )", $source, $field );
        }
    }
}

<?php

use PHPUnit\Framework\TestCase;

final class LatvianLocaleCompatibilityTest extends TestCase {
    public function test_latvian_is_a_complete_predefined_language(): void {
        self::assertSame( 'Latviešu', qtranxf_default_language_name()['lv'] );
        self::assertSame( 'lv', qtranxf_default_locale()['lv'] );
        self::assertSame( 'lv.png', qtranxf_default_flag()['lv'] );
        self::assertArrayHasKey( 'lv', qtranxf_default_not_available() );
        self::assertArrayHasKey( 'lv', qtranxf_default_date_format() );
        self::assertArrayHasKey( 'lv', qtranxf_default_time_format() );
    }

    /**
     * @dataProvider latvianLocaleAliases
     */
    public function test_legacy_latvian_locale_aliases_use_wordpress_pack_locale( string $configured ): void {
        self::assertSame( 'lv', qtranxf_normalize_wordpress_locale( 'lv', $configured ) );
    }

    public static function latvianLocaleAliases(): array {
        return array(
            'canonical'  => array( 'lv' ),
            'underscore' => array( 'lv_LV' ),
            'hyphen'     => array( 'lv-LV' ),
            'case'       => array( 'LV_lv' ),
        );
    }

    public function test_other_locales_are_not_rewritten(): void {
        self::assertSame( 'ru_RU', qtranxf_normalize_wordpress_locale( 'ru', 'ru_RU' ) );
        self::assertSame( 'en_US', qtranxf_normalize_wordpress_locale( 'en', 'en_US' ) );
        self::assertSame( 'custom', qtranxf_normalize_wordpress_locale( 'lv', 'custom' ) );
    }
}

<?php

namespace QTX\Tests\Characterization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyMultilingualParserTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig = $q_config;
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
    }

    public static function corpusProvider(): iterable {
        $path   = dirname( __DIR__ ) . '/Fixtures/multilingual-corpus.json';
        $corpus = json_decode( file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );

        foreach ( $corpus['cases'] as $case ) {
            yield $case['id'] => [ $case ];
        }
    }

    #[DataProvider( 'corpusProvider' )]
    public function testLegacyCorpus( array $case ): void {
        $raw      = $this->materializeRaw( $case );
        $expected = $case['expected_php'];

        if ( array_key_exists( 'is_multilingual', $expected ) ) {
            self::assertSame( $expected['is_multilingual'], qtranxf_isMultilingual( $raw ), $case['id'] . ': detector' );
        }

        $blocks = qtranxf_get_language_blocks( $raw );
        if ( array_key_exists( 'blocks', $expected ) ) {
            self::assertSame( $expected['blocks'], $blocks, $case['id'] . ': blocks' );
        }

        $split = qtranxf_split( $raw );
        if ( array_key_exists( 'split', $expected ) ) {
            self::assertSame( $expected['split'], $split, $case['id'] . ': split' );
            $found = array();
            self::assertSame( $expected['split'], qtranxf_split_blocks( $blocks, $found ), $case['id'] . ': split_blocks' );
        }

        if ( array_key_exists( 'split_languages', $expected ) ) {
            self::assertSame( $expected['split_languages'], qtranxf_split_languages( $blocks ), $case['id'] . ': split_languages' );
        }

        if ( array_key_exists( 'available', $expected ) ) {
            self::assertSame( $expected['available'], qtranxf_getAvailableLanguages( $raw ), $case['id'] . ': available languages' );
        }

        foreach ( $expected['use'] ?? array() as $lang => $translation ) {
            self::assertSame( $translation, qtranxf_use( $lang, $raw ), $case['id'] . ": qtranxf_use {$lang}" );
            self::assertSame( $translation, qtranxf_use_language( $lang, $raw ), $case['id'] . ": qtranxf_use_language {$lang}" );
        }

        foreach ( $expected['use_show_empty'] ?? array() as $lang => $translation ) {
            self::assertSame( $translation, qtranxf_use( $lang, $raw, false, true ), $case['id'] . ": show_empty {$lang}" );
        }

        if ( isset( $expected['split_length'] ) ) {
            foreach ( $expected['split_length'] as $lang => $length ) {
                self::assertSame( $length, strlen( $split[ $lang ] ), $case['id'] . ": split length {$lang}" );
            }
        }

        if ( isset( $case['round_trip'] ) ) {
            self::assertContains( $case['round_trip']['classification'], array( 'LOSSLESS', 'NORMALIZED', 'LEGACY-QUIRK' ) );
            self::assertSame( $case['round_trip']['join_b'], qtranxf_join_b( $split ), $case['id'] . ': join_b' );
            self::assertSame( $case['round_trip']['join_c'], qtranxf_join_c( $split ), $case['id'] . ': join_c' );
            self::assertSame( $case['round_trip']['join_s'], qtranxf_join_s( $split ), $case['id'] . ': join_s' );
        }
    }

    public static function fallbackProvider(): iterable {
        yield 'requested language exists' => [ 'lv', '[:lv]LV[:ru]RU[:]', false, false, array(), 'LV' ];
        yield 'requested language missing uses first enabled' => [ 'en', '[:lv]LV[:ru]RU[:]', false, false, array(), 'LV' ];
        yield 'requested language missing and show_empty' => [ 'en', '[:lv]LV[:ru]RU[:]', false, true, array(), '' ];
        yield 'explicit empty marker falls back' => [ 'lv', '[:lv][:ru]RU[:]', false, false, array(), 'RU' ];
        yield 'explicit empty marker and show_empty' => [ 'lv', '[:lv][:ru]RU[:]', false, true, array(), '' ];
        yield 'displayed language prefix' => [ 'en', '[:lv]LV[:ru]RU[:]', false, false, array( 'show_displayed_language_prefix' => true ), '(Latviešu) LV' ];
        yield 'default exists when explicitly requested' => [ 'en', '[:lv]LV[:en]EN[:]', false, false, array(), 'EN' ];
        yield 'show_available message' => [ 'en', '[:lv]LV[:ru]RU[:]', true, false, array(), self::availableMessage() ];
    }

    #[DataProvider( 'fallbackProvider' )]
    public function testFallbackBehavior( string $lang, string $raw, bool $showAvailable, bool $showEmpty, array $config, string $expected ): void {
        global $q_config;
        $q_config = array_replace( $q_config, $config );

        self::assertSame( $expected, qtranxf_use( $lang, $raw, $showAvailable, $showEmpty ) );
    }

    public function testCurrentAndDefaultLanguageWrappers(): void {
        global $q_config;
        $q_config['language']         = 'lv';
        $q_config['default_language'] = 'en';

        self::assertSame( '', qtranxf_useCurrentLanguageIfNotFoundShowEmpty( '[:ru]RU[:]' ) );
        self::assertSame( self::availableMessage( array( 'ru' ), 'RU', 'lv' ), qtranxf_useCurrentLanguageIfNotFoundShowAvailable( '[:ru]RU[:]' ) );
        self::assertSame( 'RU', qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( '[:ru]RU[:]' ) );
        self::assertSame( 'EN', qtranxf_useDefaultLanguage( '[:lv]LV[:en]EN[:]' ) );
    }

    public function testQtranxfUseRecursesThroughArrays(): void {
        self::assertSame(
            array( 'title' => 'Sveiki', 'nested' => array( 'Привет' ) ),
            qtranxf_use( 'lv', array( 'title' => '[:lv]Sveiki[:ru]Привет[:]', 'nested' => array( '[:lv]Привет[:]' ) ) )
        );
    }

    public function testQtranxfUseRecursesThroughObjects(): void {
        $value        = new \stdClass();
        $value->title = '[:lv]Sveiki[:ru]Привет[:]';

        self::assertSame( $value, qtranxf_use( 'lv', $value ) );
        self::assertSame( 'Sveiki', $value->title );
    }

    public function testPublicJoinAndBlockHelpersRetainLegacyBehavior(): void {
        $translations = array( 'lv' => 'A', 'ru' => 'Б', 'en' => '' );
        $blocks       = qtranxf_get_language_blocks( '[:lv]A[:ru]Б[:]' );

        self::assertNull( qtranxf_allthesame( $translations ) );
        self::assertSame( 'Same', qtranxf_allthesame( array( 'lv' => 'Same', 'ru' => 'Same' ) ) );
        self::assertSame( '[:lv]A[:ru]Б', qtranxf_join_b_no_closing( $translations ) );
        self::assertSame( 'A', qtranxf_use_block( 'lv', $blocks ) );
        self::assertSame( 'Б', qtranxf_use_content( 'ru', $translations, array( 'lv' => true, 'ru' => true ) ) );
        self::assertSame(
            'Same, value',
            qtranxf_join_byseparator( array( 'lv' => 'Same, value', 'ru' => 'Same, value' ), '/(, )/' )
        );
        self::assertSame(
            '[:lv]A[:ru]А[:]' . PHP_EOL . '[:lv]B[:ru]Б[:]' . PHP_EOL,
            qtranxf_join_byline( array( 'lv' => "A\nB", 'ru' => "А\nБ" ) )
        );
    }

    private function materializeRaw( array $case ): string {
        if ( isset( $case['raw'] ) ) {
            return $case['raw'];
        }

        $generator = $case['raw_generator'];

        return $generator['prefix'] . str_repeat( $generator['repeat'], $generator['count'] ) . $generator['suffix'];
    }

    private static function availableMessage( array $languages = array( 'lv', 'ru' ), string $alternative = 'LV', string $requested = 'en' ): string {
        global $q_config;
        $links = array();
        foreach ( $languages as $language ) {
            $name    = $q_config['language_name'][ $language ];
            $links[] = '<a href="/?lang=' . $language . '" class="qtranxs-available-language-link qtranxs-available-language-link-' . $language . '" title="' . $name . '">' . $name . '</a>';
        }
        $list = count( $links ) === 2 ? $links[0] . ' and ' . $links[1] : implode( ', ', $links );

        return '<p class="qtranxs-available-languages-message qtranxs-available-languages-message-' . $requested . '">Available: ' . $list . '</p>' . $alternative;
    }
}

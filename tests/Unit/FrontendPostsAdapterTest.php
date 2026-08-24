<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\WordPress\FrontendTranslationAdapter;

final class FrontendPostsAdapterTest extends TestCase {
    private array $baseConfig;

    protected function setUp(): void {
        global $q_config;
        $this->baseConfig       = $q_config;
        $q_config['language']   = 'ru';
    }

    protected function tearDown(): void {
        global $q_config;
        $q_config = $this->baseConfig;
    }

    public function testPostsAdapterMatchesPreservedLegacyProjection(): void {
        $query = (object) array( 'query_vars' => array( 'post_type' => 'post' ) );
        $posts = array( $this->postFixture(), $this->postFixture() );
        $legacyPosts  = array_map( static fn ( $post ) => clone $post, $posts );
        $adapterPosts = array_map( static fn ( $post ) => clone $post, $posts );

        self::assertEquals(
            qtranxf_legacy_postsFilter( $legacyPosts, $query ),
            FrontendTranslationAdapter::translatePosts( $adapterPosts, $query )
        );
    }

    public function testPostsAdapterPreservesNonArrayAndMenuBypass(): void {
        $query = (object) array( 'query_vars' => array( 'post_type' => 'post' ) );
        self::assertSame( false, FrontendTranslationAdapter::translatePosts( false, $query ) );

        $menuQuery = (object) array( 'query_vars' => array( 'post_type' => 'nav_menu_item' ) );
        $posts     = array( $this->postFixture() );
        self::assertSame( $posts, FrontendTranslationAdapter::translatePosts( $posts, $menuQuery ) );
    }

    public function testFrontendRegistrationPreservesPostsHookContract(): void {
        $source = file_get_contents( dirname( __DIR__, 2 ) . '/src/frontend.php' );
        self::assertStringContainsString(
            "add_filter( 'the_posts', array( \\QTX\\Integration\\WordPress\\FrontendTranslationAdapter::class, 'translatePosts' ), 5, 2 );",
            $source
        );
    }

    private function postFixture(): \stdClass {
        return (object) array(
            'ID'                    => 10,
            'post_author'           => 1,
            'post_date'             => '2024-01-01 00:00:00',
            'post_date_gmt'         => '2024-01-01 00:00:00',
            'post_content'          => '[:lv]Saturs[:ru]Содержимое[:]',
            'post_title'            => '[:lv]Virsraksts[:ru]Заголовок[:]',
            'post_excerpt'          => '[:lv]Izraksts[:ru]Отрывок[:]',
            'post_status'           => 'publish',
            'comment_status'        => 'open',
            'ping_status'           => 'open',
            'post_password'         => '',
            'post_name'             => 'name',
            'to_ping'               => '',
            'pinged'                => '',
            'post_modified'         => '2024-01-01 00:00:00',
            'post_modified_gmt'     => '2024-01-01 00:00:00',
            'post_content_filtered' => '[:lv]Filtrēts[:ru]Фильтрованное[:]',
            'post_parent'           => 0,
            'guid'                  => 'https://example.test/?p=10',
            'menu_order'            => 0,
            'post_type'             => 'post',
            'post_mime_type'        => '',
            'comment_count'         => '0',
            'filter'                => 'raw',
        );
    }
}

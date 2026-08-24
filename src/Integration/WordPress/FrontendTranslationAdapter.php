<?php

namespace QTX\Integration\WordPress;

final class FrontendTranslationAdapter {
    public static function translateTitle( $title ) {
        global $q_config;

        return qtranxf_use( $q_config['language'], $title, false, false );
    }

    public static function translateContent( $content ) {
        global $q_config;

        return qtranxf_use( $q_config['language'], $content, true, false );
    }

    public static function translateExcerpt( $excerpt ) {
        global $q_config;

        return qtranxf_use( $q_config['language'], $excerpt, true, false );
    }

    public static function translateRssExcerpt( $excerpt ) {
        return self::translateExcerpt( $excerpt );
    }

    public static function translateRssText( $text ) {
        global $q_config;

        return qtranxf_use( $q_config['language'], $text, false, false );
    }

    public static function translatePosts( $posts, $query ) {
        global $q_config;
        if ( ! is_array( $posts ) ) {
            return $posts;
        }
        if ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] === 'nav_menu_item' ) {
            return $posts;
        }
        foreach ( $posts as $post ) {
            qtranxf_translate_post( $post, $q_config['language'] );
        }

        return $posts;
    }

    public static function translateTerm( $term ) {
        global $q_config;

        return qtranxf_term_use( $q_config['language'], $term, null );
    }

    public static function translateMenuItems( $items, $menu, $args ) {
        return qtranxf_legacy_wp_get_nav_menu_items( $items, $menu, $args );
    }
}

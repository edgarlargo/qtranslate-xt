<?php
/**
 * Exact release-candidate archive smoke test. Run only through WP-CLI.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    throw new RuntimeException( 'This release-candidate smoke test must run through WP-CLI.' );
}

$phase = getenv( 'QTX_RC_PHASE' );
if ( ! in_array( $phase, array( 'prepare', 'verify' ), true ) ) {
    WP_CLI::error( 'QTX_RC_PHASE must be prepare or verify.' );
}

if ( ! defined( 'QTX_VERSION' ) || '4.0.0-rc1' !== QTX_VERSION ) {
    WP_CLI::error( 'Unexpected installed qTranslate-XT version.' );
}

$plugin_root = dirname( QTRANSLATE_FILE );
if ( ! is_file( $plugin_root . '/lang/qtranslate-lv.mo' ) ) {
    WP_CLI::error( 'The Latvian MO file is absent from the installed archive.' );
}

$fixture_option = 'qtx_rc_smoke_post_id';
$raw_title     = '[:lv]Arhīva pārbaude[:ru]Проверка архива[:en]Archive check[:]';
$raw_content   = '[:lv]Saglabāts saturs[:ru]Сохранённое содержимое[:en]Preserved content[:]';
$translations  = array(
    'lv' => array( 'Arhīva pārbaude', 'Saglabāts saturs' ),
    'ru' => array( 'Проверка архива', 'Сохранённое содержимое' ),
    'en' => array( 'Archive check', 'Preserved content' ),
);

if ( 'prepare' === $phase ) {
    if ( get_option( $fixture_option ) ) {
        WP_CLI::error( 'A stale release-candidate fixture already exists.' );
    }

    $post_id = wp_insert_post(
        array(
            'post_status'  => 'draft',
            'post_title'   => $raw_title,
            'post_content' => $raw_content,
        ),
        true
    );
    if ( is_wp_error( $post_id ) ) {
        WP_CLI::error( 'Could not create the multilingual fixture: ' . $post_id->get_error_message() );
    }

    update_option( $fixture_option, (int) $post_id, false );
    WP_CLI::success( 'Release-candidate multilingual fixture prepared.' );
    return;
}

$post_id = (int) get_option( $fixture_option );
if ( $post_id <= 0 ) {
    WP_CLI::error( 'The release-candidate fixture was not preserved.' );
}

$stored_title   = get_post_field( 'post_title', $post_id, 'raw' );
$stored_content = get_post_field( 'post_content', $post_id, 'raw' );
if ( $raw_title !== $stored_title || $raw_content !== $stored_content ) {
    WP_CLI::error( 'Raw multilingual storage changed across deactivation/reactivation.' );
}

foreach ( $translations as $language => $expected ) {
    if ( $expected[0] !== qtranxf_use( $language, $stored_title ) ) {
        WP_CLI::error( 'Title projection failed for ' . $language . '.' );
    }
    if ( $expected[1] !== qtranxf_use( $language, $stored_content ) ) {
        WP_CLI::error( 'Content projection failed for ' . $language . '.' );
    }
}

wp_delete_post( $post_id, true );
delete_option( $fixture_option );
WP_CLI::success( 'Exact ZIP activation and LV/RU/EN preservation checks passed.' );

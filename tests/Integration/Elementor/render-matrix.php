<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'ELEMENTOR_VERSION' ) ) {
    fwrite( STDERR, "Elementor integration runner requires WordPress and Elementor.\n" );
    exit( 1 );
}

$assertions = 0;
$assert = static function ( bool $condition, string $message ) use ( &$assertions ): void {
    ++$assertions;
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$message}\n" );
        exit( 1 );
    }
};

$rawHeading = '[:lv]QTX_ELEMENTOR_VIRSRAKSTS[:ru]QTX_ELEMENTOR_ЗАГОЛОВОК[:en]QTX_ELEMENTOR_HEADING[:]';
$rawBody = '[:lv]<p>QTX_ELEMENTOR_TEKSTS</p>[:ru]<p>QTX_ELEMENTOR_ТЕКСТ</p>[:en]<p>QTX_ELEMENTOR_BODY</p>[:]';
$rawButton = '[:lv]QTX_ELEMENTOR_POGA[:ru]QTX_ELEMENTOR_КНОПКА[:en]QTX_ELEMENTOR_BUTTON[:]';
$technicalUrl = 'https://example.test/unchanged-target?ref=qtx';
$data = array(
    array(
        'id'       => 'qtxsect1',
        'elType'   => 'section',
        'settings' => array(),
        'elements' => array(
            array(
                'id'       => 'qtxcol01',
                'elType'   => 'column',
                'settings' => array( '_column_size' => 100 ),
                'elements' => array(
                    array(
                        'id'         => 'qtxhead1',
                        'elType'     => 'widget',
                        'widgetType' => 'heading',
                        'settings'   => array( 'title' => $rawHeading, 'header_size' => 'h2' ),
                        'elements'   => array(),
                    ),
                    array(
                        'id'         => 'qtxtext1',
                        'elType'     => 'widget',
                        'widgetType' => 'text-editor',
                        'settings'   => array( 'editor' => $rawBody ),
                        'elements'   => array(),
                    ),
                    array(
                        'id'         => 'qtxbtn01',
                        'elType'     => 'widget',
                        'widgetType' => 'button',
                        'settings'   => array(
                            'text' => $rawButton,
                            'link' => array( 'url' => $technicalUrl, 'is_external' => 'on' ),
                        ),
                        'elements'   => array(),
                    ),
                ),
            ),
        ),
    ),
);

$pageId = (int) get_option( 'qtx_elementor_fixture_page_id', 0 );
if ( $pageId <= 0 || ! get_post( $pageId ) ) {
    $pageId = wp_insert_post(
        array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_name'    => 'qtx-elementor',
            'post_title'   => '[:lv]Elementor LV[:ru]Elementor RU[:en]Elementor EN[:]',
            'post_content' => '',
        )
    );
    $assert( ! is_wp_error( $pageId ) && $pageId > 0, 'fixture page creation' );
    update_option( 'qtx_elementor_fixture_page_id', $pageId, false );
}

update_post_meta( $pageId, '_elementor_edit_mode', 'builder' );
update_post_meta( $pageId, '_elementor_template_type', 'wp-page' );
update_post_meta( $pageId, '_elementor_version', ELEMENTOR_VERSION );
update_post_meta( $pageId, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );

$stored = get_post_meta( $pageId, '_elementor_data', true );
$assert( is_string( $stored ) && strpos( $stored, $rawHeading ) !== false, 'raw multilingual heading preserved in Elementor JSON' );
$assert( strpos( $stored, $rawBody ) !== false, 'raw multilingual WYSIWYG value preserved in Elementor JSON' );
$assert( strpos( $stored, $technicalUrl ) !== false, 'technical URL preserved in Elementor JSON' );
$assert( strpos( $stored, 'qtxbtn01' ) !== false, 'Elementor element ID preserved' );

global $q_config;
$originalLanguage = $q_config['language'];
$expected = array(
    'lv' => array( 'QTX_ELEMENTOR_VIRSRAKSTS', 'QTX_ELEMENTOR_TEKSTS', 'QTX_ELEMENTOR_POGA' ),
    'ru' => array( 'QTX_ELEMENTOR_ЗАГОЛОВОК', 'QTX_ELEMENTOR_ТЕКСТ', 'QTX_ELEMENTOR_КНОПКА' ),
    'en' => array( 'QTX_ELEMENTOR_HEADING', 'QTX_ELEMENTOR_BODY', 'QTX_ELEMENTOR_BUTTON' ),
);
foreach ( $expected as $language => $needles ) {
    $q_config['language'] = $language;
    $html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $pageId, true );
    foreach ( $needles as $needle ) {
        $assert( strpos( $html, $needle ) !== false, "{$language} rendered {$needle}" );
    }
    $assert( strpos( $html, '[:lv]' ) === false, "{$language} output has no raw marker" );
    $assert( strpos( $html, $technicalUrl ) !== false, "{$language} output preserves technical URL" );
}
$q_config['language'] = $originalLanguage;

$adapter = $GLOBALS['qtx_elementor_adapter'] ?? null;
$assert( $adapter instanceof \QTX\Integration\Elementor\ElementorAdapter, 'core Elementor adapter is active' );
$adapter->enqueueFrontend();
$adapter->enqueueEditor();
$assert( wp_script_is( 'qtx-elementor-frontend', 'enqueued' ), 'frontend DOM bridge enqueued' );
$assert( wp_script_is( 'qtx-elementor-editor', 'enqueued' ), 'editor language bridge enqueued' );
$assert( wp_style_is( 'qtx-elementor-editor', 'enqueued' ), 'editor language bridge stylesheet enqueued' );
$assert( in_array( 'elementor-editor', wp_scripts()->registered['qtx-elementor-editor']->deps, true ), 'editor script uses official Elementor dependency' );

echo 'Elementor ' . ELEMENTOR_VERSION . " matrix PASS ({$assertions} assertions) page={$pageId}\n";

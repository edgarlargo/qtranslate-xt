<?php

/**
 * Disposable native ACF runtime smoke test.
 *
 * Run inside a dedicated WordPress test installation:
 *   wp eval-file tests/Integration/acf-native-runtime-smoke.php
 *
 * The runner uses only public ACF/QTX APIs, refuses to overwrite an existing
 * fixture namespace and removes every value it creates in a finally block.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    throw new RuntimeException( 'This ACF smoke runner must be executed through WP-CLI.' );
}
if ( ! function_exists( 'acf' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
    WP_CLI::error( 'A real ACF runtime is required.' );
}
if ( ! function_exists( 'qtranxf_getLanguage' ) || ! isset( $GLOBALS['qtx_acf_runtime_services'] ) ) {
    WP_CLI::error( 'The native qTranslate-XT ACF adapter is not initialized.' );
}

$prefix = 'qtx_native_smoke';
$post_id = 'option';
$created_field_keys = array();
$results = array();
$failures = array();
global $wpdb;

$fixtures = array(
    'lv' => array(
        'contact' => 'Sazināties ar mums',
        'message' => 'Nosūti mums ziņu!',
        'button' => 'Sūtīt',
    ),
    'ru' => array(
        'contact' => 'Связаться с нами',
        'message' => 'Отправьте нам сообщение!',
        'button' => 'Отправить',
    ),
    'en' => array(
        'contact' => 'Contact us',
        'message' => 'Send us a message!',
        'button' => 'Send',
    ),
);
$raw_values = array(
    'contact' => '[:lv]Sazināties ar mums[:ru]Связаться с нами[:en]Contact us[:]',
    'message' => '[:lv]Nosūti mums ziņu![:ru]Отправьте нам сообщение![:en]Send us a message![:]',
    'button' => '[:lv]Sūtīt[:ru]Отправить[:en]Send[:]',
);

$record = static function ( string $scenario, string $status, string $detail = '' ) use ( &$results, &$failures ): void {
    $results[] = array( 'scenario' => $scenario, 'status' => $status, 'detail' => $detail );
    if ( $status === 'FAIL' ) {
        $failures[] = $scenario . ( $detail === '' ? '' : ': ' . $detail );
    }
};
$assert_same = static function ( string $scenario, $expected, $actual ) use ( $record ): void {
    if ( $expected === $actual ) {
        $record( $scenario, 'PASS' );
        return;
    }
    $record( $scenario, 'FAIL', 'expected ' . wp_json_encode( $expected ) . ', got ' . wp_json_encode( $actual ) );
};
$field_type_available = static function ( string $type ): bool {
    return function_exists( 'acf_get_field_type' ) && is_object( acf_get_field_type( $type ) );
};

$language = qtranxf_getLanguage();
if ( ! isset( $fixtures[ $language ] ) ) {
    WP_CLI::error( 'Enable LV/RU/EN and execute with one of those current QTX languages.' );
}

$fields = array(
    array( 'key' => 'field_qtx_native_smoke_contact', 'name' => $prefix . '_contact', 'type' => 'text' ),
    array( 'key' => 'field_qtx_native_smoke_message', 'name' => $prefix . '_message', 'type' => 'textarea' ),
    array( 'key' => 'field_qtx_native_smoke_button', 'name' => $prefix . '_button', 'type' => 'wysiwyg' ),
    array( 'key' => 'field_qtx_native_smoke_url', 'name' => $prefix . '_url', 'type' => 'url' ),
);

if ( $field_type_available( 'group' ) ) {
    $fields[] = array(
        'key' => 'field_qtx_native_smoke_group',
        'name' => $prefix . '_group',
        'type' => 'group',
        'sub_fields' => array(
            array( 'key' => 'field_qtx_native_smoke_group_copy', 'name' => 'copy', 'type' => 'text' ),
            array( 'key' => 'field_qtx_native_smoke_group_id', 'name' => 'technical_id', 'type' => 'number' ),
        ),
    );
}
if ( $field_type_available( 'repeater' ) ) {
    $fields[] = array(
        'key' => 'field_qtx_native_smoke_repeater',
        'name' => $prefix . '_repeater',
        'type' => 'repeater',
        'sub_fields' => array(
            array( 'key' => 'field_qtx_native_smoke_repeater_copy', 'name' => 'copy', 'type' => 'textarea' ),
            array( 'key' => 'field_qtx_native_smoke_repeater_id', 'name' => 'technical_id', 'type' => 'number' ),
        ),
    );
}
if ( $field_type_available( 'flexible_content' ) ) {
    $fields[] = array(
        'key' => 'field_qtx_native_smoke_flexible',
        'name' => $prefix . '_flexible',
        'type' => 'flexible_content',
        'layouts' => array(
            array(
                'key' => 'layout_qtx_native_smoke_hero',
                'name' => 'hero',
                'label' => 'Hero',
                'sub_fields' => array(
                    array( 'key' => 'field_qtx_native_smoke_flexible_copy', 'name' => 'copy', 'type' => 'wysiwyg' ),
                    array( 'key' => 'field_qtx_native_smoke_flexible_id', 'name' => 'technical_id', 'type' => 'number' ),
                ),
            ),
        ),
    );
}

$existing_fixture_options = $wpdb->get_col( $wpdb->prepare(
    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
    '%' . $wpdb->esc_like( $prefix ) . '%'
) );
if ( $existing_fixture_options !== array() ) {
    WP_CLI::error( 'Refusing to overwrite existing fixture options: ' . implode( ', ', $existing_fixture_options ) );
}
foreach ( $fields as $field ) {
    $created_field_keys[] = $field['key'];
}

acf_add_local_field_group( array(
    'key' => 'group_qtx_native_runtime_smoke',
    'title' => 'QTX native runtime smoke',
    'fields' => $fields,
    'location' => array(),
) );

try {
    foreach ( array( 'contact', 'message', 'button' ) as $name ) {
        $key = 'field_qtx_native_smoke_' . $name;
        update_field( $key, $raw_values[ $name ], $post_id );
        $assert_same( ucfirst( $name ) . ' raw storage', $raw_values[ $name ], get_field( $key, $post_id, false ) );
        $formatted = get_field( $key, $post_id, true );
        if ( $name === 'button' && is_string( $formatted ) ) {
            $formatted = trim( wp_strip_all_tags( $formatted ) );
        }
        $assert_same( ucfirst( $name ) . ' translated ' . strtoupper( $language ), $fixtures[ $language ][ $name ], $formatted );
    }

    $technical_raw = '[:lv]https://lv.example.test[:ru]https://ru.example.test[:en]https://en.example.test[:]';
    update_field( 'field_qtx_native_smoke_url', $technical_raw, $post_id );
    $assert_same( 'Technical URL remains untouched', $technical_raw, get_field( 'field_qtx_native_smoke_url', $post_id, true ) );

    if ( $field_type_available( 'group' ) ) {
        update_field( 'field_qtx_native_smoke_group', array(
            'field_qtx_native_smoke_group_copy' => $raw_values['contact'],
            'field_qtx_native_smoke_group_id' => 73,
        ), $post_id );
        $group = get_field( 'field_qtx_native_smoke_group', $post_id, true );
        $assert_same( 'Group multilingual leaf', $fixtures[ $language ]['contact'], $group['copy'] ?? null );
        $assert_same( 'Group technical leaf', 73, $group['technical_id'] ?? null );
    } else {
        $record( 'Group runtime', 'BLOCKED', 'field type unavailable' );
    }

    if ( $field_type_available( 'repeater' ) ) {
        update_field( 'field_qtx_native_smoke_repeater', array( array(
            'field_qtx_native_smoke_repeater_copy' => $raw_values['message'],
            'field_qtx_native_smoke_repeater_id' => 74,
        ) ), $post_id );
        $rows = get_field( 'field_qtx_native_smoke_repeater', $post_id, true );
        $assert_same( 'Repeater multilingual leaf', $fixtures[ $language ]['message'], $rows[0]['copy'] ?? null );
        $assert_same( 'Repeater technical leaf', 74, $rows[0]['technical_id'] ?? null );
    } else {
        $record( 'Repeater runtime', 'BLOCKED', 'ACF Pro field type unavailable' );
    }

    if ( $field_type_available( 'flexible_content' ) ) {
        update_field( 'field_qtx_native_smoke_flexible', array( array(
            'acf_fc_layout' => 'hero',
            'field_qtx_native_smoke_flexible_copy' => $raw_values['button'],
            'field_qtx_native_smoke_flexible_id' => 75,
        ) ), $post_id );
        $rows = get_field( 'field_qtx_native_smoke_flexible', $post_id, true );
        $copy = isset( $rows[0]['copy'] ) && is_string( $rows[0]['copy'] ) ? trim( wp_strip_all_tags( $rows[0]['copy'] ) ) : null;
        $assert_same( 'Flexible multilingual leaf', $fixtures[ $language ]['button'], $copy );
        $assert_same( 'Flexible technical leaf', 75, $rows[0]['technical_id'] ?? null );
        $assert_same( 'Flexible layout key', 'hero', $rows[0]['acf_fc_layout'] ?? null );
    } else {
        $record( 'Flexible runtime', 'BLOCKED', 'ACF Pro field type unavailable' );
    }

    $runtime = ( new \QTX\Integration\Acf\AcfRuntimeDetector( '5.6.0' ) )->detect();
    $record( 'ACF runtime', $runtime->isAvailable() ? 'PASS' : 'FAIL', (string) $runtime->version() );
    $record( 'ACF Pro runtime', $runtime->isPro() ? 'PASS' : 'BLOCKED', $runtime->isPro() ? 'detected' : 'not installed' );
    if ( function_exists( 'acf_add_options_page' ) ) {
        $options_page = acf_add_options_page( array(
            'page_title' => 'QTX native smoke',
            'menu_title' => 'QTX native smoke',
            'menu_slug' => 'qtx-native-smoke-options',
            'post_id' => 'options',
        ) );
        $assert_same( 'Options Page registration', 'options', $options_page['post_id'] ?? null );
        if ( function_exists( 'acf_get_options_page' ) ) {
            $registered_page = acf_get_options_page( 'qtx-native-smoke-options' );
            $assert_same( 'Options Page lookup', 'qtx-native-smoke-options', $registered_page['menu_slug'] ?? null );
        }
    } else {
        $record( 'Options Page API', 'BLOCKED', 'ACF Pro API unavailable' );
    }
} finally {
    foreach ( $created_field_keys as $field_key ) {
        delete_field( $field_key, $post_id );
    }
    $fixture_options = $wpdb->get_col( $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        '%' . $wpdb->esc_like( $prefix ) . '%'
    ) );
    foreach ( $fixture_options as $option_name ) {
        delete_option( $option_name );
    }
}

WP_CLI::log( wp_json_encode( array(
    'acf_version' => function_exists( 'acf_get_setting' ) ? acf_get_setting( 'version' ) : null,
    'language' => $language,
    'results' => $results,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

if ( $failures !== array() ) {
    WP_CLI::error( implode( '; ', $failures ) );
}

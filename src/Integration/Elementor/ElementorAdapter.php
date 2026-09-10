<?php

namespace QTX\Integration\Elementor;

/**
 * Built-in Elementor bridge.
 *
 * Elementor keeps a document as JSON in private post metadata. The bridge does
 * not filter or rewrite that metadata: Elementor remains the only owner of its
 * document schema. Text controls store the normal qTranslate-XT scalar value,
 * while translation is projected at Elementor's rendered-output boundary.
 */
final class ElementorAdapter {
    /** @var callable */
    private $isAvailable;
    private bool $registered = false;

    public function __construct( ?callable $isAvailable = null ) {
        $this->isAvailable = $isAvailable ?? static function (): bool {
            return class_exists( '\\Elementor\\Plugin' );
        };
    }

    public function register(): void {
        if ( $this->registered ) {
            return;
        }

        add_filter( 'elementor/widget/render_content', array( $this, 'translateRenderedContent' ), 99, 2 );
        add_filter( 'elementor/frontend/the_content', array( $this, 'translateRenderedContent' ), 99, 1 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueueFrontend' ), 30 );
        add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueueEditor' ) );
        $this->registered = true;
    }

    /**
     * Translate only the finished HTML string. Technical Elementor settings,
     * layout IDs, links, queries and the private `_elementor_data` JSON are
     * never translated at this boundary.
     *
     * @param mixed $content
     * @param mixed $widget
     * @return mixed
     */
    public function translateRenderedContent( $content, $widget = null ) {
        if ( ! is_string( $content ) || ! qtranxf_isMultilingual( $content ) ) {
            return $content;
        }

        global $q_config;
        if ( ! isset( $q_config['language'] ) || ! is_string( $q_config['language'] ) ) {
            return $content;
        }

        return qtranxf_use( $q_config['language'], $content, false, false );
    }

    public function enqueueFrontend(): void {
        if ( ! ( $this->isAvailable )() ) {
            return;
        }

        wp_register_script(
            'qtx-elementor-frontend',
            plugins_url( 'dist/elementor-frontend.js', QTRANSLATE_FILE ),
            array(),
            QTX_VERSION,
            true
        );
        wp_localize_script( 'qtx-elementor-frontend', 'qtxElementorFrontend', $this->languageSettings() );
        wp_enqueue_script( 'qtx-elementor-frontend' );
    }

    public function enqueueEditor(): void {
        if ( ! ( $this->isAvailable )() ) {
            return;
        }

        wp_enqueue_style(
            'qtx-elementor-editor',
            plugins_url( 'css/modules/elementor.css', QTRANSLATE_FILE ),
            array(),
            QTX_VERSION
        );
        wp_register_script(
            'qtx-elementor-editor',
            plugins_url( 'dist/elementor-editor.js', QTRANSLATE_FILE ),
            array( 'elementor-editor' ),
            QTX_VERSION,
            true
        );
        wp_localize_script( 'qtx-elementor-editor', 'qtxElementorEditor', $this->editorSettings() );
        wp_enqueue_script( 'qtx-elementor-editor' );
    }

    /** @return array<string, mixed> */
    private function languageSettings(): array {
        global $q_config;

        return array(
            'language'            => isset( $q_config['language'] ) ? (string) $q_config['language'] : '',
            'defaultLanguage'     => isset( $q_config['default_language'] ) ? (string) $q_config['default_language'] : '',
            'enabledLanguages'    => isset( $q_config['enabled_languages'] ) && is_array( $q_config['enabled_languages'] )
                ? array_values( array_filter( $q_config['enabled_languages'], 'is_string' ) )
                : array(),
            'languageCodePattern' => QTX_LANG_CODE_FORMAT,
        );
    }

    /** @return array<string, mixed> */
    private function editorSettings(): array {
        global $q_config;

        $settings = $this->languageSettings();
        $settings['languageNames'] = isset( $q_config['language_name'] ) && is_array( $q_config['language_name'] )
            ? $q_config['language_name']
            : array();

        return $settings;
    }
}

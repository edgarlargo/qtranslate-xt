<?php

class QTX_Module_Acf_Field_Wysiwyg extends acf_field_wysiwyg {
    /**
     * Setup the field type data
     */
    function initialize() {
        parent::initialize();
        $this->name     = 'qtranslate_wysiwyg';
        $this->category = QTX_Module_Acf_Extended::ACF_CATEGORY_QTX;
        $this->label    .= ' [' . $this->category . ']' . ' - ' . __( 'Deprecated', 'qtranslate' );
    }

    /**
     * Hook/override ACF render_field to create the HTML interface
     *
     * @param array $field
     */
    function render_field( $field ) {
        acf_enqueue_uploader();

        $default_editor = 'html';
        $show_tabs      = true;

        // minimum height is 300
        $height = acf_get_user_setting( 'wysiwyg_height', 300 );
        $height = max( $height, 300 );

        // detect mode
        if ( ! user_can_richedit() ) {
            $show_tabs = false;
        } elseif ( $field['tabs'] == 'visual' ) {
            // visual tab only
            $default_editor = 'tinymce';
            $show_tabs      = false;
        } elseif ( $field['tabs'] == 'text' ) {
            // text tab only
            $show_tabs = false;
        } elseif ( wp_default_editor() == 'tinymce' ) {
            // both tabs
            $default_editor = 'tinymce';
        }

        // must be logged in tp upload
        if ( ! current_user_can( 'upload_files' ) ) {
            $field['media_upload'] = 0;
        }

        // set mode
        $switch_class = ( $default_editor === 'html' ) ? 'html-active' : 'tmce-active';

        // filter value for editor
        remove_filter( 'acf_the_editor_content', 'format_for_editor', 10 );
        remove_filter( 'acf_the_editor_content', 'wp_htmledit_pre', 10 );
        remove_filter( 'acf_the_editor_content', 'wp_richedit_pre', 10 );

        add_filter( 'acf_the_editor_content', 'format_for_editor', 10, 2 );

        global $q_config;

        $languages       = qtranxf_getSortedLanguages( true );
        $values          = QTX_Module_Acf_Extended::decode_language_values( $field['value'] );
        $currentLanguage = qtranxf_getLanguage();

        echo '<div class="multi-language-field multi-language-field-wysiwyg">';

        foreach ( $languages as $language ) {
            $class = ( $language === $currentLanguage ) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
            echo '<a class="' . esc_attr( $class ) . '" data-language="' . esc_attr( $language ) . '">' . esc_html( $q_config['language_name'][ $language ] ) . '</a>';
        }

        $uid       = uniqid( 'acf-editor-' );
        foreach ( $languages as $language ):

            $id = $uid . "-$language";
            $name  = $field['name'] . "[$language]";
            $class = $switch_class;
            if ( $language === $currentLanguage ) {
                $class .= ' current-language';
            }

            $value = apply_filters( 'acf_the_editor_content', $values[ $language ], $default_editor );
            // Match the native WordPress editor safeguard without double-escaping rich content.
            $value = preg_replace( '#</textarea#i', '&lt;/textarea', (string) $value );

            ?>
            <div id="wp-<?php echo esc_attr( $id ); ?>-wrap" class="acf-editor-wrap wp-core-ui wp-editor-wrap <?php echo esc_attr( $class ); ?>"
                 data-toolbar="<?php echo esc_attr( $field['toolbar'] ); ?>" data-upload="<?php echo esc_attr( $field['media_upload'] ); ?>"
                 data-language="<?php echo esc_attr( $language ); ?>">
                <div id="wp-<?php echo esc_attr( $id ); ?>-editor-tools" class="wp-editor-tools hide-if-no-js">
                    <?php if ( $field['media_upload'] ): ?>
                        <div id="wp-<?php echo esc_attr( $id ); ?>-media-buttons" class="wp-media-buttons">
                            <?php do_action( 'media_buttons' ); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( user_can_richedit() && $show_tabs ): ?>
                        <div class="wp-editor-tabs">
                            <button id="<?php echo esc_attr( $id ); ?>-tmce"
                                    class="wp-switch-editor switch-tmce" data-wp-editor-id="<?php echo esc_attr( $id ); ?>"
                                    type="button"><?php echo esc_html__( 'Visual', 'acf' ); ?></button>
                            <button id="<?php echo esc_attr( $id ); ?>-html"
                                    class="wp-switch-editor switch-html" data-wp-editor-id="<?php echo esc_attr( $id ); ?>"
                                    type="button"><?php echo esc_html_x( 'Text', 'Name for the Text editor tab (formerly HTML)', 'acf' ); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="wp-<?php echo esc_attr( $id ); ?>-editor-container" class="wp-editor-container">
                    <textarea id="<?php echo esc_attr( $id ); ?>" class="qtx-wp-editor-area qtranxs-translatable"
                              name="<?php echo esc_attr( $name ); ?>"
                              <?php if ( $height ): ?>style="height:<?php echo absint( $height ); ?>px;"<?php endif; ?>><?php echo $value; ?></textarea>
                </div>
            </div>

        <?php endforeach;

        echo '</div>';
    }

    /**
     * Hook/override ACF update_value
     *
     * @param array $values - the values to save in database
     * @param int $post_id - the post_id of which the value will be saved
     * @param array $field - the field array holding all the field options
     *
     * @return    string - the modified value
     */
    function update_value( $values, $post_id, $field ) {
        return QTX_Module_Acf_Extended::encode_language_values( $values );
    }

    /**
     *  Hook/override ACF validation to handle the value formatted to a multi-lang array instead of string
     *
     * @param bool|string $valid
     * @param array $value containing values per language
     * @param string $field
     * @param string $input
     *
     * @return bool|string
     * @see acf_validation::acf_validate_value
     */
    function validate_value( $valid, $value, $field, $input ) {
        if ( is_array( $value ) ) {
            $valid = QTX_Module_Acf_Extended::validate_language_values( $this, $valid, $value, $field, $input );
        }

        return $valid;
    }
}

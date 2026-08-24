<?php

namespace QTX\Integration\Acf;

final class AcfLifecycleAdapter {
    private AcfFieldSchema $schema;
    /** @var callable */
    private $translateScalar;
    private AcfValueContext $context;
    private bool $registered = false;

    public function __construct( AcfFieldSchema $schema, callable $translateScalar, ?AcfValueContext $context = null ) {
        $this->schema = $schema;
        $this->translateScalar = $translateScalar;
        $this->context = $context ?? new AcfValueContext( AcfValueContext::TRANSLATED );
    }

    public function register(): void {
        if ( $this->registered ) {
            return;
        }
        foreach ( $this->supportedLeafTypes() as $type ) {
            add_filter( 'acf/format_value/type=' . $type, array( $this, 'formatValue' ), 5, 3 );
        }
        $this->registered = true;
    }

    public function unregister(): void {
        if ( ! $this->registered ) {
            return;
        }
        foreach ( $this->supportedLeafTypes() as $type ) {
            remove_filter( 'acf/format_value/type=' . $type, array( $this, 'formatValue' ), 5 );
        }
        $this->registered = false;
    }

    /** @param mixed $postId
     *  @param array<string, mixed> $field
     */
    public function formatValue( $value, $postId, array $field ) {
        if ( ! $this->context->shouldTranslate() ) {
            return $value;
        }
        $definitions = $this->schema->discover( array( $field ) );
        if ( $definitions === array() ) {
            return $value;
        }
        $projector = new AcfValueProjector( $definitions, $this->translateScalar );

        return $projector->project( $field, $value );
    }

    /** @return string[] */
    private function supportedLeafTypes(): array {
        return array(
            'text',
            'textarea',
            'wysiwyg',
            'qtranslate_text',
            'qtranslate_textarea',
            'qtranslate_wysiwyg',
        );
    }
}

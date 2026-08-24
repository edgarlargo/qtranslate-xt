<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\Acf\AcfFieldSchema;
use QTX\Integration\Acf\AcfValueProjector;

final class AcfValueProjectorTest extends TestCase {
    public function testProjectsOnlyRegisteredLeavesAndPreservesCompoundShape(): void {
        $field = array(
            'key' => 'field_sections',
            'name' => 'sections',
            'type' => 'repeater',
            'sub_fields' => array(
                array( 'key' => 'field_heading', 'name' => 'heading', 'type' => 'text' ),
                array( 'key' => 'field_image', 'name' => 'image', 'type' => 'image' ),
                array(
                    'key' => 'field_details',
                    'name' => 'details',
                    'type' => 'group',
                    'sub_fields' => array(
                        array( 'key' => 'field_body', 'name' => 'body', 'type' => 'wysiwyg' ),
                        array( 'key' => 'field_email', 'name' => 'email', 'type' => 'email' ),
                    ),
                ),
            ),
        );
        $schema = new AcfFieldSchema();
        $definitions = $schema->discover( array( $field ) );
        $projector = new AcfValueProjector( $definitions, static fn ( string $value ): string => 'translated:' . $value );
        $value = array(
            array(
                'heading' => '[:lv]Virsraksts[:ru]Заголовок[:]',
                'image' => 42,
                'details' => array(
                    'body' => '[:lv]<b>Saturs</b>[:ru]<i>Текст</i>[:]',
                    'email' => 'editor@example.test',
                ),
                'unregistered' => 'keep',
            ),
        );

        $projected = $projector->project( $field, $value );
        self::assertStringStartsWith( 'translated:', $projected[0]['heading'] );
        self::assertStringStartsWith( 'translated:', $projected[0]['details']['body'] );
        self::assertSame( 42, $projected[0]['image'] );
        self::assertSame( 'editor@example.test', $projected[0]['details']['email'] );
        self::assertSame( 'keep', $projected[0]['unregistered'] );
    }

    public function testFlexibleLayoutsKeepTechnicalLayoutAndUnknownRows(): void {
        $field = array(
            'key' => 'field_content',
            'type' => 'flexible_content',
            'layouts' => array(
                array(
                    'name' => 'hero',
                    'sub_fields' => array(
                        array( 'key' => 'field_hero_text', 'name' => 'text', 'type' => 'textarea' ),
                    ),
                ),
            ),
        );
        $projector = new AcfValueProjector(
            ( new AcfFieldSchema() )->discover( array( $field ) ),
            static fn ( string $value ): string => strtoupper( $value )
        );
        $value = array(
            array( 'acf_fc_layout' => 'hero', 'text' => 'hello' ),
            array( 'acf_fc_layout' => 'unknown', 'text' => 'keep' ),
        );

        self::assertSame(
            array(
                array( 'acf_fc_layout' => 'hero', 'text' => 'HELLO' ),
                array( 'acf_fc_layout' => 'unknown', 'text' => 'keep' ),
            ),
            $projector->project( $field, $value )
        );
    }

    public function testObjectsAndSerializedLookingLeavesRemainUntouched(): void {
        $field = array( 'key' => 'field_text', 'name' => 'text', 'type' => 'text' );
        $projector = new AcfValueProjector(
            ( new AcfFieldSchema() )->discover( array( $field ) ),
            static fn ( string $value ): string => 'changed'
        );
        $object = (object) array( 'value' => 'raw' );
        $serialized = 'a:1:{s:4:"text";s:3:"raw";}';

        self::assertSame( $object, $projector->project( $field, $object ) );
        self::assertSame( $serialized, $projector->project( $field, $serialized ) );
    }
}

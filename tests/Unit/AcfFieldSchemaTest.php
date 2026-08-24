<?php

namespace QTX\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QTX\Integration\Acf\AcfFieldSchema;

final class AcfFieldSchemaTest extends TestCase {
    public function testDiscoversOnlyWhitelistedStableLeafKeys(): void {
        $definitions = ( new AcfFieldSchema() )->discover( array(
            array( 'key' => 'field_title', 'type' => 'text' ),
            array( 'key' => 'field_summary', 'type' => 'textarea' ),
            array( 'key' => 'field_body', 'type' => 'wysiwyg' ),
            array( 'key' => 'field_image', 'type' => 'image' ),
            array( 'key' => 'field_url', 'type' => 'url' ),
            array( 'key' => 'field_email', 'type' => 'email' ),
            array( 'key' => 'name-not-key', 'type' => 'text' ),
        ) );

        self::assertSame( array( 'field_title', 'field_summary', 'field_body' ), array_keys( $definitions ) );
        self::assertSame( 'text', $definitions['field_title']->valueType() );
        self::assertSame( 'html', $definitions['field_body']->valueType() );
    }

    public function testRecursesThroughGroupRepeaterAndFlexibleContentLeaves(): void {
        $definitions = ( new AcfFieldSchema() )->discover( array(
            array(
                'key' => 'field_group',
                'type' => 'group',
                'sub_fields' => array(
                    array( 'key' => 'field_group_text', 'type' => 'text' ),
                    array(
                        'key' => 'field_repeater',
                        'type' => 'repeater',
                        'sub_fields' => array(
                            array( 'key' => 'field_row_body', 'type' => 'wysiwyg' ),
                            array( 'key' => 'field_row_relation', 'type' => 'relationship' ),
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_flexible',
                'type' => 'flexible_content',
                'layouts' => array(
                    array(
                        'name' => 'hero',
                        'sub_fields' => array(
                            array( 'key' => 'field_hero_copy', 'type' => 'textarea' ),
                            array( 'key' => 'field_hero_color', 'type' => 'color_picker' ),
                        ),
                    ),
                ),
            ),
        ) );

        self::assertSame(
            array( 'field_group_text', 'field_row_body', 'field_hero_copy' ),
            array_keys( $definitions )
        );
    }

    public function testAcceptsStableUnicodeKeysAndRejectsUnsafeSeparators(): void {
        $definitions = ( new AcfFieldSchema() )->discover( array(
            array( 'key' => 'field_vārds_uzvārds', 'type' => 'text' ),
            array( 'key' => 'field_tālrunis_placeholder', 'type' => 'textarea' ),
            array( 'key' => 'field_имя_поля', 'type' => 'wysiwyg' ),
            array( 'key' => 'field_has space', 'type' => 'text' ),
            array( 'key' => 'field_has/slash', 'type' => 'text' ),
            array( 'key' => "field_bad\0key", 'type' => 'text' ),
        ) );

        self::assertSame(
            array( 'field_vārds_uzvārds', 'field_tālrunis_placeholder', 'field_имя_поля' ),
            array_keys( $definitions )
        );
    }

    public function testRejectsConflictingKeysAndBoundsNesting(): void {
        $this->expectException( InvalidArgumentException::class );
        ( new AcfFieldSchema() )->discover( array(
            array( 'key' => 'field_duplicate', 'type' => 'text' ),
            array( 'key' => 'field_duplicate', 'type' => 'wysiwyg' ),
        ) );
    }

    public function testNestingLimitIsEnforced(): void {
        $this->expectException( InvalidArgumentException::class );
        ( new AcfFieldSchema( 1 ) )->discover( array(
            array(
                'key' => 'field_group',
                'type' => 'group',
                'sub_fields' => array( array( 'key' => 'field_nested', 'type' => 'text' ) ),
            ),
        ) );
    }
}

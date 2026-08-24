<?php

use PHPUnit\Framework\TestCase;

final class DateTimeCompatibilityTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['qtx_test_deprecated_functions'] = array();
        date_default_timezone_set( 'UTC' );
    }

    public function testDeprecatedWrapperPreservesEmptyFormatDefault(): void {
        self::assertSame( 'fallback', qtranxf_strftime( '', 0, 'fallback', 'before', 'after' ) );
        self::assertSame(
            array( array( 'qtranxf_strftime', '3.13.0', 'qxtranxf_intl_strftime' ) ),
            $GLOBALS['qtx_test_deprecated_functions']
        );
    }

    public function testDeprecatedWrapperDelegatesKnownFormatsToIntlFormatter(): void {
        $timestamp = strtotime( '2024-02-21 13:05:09 UTC' );
        $format    = '%Y-%m-%d %H:%M:%S %%';

        self::assertSame(
            'before:' . qxtranxf_intl_strftime( $format, $timestamp ) . ':after',
            qtranxf_strftime( $format, $timestamp, '', 'before:', ':after' )
        );
    }

    public function testDeprecatedWrapperPreservesExtendedQtranslateFormats(): void {
        $timestamp = strtotime( '2024-02-21 13:05:09 UTC' );

        self::assertSame(
            '21st 3 51 2 29 1 586 1 13 000000 UTC 0 +0000 +00:00 UTC 0 '
                . '2024-02-21T13:05:09+00:00 Wed, 21 Feb 2024 13:05:09 +0000 1708520709',
            qtranxf_strftime( '%E%q %f %F %i %J %k %K %l %L %N %Q %o %O %s %v %1 %2 %3 %4', $timestamp )
        );
    }

    public function testIntlFormatterHandlesNumericExtendedFormatsDirectly(): void {
        $timestamp = strtotime( '2024-02-21 13:05:09 UTC' );

        self::assertSame(
            '0 2024-02-21T13:05:09+00:00 Wed, 21 Feb 2024 13:05:09 +0000 1708520709',
            qxtranxf_intl_strftime( '%1 %2 %3 %4', $timestamp )
        );
    }
}

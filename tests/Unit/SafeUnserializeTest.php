<?php

use PHPUnit\Framework\TestCase;

final class QtxUnsafeWakeupFixture {
    public static int $wakeups = 0;
    public function __wakeup(): void { ++self::$wakeups; }
}

final class SafeUnserializeTest extends TestCase {
    protected function setUp(): void {
        QtxUnsafeWakeupFixture::$wakeups = 0;
    }

    public function testScalarAndArrayCompatibilityIsPreserved(): void {
        self::assertSame( array( 'lv' => 'Sveiki', 'id' => 42 ), qtranxf_maybe_unserialize_safe( 'a:2:{s:2:"lv";s:6:"Sveiki";s:2:"id";i:42;}' ) );
        self::assertSame( false, qtranxf_maybe_unserialize_safe( 'b:0;' ) );
        self::assertSame( 'plain', qtranxf_maybe_unserialize_safe( 'plain' ) );
        self::assertSame( 42, qtranxf_maybe_unserialize_safe( 42 ) );
    }

    public function testObjectPayloadCannotInvokeMagicMethods(): void {
        $payload = serialize( new QtxUnsafeWakeupFixture() );
        $value = qtranxf_maybe_unserialize_safe( $payload );

        self::assertSame( 0, QtxUnsafeWakeupFixture::$wakeups );
        self::assertInstanceOf( __PHP_Incomplete_Class::class, $value );
    }

    public function testObjectNestedInArrayCannotInvokeMagicMethods(): void {
        $payload = serialize( array( 'object' => new QtxUnsafeWakeupFixture(), 'text' => 'keep' ) );
        $value = qtranxf_maybe_unserialize_safe( $payload );

        self::assertSame( 0, QtxUnsafeWakeupFixture::$wakeups );
        self::assertSame( 'keep', $value['text'] );
        self::assertInstanceOf( __PHP_Incomplete_Class::class, $value['object'] );
    }
}

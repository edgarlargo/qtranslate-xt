<?php

use PHPUnit\Framework\TestCase;
use QTX\Core\Config\LocalSqlFilePolicy;

final class LocalSqlFilePolicyTest extends TestCase {
    private string $directory;

    protected function setUp(): void {
        $this->directory = sys_get_temp_dir() . '/qtx-sql-policy-' . bin2hex( random_bytes( 5 ) );
        mkdir( $this->directory );
    }

    protected function tearDown(): void {
        foreach ( glob( $this->directory . '/*' ) ?: array() as $file ) {
            unlink( $file );
        }
        rmdir( $this->directory );
    }

    public function testApprovesCanonicalSqlInsideRegisteredRoot(): void {
        $path = $this->directory . '/backup.sql';
        file_put_contents( $path, '-- fixture' );

        self::assertSame( realpath( $path ), ( new LocalSqlFilePolicy( array( $this->directory ) ) )->approveInput( $path ) );
    }

    public function testRejectsWrongExtensionWrappersAndOutsideRoot(): void {
        $json = $this->directory . '/backup.json';
        file_put_contents( $json, '{}' );
        $outside = tempnam( sys_get_temp_dir(), 'qtx-outside-' );
        $outsideSql = $outside . '.sql';
        rename( $outside, $outsideSql );
        try {
            $policy = new LocalSqlFilePolicy( array( $this->directory ) );
            self::assertNull( $policy->approveInput( $json ) );
            self::assertNull( $policy->approveInput( 'php://filter/resource=' . $json ) );
            self::assertNull( $policy->approveInput( $outsideSql ) );
        } finally {
            unlink( $outsideSql );
        }
    }
}

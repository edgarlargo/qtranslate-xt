<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Core\Config\I18nConfigFilePolicy;

final class I18nConfigFilePolicyTest extends TestCase {
    private string $root;
    private string $outside;

    protected function setUp(): void {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qtx-config-policy-' . bin2hex( random_bytes( 6 ) );
        $this->root = $base . DIRECTORY_SEPARATOR . 'content';
        $this->outside = $base . DIRECTORY_SEPARATOR . 'outside';
        mkdir( $this->root, 0777, true );
        mkdir( $this->outside, 0777, true );
    }

    protected function tearDown(): void {
        foreach ( array( $this->root, $this->outside ) as $directory ) {
            foreach ( glob( $directory . DIRECTORY_SEPARATOR . '*' ) ?: array() as $file ) {
                unlink( $file );
            }
            rmdir( $directory );
        }
        rmdir( dirname( $this->root ) );
    }

    public function testResolvesOnlyJsonInsideApprovedCanonicalRoot(): void {
        $valid = $this->root . DIRECTORY_SEPARATOR . 'i18n-config.json';
        file_put_contents( $valid, '{"front-config":[]}' );
        $policy = new I18nConfigFilePolicy( array( $this->root ) );

        self::assertSame( realpath( $valid ), $policy->resolve( 'i18n-config.json', $this->root, $this->root ) );
        self::assertSame( realpath( $valid ), $policy->resolve( './i18n-config.json', $this->root, $this->outside ) );
    }

    public function testRejectsTraversalAbsoluteOutsideWrappersAndWrongExtension(): void {
        $outside = $this->outside . DIRECTORY_SEPARATOR . 'secret.json';
        file_put_contents( $outside, '{"front-config":[]}' );
        file_put_contents( $this->root . DIRECTORY_SEPARATOR . 'config.php', '{}' );
        $policy = new I18nConfigFilePolicy( array( $this->root ) );

        self::assertNull( $policy->resolve( '../outside/secret.json', $this->root, $this->root ) );
        self::assertNull( $policy->resolve( $outside, $this->root, $this->root ) );
        self::assertNull( $policy->resolve( 'php://filter/resource=i18n-config.json', $this->root, $this->root ) );
        self::assertNull( $policy->resolve( 'config.php', $this->root, $this->root ) );
    }

    public function testSizeLimitAppliesToResolveAndRead(): void {
        $file = $this->root . DIRECTORY_SEPARATOR . 'large.json';
        file_put_contents( $file, str_repeat( 'x', 17 ) );
        $policy = new I18nConfigFilePolicy( array( $this->root ), 16 );

        self::assertNull( $policy->resolve( 'large.json', $this->root, $this->root ) );
        self::assertNull( $policy->read( $file ) );
    }

    public function testSchemaSupportsLegacyAndVersionOneOnly(): void {
        $policy = new I18nConfigFilePolicy( array( $this->root ) );

        self::assertTrue( $policy->validateSchema( array( 'front-config' => array() ) ) );
        self::assertTrue( $policy->validateSchema( array( 'schema-version' => 1, 'admin-config' => array() ) ) );
        self::assertFalse( $policy->validateSchema( array( 'schema-version' => 2, 'front-config' => array() ) ) );
        self::assertFalse( $policy->validateSchema( array( 'front-config' => 'invalid' ) ) );
        self::assertFalse( $policy->validateSchema( array( 'unrecognized' => array() ) ) );
    }

    public function testPackagedDefaultConfigurationRemainsReadableInLegacyMode(): void {
        $policy = new I18nConfigFilePolicy( array( QTRANSLATE_DIR ) );
        $resolved = $policy->resolve( './i18n-config.json', QTRANSLATE_DIR, dirname( QTRANSLATE_DIR ) );

        self::assertSame( realpath( QTRANSLATE_DIR . '/i18n-config.json' ), $resolved );
        $decoded = json_decode( $policy->read( $resolved ), true );
        self::assertIsArray( $decoded );
        self::assertTrue( $policy->validateSchema( $decoded ) );
    }
}

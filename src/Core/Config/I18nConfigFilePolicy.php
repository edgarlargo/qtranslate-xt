<?php

namespace QTX\Core\Config;

final class I18nConfigFilePolicy {
    /** @var string[] */
    private array $roots;
    private int $maximumBytes;

    /** @param string[] $roots */
    public function __construct( array $roots, int $maximumBytes = 1048576 ) {
        $this->roots = array();
        foreach ( $roots as $root ) {
            if ( ! is_string( $root ) ) {
                continue;
            }
            $canonical = realpath( $root );
            if ( $canonical !== false && is_dir( $canonical ) ) {
                $this->roots[] = rtrim( $canonical, '/\\' );
            }
        }
        $this->roots = array_values( array_unique( $this->roots ) );
        $this->maximumBytes = max( 1, $maximumBytes );
    }

    public function resolve( string $configuredPath, string $pluginRoot, string $contentRoot ): ?string {
        if ( $configuredPath === '' || preg_match( '#^[a-z][a-z0-9+.-]*://#i', $configuredPath ) ) {
            return null;
        }
        if ( substr( $configuredPath, -5 ) !== '.json' ) {
            return null;
        }
        if ( strncmp( $configuredPath, './', 2 ) === 0 ) {
            $candidate = rtrim( $pluginRoot, '/\\' ) . DIRECTORY_SEPARATOR . substr( $configuredPath, 2 );
        } elseif ( $this->isAbsolutePath( $configuredPath ) ) {
            $candidate = $configuredPath;
        } else {
            $candidate = rtrim( $contentRoot, '/\\' ) . DIRECTORY_SEPARATOR . $configuredPath;
        }
        $canonical = realpath( $candidate );
        if ( $canonical === false || ! is_file( $canonical ) || !$this->isWithinRoot( $canonical ) ) {
            return null;
        }
        $size = filesize( $canonical );
        if ( $size === false || $size > $this->maximumBytes ) {
            return null;
        }

        return $canonical;
    }

    public function read( string $canonicalPath ): ?string {
        if ( !$this->isWithinRoot( $canonicalPath ) || ! is_file( $canonicalPath ) ) {
            return null;
        }
        $contents = file_get_contents( $canonicalPath, false, null, 0, $this->maximumBytes + 1 );
        if ( $contents === false || strlen( $contents ) > $this->maximumBytes ) {
            return null;
        }

        return $contents;
    }

    /** @param array<string, mixed> $config */
    public function validateSchema( array $config ): bool {
        if ( isset( $config['schema-version'] ) && $config['schema-version'] !== 1 && $config['schema-version'] !== '1' ) {
            return false;
        }
        $recognized = false;
        foreach ( array( 'admin-config', 'front-config' ) as $key ) {
            if ( ! array_key_exists( $key, $config ) ) {
                continue;
            }
            if ( ! is_array( $config[ $key ] ) ) {
                return false;
            }
            $recognized = true;
        }

        return $recognized;
    }

    private function isWithinRoot( string $path ): bool {
        $canonical = realpath( $path );
        if ( $canonical === false ) {
            return false;
        }
        foreach ( $this->roots as $root ) {
            $prefix = $root . DIRECTORY_SEPARATOR;
            if ( DIRECTORY_SEPARATOR === '\\' ) {
                if ( strncasecmp( $canonical, $prefix, strlen( $prefix ) ) === 0 ) {
                    return true;
                }
            } elseif ( strncmp( $canonical, $prefix, strlen( $prefix ) ) === 0 ) {
                return true;
            }
        }

        return false;
    }

    private function isAbsolutePath( string $path ): bool {
        return $path[0] === '/' || $path[0] === '\\' || preg_match( '/^[a-z]:[\\\\\/]/i', $path ) === 1;
    }
}

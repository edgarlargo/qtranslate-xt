<?php

namespace QTX\Core\Config;

final class LocalSqlFilePolicy {
    /** @var string[] */
    private array $roots;

    /** @param string[] $roots */
    public function __construct( array $roots ) {
        $this->roots = array();
        foreach ( $roots as $root ) {
            if ( ! is_string( $root ) || $root === '' ) {
                continue;
            }
            $canonical = realpath( $root );
            if ( $canonical !== false && is_dir( $canonical ) ) {
                $this->roots[] = $this->normalize( $canonical );
            }
        }
    }

    public function approveInput( string $path ): ?string {
        if ( $path === '' || preg_match( '#^[a-z][a-z0-9+.-]*://#i', $path ) === 1 ) {
            return null;
        }
        if ( strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) !== 'sql' ) {
            return null;
        }
        $canonical = realpath( $path );
        if ( $canonical === false || ! is_file( $canonical ) || ! is_readable( $canonical ) ) {
            return null;
        }
        $normalized = $this->normalize( $canonical );
        foreach ( $this->roots as $root ) {
            if ( $normalized === $root || strncmp( $normalized, $root . '/', strlen( $root ) + 1 ) === 0 ) {
                return $canonical;
            }
        }

        return null;
    }

    private function normalize( string $path ): string {
        $path = str_replace( '\\', '/', rtrim( $path, "/\\" ) );

        return DIRECTORY_SEPARATOR === '\\' ? strtolower( $path ) : $path;
    }
}

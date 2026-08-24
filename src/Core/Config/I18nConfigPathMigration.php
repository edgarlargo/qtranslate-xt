<?php

namespace QTX\Core\Config;

/** Repairs the packaged config path after the plugin directory is renamed. */
final class I18nConfigPathMigration {
    private const BUNDLED_PATH = './i18n-config.json';

    /** @param mixed[] $paths
     *  @return mixed[]
     */
    public static function repairBundledPath( array $paths ): array {
        $repaired = array();
        foreach ( $paths as $path ) {
            if ( is_string( $path ) && self::isLegacyBundledPath( $path ) ) {
                $path = self::BUNDLED_PATH;
            }
            if ( $path === self::BUNDLED_PATH && in_array( self::BUNDLED_PATH, $repaired, true ) ) {
                continue;
            }
            $repaired[] = $path;
        }

        return $repaired;
    }

    private static function isLegacyBundledPath( string $path ): bool {
        $normalized = ltrim( str_replace( '\\', '/', trim( $path ) ), '/' );

        return preg_match( '#^plugins/qtranslate-xt(?:-master)?/i18n-config\.json$#i', $normalized ) === 1;
    }
}

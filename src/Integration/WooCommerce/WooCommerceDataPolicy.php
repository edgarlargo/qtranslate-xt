<?php

namespace QTX\Integration\WooCommerce;

final class WooCommerceDataPolicy {
    /** @var string[] */
    private const TECHNICAL_META_KEYS = array(
        '_sku', '_price', '_regular_price', '_sale_price', '_stock', '_stock_status',
        '_manage_stock', '_backorders', '_sold_individually', '_virtual', '_downloadable',
        '_tax_status', '_tax_class', '_weight', '_length', '_width', '_height',
        '_product_attributes', '_default_attributes',
        '_thumbnail_id', '_product_image_gallery', '_download_limit', '_download_expiry',
        '_transaction_id', '_payment_method', '_payment_method_title', '_order_key',
        '_cart_hash', '_customer_user', '_customer_ip_address', '_customer_user_agent',
    );

    public function isTechnicalMetaKey( string $key ): bool {
        if ( $key === '' || in_array( $key, self::TECHNICAL_META_KEYS, true ) ) {
            return true;
        }

        return preg_match( '/^_(?:downloadable_files|download_permissions_granted|recorded_(?:sales|coupon_usage_counts)|wc_|billing_|shipping_)/', $key ) === 1;
    }

    public function translatePresentationValue( $value, callable $translate ) {
        if ( ! is_string( $value ) || $value === '' ) {
            return $value;
        }

        return $translate( $value );
    }

    /** @param mixed $cart */
    public function cartHash( $cart, string $language ): string {
        return md5( json_encode( $cart ) . $language );
    }

    /** @param string[] $enabledLanguages */
    public function explicitOrderLanguage( $storedLanguage, array $enabledLanguages ): ?string {
        $normalized = is_string( $storedLanguage ) ? strtolower( $storedLanguage ) : '';
        if ( in_array( $normalized, $enabledLanguages, true ) ) {
            return $normalized;
        }

        return null;
    }

    /** @param string[] $enabledLanguages */
    public function orderLanguage( $storedLanguage, string $currentLanguage, array $enabledLanguages ): string {
        $explicit = $this->explicitOrderLanguage( $storedLanguage, $enabledLanguages );
        if ( $explicit !== null ) {
            return $explicit;
        }

        return in_array( $currentLanguage, $enabledLanguages, true )
            ? $currentLanguage
            : ( $enabledLanguages[0] ?? '' );
    }

    /** @param string[] $enabledLanguages
     *  @return string[]
     */
    public function webhookCacheGroups( array $enabledLanguages ): array {
        $groups = array( 'posts', 'post_meta', 'terms', 'term_meta' );
        foreach ( $enabledLanguages as $language ) {
            if ( ! is_string( $language ) || $language === '' ) {
                continue;
            }
            $groups[] = 'post_meta' . $language;
            $groups[] = 'term_meta' . $language;
        }

        return array_values( array_unique( $groups ) );
    }
}

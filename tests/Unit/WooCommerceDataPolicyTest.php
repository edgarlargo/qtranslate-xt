<?php

namespace QTX\Tests\Unit;

use PHPUnit\Framework\TestCase;
use QTX\Integration\WooCommerce\WooCommerceDataPolicy;

final class WooCommerceDataPolicyTest extends TestCase {
    public function testTechnicalCommerceMetadataIsNeverSelectedForTranslation(): void {
        $policy = new WooCommerceDataPolicy();

        foreach ( array(
            '', '_sku', '_price', '_regular_price', '_sale_price', '_stock', '_stock_status',
            '_product_attributes', '_default_attributes', '_transaction_id', '_payment_method',
            '_order_key', '_cart_hash', '_billing_email', '_shipping_address_1', '_wc_order_attribution_source_type',
        ) as $key ) {
            self::assertTrue( $policy->isTechnicalMetaKey( $key ), $key );
        }
    }

    public function testExplicitHumanReadableMetadataMayUseNormalTranslationPath(): void {
        $policy = new WooCommerceDataPolicy();

        self::assertFalse( $policy->isTechnicalMetaKey( '_purchase_note' ) );
        self::assertFalse( $policy->isTechnicalMetaKey( 'custom_product_label' ) );
    }

    public function testPresentationAdapterNeverTraversesTechnicalStructures(): void {
        $policy = new WooCommerceDataPolicy();
        $translate = static fn ( string $value ): string => 'translated:' . $value;
        $technical = array( 'sku' => 'ABC', 'price' => '19.95', 'stock' => 4 );

        self::assertSame( 'translated:[:lv]Produkts[:en]Product[:]', $policy->translatePresentationValue( '[:lv]Produkts[:en]Product[:]', $translate ) );
        self::assertSame( $technical, $policy->translatePresentationValue( $technical, $translate ) );
        self::assertSame( 19.95, $policy->translatePresentationValue( 19.95, $translate ) );
        self::assertSame( 42, $policy->translatePresentationValue( 42, $translate ) );
        self::assertSame( false, $policy->translatePresentationValue( false, $translate ) );
    }

    public function testCartHashVariesByLanguageWithoutChangingCartData(): void {
        $policy = new WooCommerceDataPolicy();
        $cart = array( 'sku' => 'ABC', 'price' => '19.95', 'quantity' => 2 );

        self::assertSame( md5( json_encode( $cart ) . 'lv' ), $policy->cartHash( $cart, 'lv' ) );
        self::assertNotSame( $policy->cartHash( $cart, 'lv' ), $policy->cartHash( $cart, 'ru' ) );
        self::assertSame( array( 'sku' => 'ABC', 'price' => '19.95', 'quantity' => 2 ), $cart );
    }

    public function testOrderLanguageUsesOnlyConfiguredExplicitContext(): void {
        $policy = new WooCommerceDataPolicy();
        $enabled = array( 'lv', 'ru', 'en' );

        self::assertSame( 'ru', $policy->orderLanguage( 'RU', 'lv', $enabled ) );
        self::assertSame( 'lv', $policy->orderLanguage( '../', 'lv', $enabled ) );
        self::assertSame( 'en', $policy->orderLanguage( null, 'invalid', array( 'en' ) ) );
        self::assertSame( 'ru', $policy->explicitOrderLanguage( 'RU', $enabled ) );
        self::assertNull( $policy->explicitOrderLanguage( '../', $enabled ) );
        self::assertNull( $policy->explicitOrderLanguage( null, $enabled ) );
    }

    public function testWebhookInvalidationIsLimitedToPresentationCacheGroups(): void {
        $policy = new WooCommerceDataPolicy();

        self::assertSame(
            array( 'posts', 'post_meta', 'terms', 'term_meta', 'post_metalv', 'term_metalv', 'post_metaru', 'term_metaru' ),
            $policy->webhookCacheGroups( array( 'lv', 'ru', 'lv', null ) )
        );
        self::assertNotContains( 'options', $policy->webhookCacheGroups( array( 'lv' ) ) );
        self::assertNotContains( 'users', $policy->webhookCacheGroups( array( 'lv' ) ) );
    }
}

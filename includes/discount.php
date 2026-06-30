<?php
/**
 * Applies the active group's discount: a cart fee (default) or adjusted product prices.
 *
 * @package WooCustomerGroupDiscount
 */

defined( 'ABSPATH' ) || exit;

/**
 * Discount application.
 */
class WCGD_Discount {

	/**
	 * Hook the right strategy based on the configured mode.
	 */
	public static function init() {
		$mode = get_option( 'wcgd_mode', 'cart' );

		if ( 'prices' === $mode ) {
			add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'adjust_price' ), 99, 2 );
			add_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'adjust_price' ), 99, 2 );
			add_filter( 'woocommerce_product_variation_get_price', array( __CLASS__, 'adjust_price' ), 99, 2 );
			add_filter( 'woocommerce_product_variation_get_sale_price', array( __CLASS__, 'adjust_price' ), 99, 2 );
		} else {
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_cart_discount' ) );
		}
	}

	/**
	 * Cart mode: subtract the group's percentage of the subtotal as a negative fee.
	 *
	 * @param WC_Cart $cart Cart instance.
	 */
	public static function add_cart_discount( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		$percent = wcgd_current_discount_percent();
		if ( $percent <= 0 ) {
			return;
		}
		$discount = wcgd_calc_discount( $cart->get_subtotal(), $percent, wc_get_price_decimals() );
		if ( $discount <= 0 ) {
			return;
		}

		$groups   = wcgd_get_groups();
		$group_id = get_user_meta( get_current_user_id(), 'wcgd_group', true );
		$name     = isset( $groups[ $group_id ] ) ? $groups[ $group_id ]['name'] : __( 'Group discount', 'woo-customer-group-discount' );
		$label    = isset( $groups[ $group_id ]['label'] ) ? $groups[ $group_id ]['label'] : '';

		// Custom label shown verbatim ({percent} token replaced); blank falls back to "Name (15%)".
		$text = ( '' !== $label )
			? str_replace( '{percent}', wc_format_localized_decimal( $percent ), $label )
			: sprintf( '%s (%s%%)', $name, wc_format_localized_decimal( $percent ) );

		// ponytail: negative fee on ex-tax subtotal; switch to per-line price adjustment if precise tax-on-discount is needed.
		$cart->add_fee( $text, -$discount, false );
	}

	/**
	 * Prices mode: reduce a product's price by the current customer's group percentage.
	 *
	 * @param string|float $price   Current price.
	 * @param WC_Product   $product Product (unused).
	 * @return string|float
	 */
	public static function adjust_price( $price, $product ) {
		if ( '' === $price || null === $price ) {
			return $price;
		}
		$percent = wcgd_current_discount_percent();
		if ( $percent <= 0 ) {
			return $price;
		}
		$discount = wcgd_calc_discount( $price, $percent, wc_get_price_decimals() );
		return max( 0, (float) $price - $discount );
	}
}

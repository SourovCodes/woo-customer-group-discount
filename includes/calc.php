<?php
/**
 * Pure discount math — no WordPress dependencies, so the test can include it directly.
 *
 * @package WooCustomerGroupDiscount
 */

if ( ! function_exists( 'wcgd_calc_discount' ) ) {
	/**
	 * Discount amount for a base amount and percentage.
	 *
	 * @param float $amount   Base amount (cart subtotal or product price).
	 * @param float $percent  Discount percent; clamped to 0–100.
	 * @param int   $decimals Decimal places to round to.
	 * @return float Discount amount, always >= 0.
	 */
	function wcgd_calc_discount( $amount, $percent, $decimals = 2 ) {
		$amount  = (float) $amount;
		$percent = max( 0.0, min( 100.0, (float) $percent ) );
		return round( $amount * $percent / 100, $decimals );
	}
}

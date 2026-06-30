<?php
/**
 * Plugin Name: Woo Customer Group Discount
 * Description: Define customer groups, give each a percentage discount, and assign customers from one admin panel.
 * Version:     1.0.1
 * Author:      sourov
 * Requires Plugins: woocommerce
 * License:     GPL-2.0-or-later
 * Text Domain: woo-customer-group-discount
 *
 * @package WooCustomerGroupDiscount
 */

defined( 'ABSPATH' ) || exit;

define( 'WCGD_FILE', __FILE__ );
define( 'WCGD_DIR', plugin_dir_path( __FILE__ ) );

require_once WCGD_DIR . 'includes/calc.php';
require_once WCGD_DIR . 'includes/discount.php';
require_once WCGD_DIR . 'includes/admin-page.php';

/**
 * Declare HPOS compatibility — we never touch orders, so just declare compatible.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCGD_FILE, true );
		}
	}
);

/**
 * Bail with an admin notice if WooCommerce is missing; otherwise wire everything up.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>' .
						esc_html__( 'Woo Customer Group Discount requires WooCommerce to be installed and active.', 'woo-customer-group-discount' ) .
						'</p></div>';
				}
			);
			return;
		}

		WCGD_Discount::init();
		WCGD_Admin::init();
	}
);

/**
 * All groups: [ id => [ 'name' => string, 'percent' => float ] ].
 *
 * @return array
 */
function wcgd_get_groups() {
	$groups = get_option( 'wcgd_groups', array() );
	return is_array( $groups ) ? $groups : array();
}

/**
 * Discount percent for the current customer (0 if none / not logged in).
 *
 * @return float
 */
function wcgd_current_discount_percent() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return 0.0;
	}
	$group_id = get_user_meta( $user_id, 'wcgd_group', true );
	if ( '' === $group_id ) {
		return 0.0;
	}
	$groups = wcgd_get_groups();
	return isset( $groups[ $group_id ] ) ? (float) $groups[ $group_id ]['percent'] : 0.0;
}

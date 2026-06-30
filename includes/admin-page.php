<?php
/**
 * The "Customer Groups" admin panel: discount-mode setting, group CRUD, and member assignment.
 *
 * @package WooCustomerGroupDiscount
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin panel.
 */
class WCGD_Admin {

	const PAGE = 'wcgd';
	const CAP  = 'manage_woocommerce';

	/**
	 * Register menu, assets and POST handlers.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_post_wcgd_save_mode', array( __CLASS__, 'save_mode' ) );
		add_action( 'admin_post_wcgd_save_group', array( __CLASS__, 'save_group' ) );
		add_action( 'admin_post_wcgd_delete_group', array( __CLASS__, 'delete_group' ) );
	}

	/**
	 * Submenu under WooCommerce.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Customer Groups', 'woo-customer-group-discount' ),
			__( 'Customer Groups', 'woo-customer-group-discount' ),
			self::CAP,
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Load Woo's enhanced-select (gives us the AJAX customer search for free) on our page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( $hook ) {
		if ( false === strpos( (string) $hook, self::PAGE ) ) {
			return;
		}
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
	}

	/**
	 * Members of a group as WP_User objects.
	 *
	 * @param int $group_id Group id.
	 * @return WP_User[]
	 */
	private static function members( $group_id ) {
		return get_users(
			array(
				'meta_key'   => 'wcgd_group',
				'meta_value' => (string) $group_id,
				'orderby'    => 'display_name',
			)
		);
	}

	/**
	 * Route between list and edit views.
	 */
	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to manage customer groups.', 'woo-customer-group-discount' ) );
		}

		echo '<div class="wrap">';

		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'woo-customer-group-discount' ) . '</p></div>';
		}

		if ( isset( $_GET['edit'] ) ) {
			self::render_form( absint( $_GET['edit'] ) );
		} else {
			self::render_list();
		}

		echo '</div>';
	}

	/**
	 * Mode selector + groups table + "add new" link.
	 */
	private static function render_list() {
		$mode       = get_option( 'wcgd_mode', 'cart' );
		$groups     = wcgd_get_groups();
		$post_url   = esc_url( admin_url( 'admin-post.php' ) );
		$add_url    = esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&edit=0' ) );

		echo '<h1>' . esc_html__( 'Customer Groups', 'woo-customer-group-discount' ) . '</h1>';

		// Discount mode.
		echo '<h2>' . esc_html__( 'Discount mode', 'woo-customer-group-discount' ) . '</h2>';
		echo '<form method="post" action="' . $post_url . '">';
		wp_nonce_field( 'wcgd_save_mode' );
		echo '<input type="hidden" name="action" value="wcgd_save_mode" />';
		echo '<p><label><input type="radio" name="mode" value="cart" ' . checked( 'cart', $mode, false ) . '> ' .
			esc_html__( 'Cart discount line — show the saving at cart/checkout (default).', 'woo-customer-group-discount' ) . '</label></p>';
		echo '<p><label><input type="radio" name="mode" value="prices" ' . checked( 'prices', $mode, false ) . '> ' .
			esc_html__( 'Adjusted prices — reduce product prices everywhere (wholesale-style).', 'woo-customer-group-discount' ) . '</label></p>';
		submit_button( __( 'Save mode', 'woo-customer-group-discount' ) );
		echo '</form>';

		// Groups table.
		echo '<h2>' . esc_html__( 'Groups', 'woo-customer-group-discount' ) . ' <a href="' . $add_url . '" class="page-title-action">' . esc_html__( 'Add new', 'woo-customer-group-discount' ) . '</a></h2>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Name', 'woo-customer-group-discount' ) . '</th>';
		echo '<th>' . esc_html__( 'Discount %', 'woo-customer-group-discount' ) . '</th>';
		echo '<th>' . esc_html__( 'Members', 'woo-customer-group-discount' ) . '</th>';
		echo '<th></th></tr></thead><tbody>';

		if ( ! $groups ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No groups yet.', 'woo-customer-group-discount' ) . '</td></tr>';
		}

		foreach ( $groups as $id => $group ) {
			$edit_url   = esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&edit=' . $id ) );
			$delete_url = esc_url(
				wp_nonce_url(
					admin_url( 'admin-post.php?action=wcgd_delete_group&id=' . $id ),
					'wcgd_delete_group_' . $id
				)
			);
			$count = count( self::members( $id ) );

			echo '<tr>';
			echo '<td><a href="' . $edit_url . '"><strong>' . esc_html( $group['name'] ) . '</strong></a></td>';
			echo '<td>' . esc_html( wc_format_localized_decimal( $group['percent'] ) ) . '%</td>';
			echo '<td>' . esc_html( $count ) . '</td>';
			echo '<td><a href="' . $edit_url . '">' . esc_html__( 'Edit', 'woo-customer-group-discount' ) . '</a> | ';
			echo '<a href="' . $delete_url . '" onclick="return confirm(\'' . esc_js( __( 'Delete this group?', 'woo-customer-group-discount' ) ) . '\')">' . esc_html__( 'Delete', 'woo-customer-group-discount' ) . '</a></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Add / edit form. $id === 0 means new.
	 *
	 * @param int $id Group id.
	 */
	private static function render_form( $id ) {
		$groups   = wcgd_get_groups();
		$is_new   = ( 0 === $id || ! isset( $groups[ $id ] ) );
		$group    = $is_new ? array( 'name' => '', 'percent' => '', 'label' => '' ) : array_merge( array( 'label' => '' ), $groups[ $id ] );
		$members  = $is_new ? array() : self::members( $id );
		$post_url = esc_url( admin_url( 'admin-post.php' ) );
		$back_url = esc_url( admin_url( 'admin.php?page=' . self::PAGE ) );

		echo '<h1>' . ( $is_new ? esc_html__( 'Add group', 'woo-customer-group-discount' ) : esc_html__( 'Edit group', 'woo-customer-group-discount' ) ) . '</h1>';
		echo '<form method="post" action="' . $post_url . '">';
		wp_nonce_field( 'wcgd_save_group' );
		echo '<input type="hidden" name="action" value="wcgd_save_group" />';
		echo '<input type="hidden" name="id" value="' . esc_attr( $is_new ? 0 : $id ) . '" />';

		echo '<table class="form-table"><tbody>';

		echo '<tr><th><label for="wcgd-name">' . esc_html__( 'Name', 'woo-customer-group-discount' ) . '</label></th>';
		echo '<td><input name="name" id="wcgd-name" type="text" class="regular-text" required value="' . esc_attr( $group['name'] ) . '" /></td></tr>';

		echo '<tr><th><label for="wcgd-percent">' . esc_html__( 'Discount %', 'woo-customer-group-discount' ) . '</label></th>';
		echo '<td><input name="percent" id="wcgd-percent" type="number" min="0" max="100" step="0.01" required value="' . esc_attr( $group['percent'] ) . '" /></td></tr>';

		echo '<tr><th><label for="wcgd-label">' . esc_html__( 'Cart discount label', 'woo-customer-group-discount' ) . '</label></th>';
		echo '<td><input name="label" id="wcgd-label" type="text" class="regular-text" value="' . esc_attr( $group['label'] ) . '" />';
		echo '<p class="description">' . esc_html__( 'Text shown on the discount line at cart/checkout (cart mode). Use {percent} for the percentage. Leave blank to use the group name, e.g. "Gold (15%)".', 'woo-customer-group-discount' ) . '</p></td></tr>';

		echo '<tr><th><label for="wcgd-members">' . esc_html__( 'Members', 'woo-customer-group-discount' ) . '</label></th><td>';
		echo '<select id="wcgd-members" class="wc-customer-search" multiple="multiple" style="width:50%" name="members[]" data-placeholder="' . esc_attr__( 'Search for a customer…', 'woo-customer-group-discount' ) . '" data-action="woocommerce_json_search_customers">';
		foreach ( $members as $m ) {
			echo '<option value="' . esc_attr( $m->ID ) . '" selected="selected">' .
				esc_html( sprintf( '%s (#%d – %s)', $m->display_name, $m->ID, $m->user_email ) ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'A customer can be in one group; adding them here moves them out of any other group.', 'woo-customer-group-discount' ) . '</p>';
		echo '</td></tr>';

		echo '</tbody></table>';

		submit_button( $is_new ? __( 'Add group', 'woo-customer-group-discount' ) : __( 'Save changes', 'woo-customer-group-discount' ) );
		echo ' <a href="' . $back_url . '" class="button">' . esc_html__( 'Cancel', 'woo-customer-group-discount' ) . '</a>';
		echo '</form>';
	}

	/**
	 * Redirect back to the panel.
	 */
	private static function redirect_back() {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&updated=1' ) );
		exit;
	}

	/**
	 * Save the global discount mode.
	 */
	public static function save_mode() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'wcgd_save_mode' );

		$mode = ( isset( $_POST['mode'] ) && 'prices' === $_POST['mode'] ) ? 'prices' : 'cart';
		update_option( 'wcgd_mode', $mode );
		self::redirect_back();
	}

	/**
	 * Create or update a group and reconcile its members.
	 */
	public static function save_group() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( -1 );
		}
		check_admin_referer( 'wcgd_save_group' );

		$groups  = wcgd_get_groups();
		$id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$percent = isset( $_POST['percent'] ) ? max( 0.0, min( 100.0, (float) $_POST['percent'] ) ) : 0.0;
		$label   = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';

		if ( '' === $name ) {
			self::redirect_back();
		}

		if ( 0 === $id || ! isset( $groups[ $id ] ) ) {
			$id = $groups ? max( array_map( 'intval', array_keys( $groups ) ) ) + 1 : 1;
		}

		$groups[ $id ] = array(
			'name'    => $name,
			'percent' => $percent,
			'label'   => $label,
		);
		update_option( 'wcgd_groups', $groups );

		// Reconcile membership: assign selected, unassign anyone who was in this group but isn't selected.
		$selected = isset( $_POST['members'] ) ? array_map( 'absint', (array) $_POST['members'] ) : array();
		$previous = wp_list_pluck( self::members( $id ), 'ID' );

		foreach ( $selected as $uid ) {
			update_user_meta( $uid, 'wcgd_group', (string) $id );
		}
		foreach ( array_diff( $previous, $selected ) as $uid ) {
			delete_user_meta( $uid, 'wcgd_group' );
		}

		self::redirect_back();
	}

	/**
	 * Delete a group and clear its members' assignment.
	 */
	public static function delete_group() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( -1 );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'wcgd_delete_group_' . $id );

		foreach ( self::members( $id ) as $m ) {
			delete_user_meta( $m->ID, 'wcgd_group' );
		}

		$groups = wcgd_get_groups();
		unset( $groups[ $id ] );
		update_option( 'wcgd_groups', $groups );

		self::redirect_back();
	}
}

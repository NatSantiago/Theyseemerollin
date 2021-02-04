<?php
/**
 * WooCommerce PDF Product Vouchers
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce PDF Product Vouchers to newer
 * versions in the future. If you wish to customize WooCommerce PDF Product Vouchers for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-pdf-product-vouchers/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2020, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_0 as Framework;

/**
 * PDF Product Vouchers Post Types class
 *
 * This class is responsible for registering the custom post types & taxonomy
 * required for PDF Product Vouchers.
 *
 * In 3.0.0 renamed from \WC_PDF_Product_Vouchers_Taxonomy to \WC_PDF_Product_Vouchers_Post_Types
 *
 * @since 1.2.0
 */
class WC_PDF_Product_Vouchers_Post_Types {


	/**
	 * Initializes and registers the PDF Vouchers post types
	 *
	 * In 3.0.0 renamed from __construct to initialize and
	 * changed to static.
	 *
	 * @since 1.2.0
	 */
	public static function initialize() {

		self::init_post_types();
		self::init_user_roles();
		self::init_post_statuses();

		add_filter( 'post_updated_messages',      array( __CLASS__, 'updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( __CLASS__, 'bulk_updated_messages' ), 10, 2 );

		// load the wc_voucher custom post type single template, used to generate a preview voucher from the admin
		add_filter( 'single_template', 'wc_vouchers_locate_voucher_preview_template' );

		add_image_size( 'wc-pdf-product-vouchers-voucher-thumb', \WC_PDF_Product_Vouchers::VOUCHER_IMAGE_THUMB_WIDTH );
	}


	/**
	 * Initializes PDF Product Vouchers user roles
	 *
	 * @since 1.2.0
	 */
	private static function init_user_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}

		// Allow shop managers and admins to manage vouchers and voucher templates
		if ( is_object( $wp_roles ) ) {

			foreach ( array( 'voucher', 'voucher_template' ) as $post_type ) {

				$args = new \stdClass();
				$args->map_meta_cap = true;
				$args->capability_type = $post_type;
				$args->capabilities = array();

				foreach ( get_post_type_capabilities( $args ) as $builtin => $mapped ) {

					$wp_roles->add_cap( 'shop_manager', $mapped );
					$wp_roles->add_cap( 'administrator', $mapped );
				}
			}

			$wp_roles->add_cap( 'shop_manager',  'manage_woocommerce_vouchers' );
			$wp_roles->add_cap( 'administrator', 'manage_woocommerce_vouchers' );

			$wp_roles->add_cap( 'shop_manager',  'manage_woocommerce_voucher_templates' );
			$wp_roles->add_cap( 'administrator', 'manage_woocommerce_voucher_templates' );

			$wp_roles->add_cap( 'shop_manager', 'redeem_woocommerce_vouchers' );
			$wp_roles->add_cap( 'administrator', 'redeem_woocommerce_vouchers' );
		}
	}


	/**
	 * Initializes PDF Product Vouchers custom post types
	 *
	 * In 3.0.0 renamed from init_taxonomy to init_post_types and
	 * changed to static.
	 *
	 * @since 1.2.0
	 */
	private static function init_post_types() {

		if ( current_user_can( 'manage_woocommerce' ) ) {
			$show_in_menu = 'woocommerce';
		} else {
			$show_in_menu = true;
		}

		register_post_type( 'wc_voucher',
			array(
				'labels' => array(
						'name'               => __( 'Vouchers', 'woocommerce-pdf-product-vouchers' ),
						'singular_name'      => __( 'Voucher', 'woocommerce-pdf-product-vouchers' ),
						'menu_name'          => _x( 'Vouchers', 'Admin menu name', 'woocommerce-pdf-product-vouchers' ),
						'add_new'            => __( 'Add Voucher', 'woocommerce-pdf-product-vouchers' ),
						'add_new_item'       => __( 'Add New Voucher', 'woocommerce-pdf-product-vouchers' ),
						'edit'               => __( 'Edit', 'woocommerce-pdf-product-vouchers' ),
						'edit_item'          => __( 'Edit Voucher', 'woocommerce-pdf-product-vouchers' ),
						'new_item'           => __( 'New Voucher', 'woocommerce-pdf-product-vouchers' ),
						'view'               => __( 'View Vouchers', 'woocommerce-pdf-product-vouchers' ),
						'view_item'          => __( 'View Voucher', 'woocommerce-pdf-product-vouchers' ),
						'search_items'       => __( 'Search Vouchers', 'woocommerce-pdf-product-vouchers' ),
						'not_found'          => __( 'No Vouchers found', 'woocommerce-pdf-product-vouchers' ),
						'not_found_in_trash' => __( 'No Vouchers found in trash', 'woocommerce-pdf-product-vouchers' ),
					),
				'description'         => __( 'This is where you can see and redeem all purchased vouchers.', 'woocommerce-pdf-product-vouchers' ),
				'public'              => false,
				'show_ui'             => true,
				'capability_type'     => 'voucher',
				'map_meta_cap'        => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_in_menu'        => $show_in_menu, // will be shown as WooCommerce -> Vouchers
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( null ),
				'show_in_nav_menus'   => false,
			)
		);

		// templates are kept publicly queryable so that it's possible to view a preview, access is restricted
		// via setting them private
		register_post_type( 'wc_voucher_template',
			array(
				'labels' => array(
						'name'               => __( 'Voucher Templates', 'woocommerce-pdf-product-vouchers' ),
						'singular_name'      => __( 'Voucher Template', 'woocommerce-pdf-product-vouchers' ),
						'menu_name'          => _x( 'Vouchers', 'Admin menu name', 'woocommerce-pdf-product-vouchers' ),
						'add_new'            => __( 'Add Voucher Template', 'woocommerce-pdf-product-vouchers' ),
						'add_new_item'       => __( 'Add New Voucher Template', 'woocommerce-pdf-product-vouchers' ),
						'edit'               => __( 'Edit', 'woocommerce-pdf-product-vouchers' ),
						'edit_item'          => __( 'Edit Voucher Template', 'woocommerce-pdf-product-vouchers' ),
						'new_item'           => __( 'New Voucher Template', 'woocommerce-pdf-product-vouchers' ),
						'view'               => __( 'View Voucher Templates', 'woocommerce-pdf-product-vouchers' ),
						'view_item'          => __( 'View Voucher Template', 'woocommerce-pdf-product-vouchers' ),
						'search_items'       => __( 'Search Voucher Templates', 'woocommerce-pdf-product-vouchers' ),
						'not_found'          => __( 'No Voucher Templates found', 'woocommerce-pdf-product-vouchers' ),
						'not_found_in_trash' => __( 'No Voucher Templates found in trash', 'woocommerce-pdf-product-vouchers' ),
					),
				'description'         => __( 'This is where you can add new templates for vouchers that you can attach to products and sell.', 'woocommerce-pdf-product-vouchers' ),
				'public'              => false,
				'show_ui'             => true,
				'capability_type'     => 'voucher_template',
				'map_meta_cap'        => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_in_menu'        => false, // will be shown in a tab under WooCommerce -> Vouchers, see WC_PDF_Product_Vouchers_Admin
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( null ),
				'show_in_nav_menus'   => false,
			)
		);
	}


	/**
	 * Initializes voucher post statuses
	 *
	 * @since 3.0.0
	 */
	private static function init_post_statuses() {

		$statuses = wc_pdf_product_vouchers_get_voucher_statuses();

		foreach ( $statuses as $status => $attrs ) {

			register_post_status( $status, array(
				'label'                     => $attrs['label'],
				'private'                   => true,
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => $attrs['label_count'],
			) );
		}
	}


	/**
	 * Customizes updated messages for voucher and voucher template post types
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 * @param array $messages original messages
	 * @return array $messages modified messages
	 */
	public static function updated_messages( $messages ) {

		$messages['wc_voucher'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Voucher updated.', 'woocommerce-pdf-product-vouchers' ),
			2  => __( 'Custom field updated.', 'woocommerce-pdf-product-vouchers' ),
			3  => __( 'Custom field deleted.', 'woocommerce-pdf-product-vouchers' ),
			4  => __( 'Voucher updated.', 'woocommerce-pdf-product-vouchers' ),
			5  => '', // Unused for vouchers, original: Post restored to revision from %s
			6  => __( 'Voucher saved.', 'woocommerce-pdf-product-vouchers' ), // Original: Post published
			7  => __( 'Voucher saved.', 'woocommerce-pdf-product-vouchers' ),
			8  => '', // Unused for vouchers, original: Post submitted
			9  => '', // Unused for vouchers, original: Post scheduled for: <strong>%1$s</strong>
			10 => __( 'Voucher draft updated.', 'woocommerce-pdf-product-vouchers' ),
			11 => __( 'Voucher updated and email sent.', 'woocommerce-pdf-product-vouchers' ),
			12 => __( 'Voucher updated and PDF generated.', 'woocommerce-pdf-product-vouchers' ),
		);

		$messages['wc_voucher_template'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Voucher Template updated.', 'woocommerce-pdf-product-vouchers' ),
			2  => __( 'Custom field updated.', 'woocommerce-pdf-product-vouchers' ),
			3  => __( 'Custom field deleted.', 'woocommerce-pdf-product-vouchers' ),
			4  => __( 'Voucher Template updated.', 'woocommerce-pdf-product-vouchers' ),
			5  => '', // Unused for voucher templates, original: Post restored to revision from %s
			6  => __( 'Voucher Template saved.', 'woocommerce-pdf-product-vouchers' ), // Original: Post published
			7  => __( 'Voucher Template saved.', 'woocommerce-pdf-product-vouchers' ),
			8  => '', // Unused for voucher templates, original: Post submitted
			9  => '', // Unused for voucher templates, original: Post scheduled for: <strong>%1$s</strong>
			10 => __( 'Voucher Template draft updated.', 'woocommerce-pdf-product-vouchers' ),
		);

		return $messages;
	}


	/**
	 * Customizes updated messages for voucher and voucher template post types
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 * @param array $messages original messages
	 * @param array $bulk_counts associative array of bulk counts
	 * @return array $messages modified messages
	 */
	public static function bulk_updated_messages( $messages, $bulk_counts ) {

		$messages['wc_voucher'] = array(
			'updated'   => _n( '%s voucher updated.', '%s vouchers updated.', $bulk_counts['updated'], 'woocommerce-pdf-product-vouchers' ),
			'locked'    => _n( '%s voucher not updated, somebody is editing it.', '%s vouchers not updated, somebody is editing them.', $bulk_counts['locked'], 'woocommerce-pdf-product-vouchers' ),
			'deleted'   => _n( '%s voucher permanently deleted.', '%s vouchers permanently deleted.', $bulk_counts['deleted'], 'woocommerce-pdf-product-vouchers' ),
			'trashed'   => _n( '%s voucher moved to the Trash.', '%s vouchers moved to the Trash.', $bulk_counts['trashed'], 'woocommerce-pdf-product-vouchers' ),
			'untrashed' => _n( '%s voucher restored from the Trash.', '%s vouchers restored from the Trash.', $bulk_counts['untrashed'], 'woocommerce-pdf-product-vouchers' ),
		);

		$messages['wc_voucher_template'] = array(
			'updated'   => _n( '%s voucher template updated.', '%s voucher templates updated.', $bulk_counts['updated'], 'woocommerce-pdf-product-vouchers' ),
			'locked'    => _n( '%s voucher template not updated, somebody is editing it.', '%s voucher templates not updated, somebody is editing them.', $bulk_counts['locked'], 'woocommerce-pdf-product-vouchers' ),
			'deleted'   => _n( '%s voucher template permanently deleted.', '%s voucher templates permanently deleted.', $bulk_counts['deleted'], 'woocommerce-pdf-product-vouchers' ),
			'trashed'   => _n( '%s voucher template moved to the Trash.', '%s voucher templates moved to the Trash.', $bulk_counts['trashed'], 'woocommerce-pdf-product-vouchers' ),
			'untrashed' => _n( '%s voucher template restored from the Trash.', '%s voucher templates restored from the Trash.', $bulk_counts['untrashed'], 'woocommerce-pdf-product-vouchers' ),
		);

		return $messages;
	}


}

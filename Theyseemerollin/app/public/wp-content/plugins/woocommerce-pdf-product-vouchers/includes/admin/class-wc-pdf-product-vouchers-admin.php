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
 * PDF Product Vouchers Voucher Admin
 *
 * @since 1.2.0
 */
class WC_PDF_Product_Vouchers_Admin {


	/** @var array tab URLs / titles */
	protected $tabs = array();

	/** @var string[] screen IDs for the Redeem Voucher admin page */
	private $redeem_voucher_screen_ids = [
		'admin_page_wc-pdf-product-vouchers-redeem-voucher',
		'toplevel_page_wc-pdf-product-vouchers-redeem-voucher',
	];

	/** @var \WC_PDF_Product_Vouchers parent plugin */
	private $plugin;

	/** @var \WC_PDF_Product_Vouchers_Admin_Orders the orders admin handler */
	private $admin_orders;

	/** @var \WC_PDF_Product_Vouchers_Admin_Products the products admin handler */
	private $admin_products;

	/** @var \WC_PDF_Product_Vouchers_Admin_Voucher_Templates_List admin voucher templates list */
	private $voucher_templates_list;

	/** @var \WC_PDF_Product_Vouchers_Admin_Voucher_Templates admin voucher templates handler */
	private $voucher_templates;

	/** @var \WC_PDF_Product_Vouchers_Admin_Vouchers_List admin vouchers list */
	private $vouchers_list;

	/** @var \WC_PDF_Product_Vouchers_Admin_Vouchers admin vouchers */
	private $vouchers;

	/** @var \stdClass Container of meta box classes instances */
	protected $meta_boxes;

	/** @var \WC_PDF_Product_Vouchers_Admin_Barcode_Redeem_Vouchers */
	private $voucher_redeem;


	/**
	 * Initializes the voucher admin
	 *
	 * @since 1.2.0
	 * @param \WC_PDF_Product_Vouchers $plugin the parent plugin
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		// tabs being shown at the top of Vouchers admin
		$this->tabs = $this->get_tabs();

		$this->voucher_templates_list = $this->plugin->load_class( '/includes/admin/class-wc-pdf-product-vouchers-admin-voucher-templates-list.php', 'WC_PDF_Product_Vouchers_Admin_Voucher_Templates_List' );
		$this->voucher_templates      = $this->plugin->load_class( '/includes/admin/class-wc-pdf-product-vouchers-admin-voucher-templates.php', 'WC_PDF_Product_Vouchers_Admin_Voucher_Templates' );
		$this->vouchers_list          = $this->plugin->load_class( '/includes/admin/class-wc-pdf-product-vouchers-admin-vouchers-list.php', 'WC_PDF_Product_Vouchers_Admin_Vouchers_List' );
		$this->vouchers               = $this->plugin->load_class( '/includes/admin/class-wc-pdf-product-vouchers-admin-vouchers.php', 'WC_PDF_Product_Vouchers_Admin_Vouchers' );
		$this->voucher_redeem         = $this->plugin->load_class( '/includes/admin/class-wc-pdf-product-vouchers-admin-barcode-redeem-voucher.php', 'WC_PDF_Product_Vouchers_Admin_Barcode_Redeem_Vouchers' );

		add_action( 'admin_head',     array( $this, 'menu_highlight' ) );
		add_action( 'admin_init',     array( $this, 'init' ) );
		add_action( 'current_screen', array( $this, 'load_meta_boxes' ) );

		add_filter( 'woocommerce_screen_ids', array( $this, 'load_wc_scripts' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_scripts' ) );

		// set current tab for Vouchers admin pages
		add_filter( 'wc_pdf_product_vouchers_admin_current_tab', array( $this, 'set_current_tab' ) );

		// render Vouchers admin tabs for pages with custom post types
		add_action( 'all_admin_notices', array( $this, 'render_tabs' ), 5 );
		add_action( 'all_admin_notices', array( $this, 'show_messages' ) );
	}


	/**
	 * Initializes the admin, adding actions to properly display and handle
	 * the Voucher custom post type add/edit page
	 *
	 * @since 1.2.0
	 */
	public function init() {
		$this->admin_products = $this->plugin->load_class( '/includes/admin/class-wc-pdf-product-vouchers-admin-products.php', 'WC_PDF_Product_Vouchers_Admin_Products' );
		$this->admin_orders   = $this->plugin->load_class( '/includes/admin/class-wc-pdf-product-vouchers-admin-orders.php', 'WC_PDF_Product_Vouchers_Admin_Orders' );
	}


	/**
	 * Loads meta boxes
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function load_meta_boxes() {
		global $pagenow;

		// bail out if not on a new post / edit post screen
		if ( 'post-new.php' !== $pagenow && 'post.php' !== $pagenow ) {
			return;
		}

		$screen = get_current_screen();
		$meta_box_classes = array();

		$this->meta_boxes = new \stdClass();

		// load voucher meta boxes
		if ( 'wc_voucher' === $screen->id ) {
			$meta_box_classes[] = 'WC_PDF_Product_Vouchers_Meta_Box_Voucher_Data';
			$meta_box_classes[] = 'WC_PDF_Product_Vouchers_Meta_Box_Voucher_Actions';
			$meta_box_classes[] = 'WC_PDF_Product_Vouchers_Meta_Box_Voucher_Preview';
			$meta_box_classes[] = 'WC_PDF_Product_Vouchers_Meta_Box_Voucher_Balance';
			$meta_box_classes[] = 'WC_PDF_Product_Vouchers_Meta_Box_Voucher_Notes';
		} elseif ( 'product' === $screen->id ) {
			$meta_box_classes[] = 'WC_PDF_Product_Vouchers_Meta_Box_Product_Redemption_Vouchers';
		}

		// load and instantiate
		foreach ( $meta_box_classes as $class ) {

			$file_name = 'class-'. strtolower( str_replace( '_', '-', $class ) ) . '.php';
			$file_path = wc_pdf_product_vouchers()->get_plugin_path() . '/includes/admin/meta-boxes/' . $file_name;

			if ( is_readable( $file_path ) ) {

				require_once( $file_path );

				if ( class_exists( $class ) ) {

					$instance_name = strtolower( str_replace( 'WC_PDF_Product_Vouchers_Meta_Box_', '', $class ) );
					$this->meta_boxes->$instance_name = new $class();
				}
			}
		}
	}


	/**
	 * Adds settings/export screen ID to the list of pages for WC to load its JS on
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 * @param array $screen_ids
	 * @return array
	 */
	public function load_wc_scripts( $screen_ids ) {

		$screen_ids[] = 'wc_voucher';
		$screen_ids[] = 'edit-wc_voucher';

		return array_merge( $screen_ids, $this->redeem_voucher_screen_ids );
	}


	/**
	 * Enqueues the vouchers admin scripts
	 *
	 * @since 1.2.0
	 */
	public function enqueue_scripts() {
		global $typenow;

		// Get admin screen id
		$screen = get_current_screen();

		// make sure the woocommerce admin styles are available for both voucher template and voucher pages
		if ( in_array( $screen->id, array( 'wc_voucher_template', 'wc_voucher' ) ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );
		}

		if ( in_array( $screen->id, array( 'wc_voucher', 'edit-wc_voucher' ) ) ) {

			// load the WP Pointers script on some screens
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
		}

		$deps = array( 'jquery', 'jquery-tiptip' );

		if ( 'wc_voucher' === $screen->id ) {

			wp_enqueue_media();

			// note - for some wicked reason, we have to explicitly declare backbone
			// as a dependency here, or backbone will be loaded after the modal script,
			// even though it's declared when the script was first registered ¯\_(ツ)_/¯
			wp_enqueue_script( 'wc-backbone-modal', null, array( 'backbone' ) );

			$deps[] = 'wc-backbone-modal';
			$deps[] = 'jquery-ui-datepicker';
		}

		if ( in_array( $screen->id, array_merge( [ 'product', 'shop_order', 'wc_voucher', 'edit-wc_voucher' ], $this->redeem_voucher_screen_ids ), true ) ) {

			if ( ! wp_script_is( 'accounting', 'enqueued' ) ) {

				wp_localize_script( 'accounting', 'accounting_params', array(
					'mon_decimal_point' => wc_get_price_decimal_separator(),
				) );

				$deps[] = 'accounting';
			}

			wp_enqueue_script( 'woocommerce_vouchers_admin', $this->plugin->get_plugin_url() . '/assets/js/admin/wc-pdf-product-vouchers.min.js', $deps );

			wp_localize_script( 'woocommerce_vouchers_admin', 'wc_pdf_product_vouchers_admin', array(
				'ajax_url'                     => admin_url('admin-ajax.php'),
				'new_voucher_url'              => admin_url( 'post-new.php?post_type=wc_voucher' ),
				'redeem_voucher_url'           => admin_url( 'admin.php?page=wc-pdf-product-vouchers-redeem-voucher' ),
				'add_voucher_note_nonce'       => wp_create_nonce( 'add-voucher-note' ),
				'delete_voucher_note_nonce'    => wp_create_nonce( 'delete-voucher-note' ),
				'get_product_details_nonce'    => wp_create_nonce( 'get-product-details' ),
				'get_voucher_preview_nonce'    => wp_create_nonce( 'get-voucher-preview' ),
				'get_customer_details_nonce'   => wp_create_nonce( 'get-customer-details' ),
				'update_voucher_product_nonce' => wp_create_nonce( 'update-voucher-product' ),
				'voucher_balance_nonce'        => wp_create_nonce( 'voucher-balance' ),
				'barcode_redeem_voucher_nonce' => wp_create_nonce( 'barcode-redeem-voucher' ),
				'tax_display_shop'             => get_option( 'woocommerce_tax_display_shop' ),
				'is_edit_voucher_page'         => ( 'edit-wc_voucher' === $screen->id ),
				'i18n'                         => array(
					'guest'                                   => __( 'Guest', 'woocommerce-pdf-product-vouchers' ),
					'product'                                 => __( 'Product', 'woocommerce-pdf-product-vouchers' ),
					'purchaser'                               => __( 'Purchaser', 'woocommerce-pdf-product-vouchers' ),
					'add_voucher'                             => __( 'Add Voucher', 'woocommerce-pdf-product-vouchers' ),
					'redeem_voucher'                          => __( 'Redeem Voucher', 'woocommerce-pdf-product-vouchers' ),
					'confirm_calculate_taxes'                 => __( "Are you sure you want to calculate taxes? This will calculate the product taxes based on the customer's country (or the store base country) and update the voucher value. This action cannot be undone.", 'woocommerce-pdf-product-vouchers' ),
					'confirm_delete_redemption'               => __( 'Are you sure you want to delete this redemption? This action cannot be undone.', 'woocommerce-pdf-product-vouchers' ),
					'confirm_void_voucher'                    => __( 'Are you sure you want to void the remaining value for this voucher?', 'woocommerce-pdf-product-vouchers' ),
					'confirm_restore_voucher'                 => __( 'Are you sure you want restore the voided balance and re-activate the voucher?', 'woocommerce-pdf-product-vouchers' ),
					'confirm_load_customer_details'           => __( 'Load the customer\'s billing details? This will remove any currently entered purchaser information.', 'woocommerce-pdf-product-vouchers' ),
					'confirm_copy_purchaser_details'          => __( 'Copy purchaser details to recipient details? This will remove any currently entered recipient information.', 'woocommerce-pdf-product-vouchers' ),
					'no_customer_selected'                    => __( 'No customer selected.', 'woocommerce-pdf-product-vouchers' ),
					'select_product_and_purchaser'            => __( 'Select Product and Purchaser', 'woocommerce-pdf-product-vouchers' ),
					'search_for_product'                      => __( 'Search for a product&hellip;', 'woocommerce-pdf-product-vouchers' ),
					'amount_label'                            => __( 'Amount *', 'woocommerce-pdf-product-vouchers' ),
					'notes_label'                             => __( 'Notes (optional)', 'woocommerce-pdf-product-vouchers' ),
					'redeem'                                  => __( 'Redeem', 'woocommerce-pdf-product-vouchers' ),
					'void'                                    => __( 'Void', 'woocommerce-pdf-product-vouchers' ),
					'cancel'                                  => __( 'Cancel', 'woocommerce-pdf-product-vouchers' ),
					'void_remaining_value'                    => __( 'Void Remaining Value', 'woocommerce-pdf-product-vouchers' ),
					'reason_label'                            => __( 'Reason (optional)', 'woocommerce-pdf-product-vouchers' ),
					'amount_greater_than_zero_error'          => __( 'Please enter in a value greater than 0.', 'woocommerce-pdf-product-vouchers' ),
					'amount_less_or_equal_to_remaining_error' => __( 'Please enter in a value less or equal to the remaining value.', 'woocommerce-pdf-product-vouchers' ),
					'amount_multiple_of_product_price_error'  => __( 'Please enter in a value that is a multiple of the product price.', 'woocommerce-pdf-product-vouchers' ),
					'empty_barcode_error'                     => __( 'Please enter a barcode number.', 'woocommerce-pdf-product-vouchers' ),
					'barcode_requires_https_error'            => __( 'The barcode scanner requires that you are on a secure (HTTPS) url.', 'woocommerce-pdf-product-vouchers' ),
					'barcode_unsupported_browser_error'       => __( 'Sorry, this browser does not support barcode scanning.', 'woocommerce-pdf-product-vouchers' ),
					'barcode_scanner_generic_error'           => __( 'Unable to open the scanner - Either no video device was found, or permission has not been granted to use it.', 'woocommerce-pdf-product-vouchers' ),
				),
			) );

			wp_enqueue_style( 'woocommerce_vouchers_admin_styles', $this->plugin->get_plugin_url() . '/assets/css/admin/wc-pdf-product-vouchers.min.css' );
		}

		if ( in_array( $screen->id, $this->redeem_voucher_screen_ids, true ) ) {
			wp_enqueue_script( 'jsQR', $this->plugin->get_plugin_url() . '/assets/js/admin/jsQR/jsQR.js', array(), '1.1.1' );
			wp_enqueue_script(
				'woocommerce_vouchers_barcode_scan_admin',
				$this->plugin->get_plugin_url() . '/assets/js/admin/wc-pdf-product-vouchers-barcode-scan.min.js',
				array( 'jsQR' )
			);
		}

		// hide MPV coupon type from coupon admin UI
		if ( 'shop_coupon' === $typenow ) {
			wc_enqueue_js('
				jQuery( "#dropdown_shop_coupon_type, #discount_type" ).find( "option[value=multi_purpose_voucher]" ).remove();
			');
		}
	}


	/**
	 * Highlights the correct top level admin menu item for the voucher post type add screen
	 *
	 * @since 1.2.0
	 */
	public function menu_highlight() {

		global $parent_file, $submenu_file, $post_type;

		if ( isset( $post_type ) && 'wc_voucher_template' === $post_type ) {
			$submenu_file = 'edit.php?post_type=wc_voucher';
			$parent_file  = 'woocommerce';
		}
	}



	/**
	 * Returns admin page tabs
	 *
	 * @since 3.0.0
	 * @return array
	 */
	private function get_tabs() {

		if ( ! empty( $this->tabs ) ) {
			return $this->tabs;
		}

		return array(
			'vouchers'  => array(
				'title' => __( 'Vouchers', 'woocommerce-pdf-product-vouchers' ),
				'url'   => admin_url( 'edit.php?post_type=wc_voucher' ),
			),
			'templates' => array(
				'title' => __( 'Voucher Templates', 'woocommerce-pdf-product-vouchers' ),
				'url'   => admin_url( 'edit.php?post_type=wc_voucher_template' ),
			),
		);
	}


	/**
	 * Sets the current tab
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 * @param string $current_tab current tab slug
	 * @return string
	 */
	public function set_current_tab( $current_tab ) {
		global $typenow;

		if ( 'wc_voucher' === $typenow ) {
			$current_tab = 'vouchers';
		} elseif ( 'wc_voucher_template' === $typenow ) {
			$current_tab = 'templates';
		}

		return $current_tab;
	}


	/**
	 * Renders tabs on our custom post types pages
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function render_tabs() {
		global $typenow;

		if ( is_string( $typenow ) && in_array( $typenow, array( 'wc_voucher', 'wc_voucher_template' ), true ) ) :

			?>
			<div class="wrap woocommerce">
				<?php
					/**
					 * Filter the current PDF Product Voucher Admin tab
					 *
					 * @since 3.0.0
					 * @param string $current_tab
					 */
					$current_tab = apply_filters( 'wc_pdf_product_vouchers_admin_current_tab', '' );
				?>
				<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
					<?php foreach ( $this->tabs as $tab_id => $tab ) : ?>
						<?php $class = ( $tab_id === $current_tab ) ? array( 'nav-tab', 'nav-tab-active' ) : array( 'nav-tab' ); ?>
						<?php printf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $tab['url'] ), implode( ' ', array_map( 'sanitize_html_class', $class ) ), esc_html( $tab['title'] ) ); ?>
					<?php endforeach; ?>
				</h2>
			</div>
			<?php

		endif;
	}


	/**
	 * Shows admin messages
	 *
	 * @since 3.0.0
	 */
	public function show_messages() {
		wc_pdf_product_vouchers()->get_message_handler()->show_messages();
	}


}

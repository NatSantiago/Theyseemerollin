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

use SkyVerge\WooCommerce\PDF_Product_Vouchers\Integrations;
use SkyVerge\WooCommerce\PluginFramework\v5_5_0 as Framework;

/**
 * WooCommerce PDF Product Vouchers Main Plugin Class
 *
 * @since 1.0
 */
class WC_PDF_Product_Vouchers extends Framework\SV_WC_Plugin {


	/** version number */
	const VERSION = '3.7.7';

	/** @var \WC_PDF_Product_Vouchers single instance of this plugin */
	protected static $instance;

	/** string the plugin id */
	const PLUGIN_ID = 'pdf_product_vouchers';

	/** Voucher image thumbnail width */
	const VOUCHER_IMAGE_THUMB_WIDTH = 100;

	/** int Recommended memory in bytes */
	const RECOMMENDED_MEMORY = 536870912; // 512MB

	/** @var \WC_PDF_Product_Vouchers_AJAX ajax class */
	private $ajax;

	/** @var \WC_PDF_Product_Vouchers_Product product class */
	private $product;

	/** @var \WC_PDF_Product_Vouchers_Cart cart class */
	private $cart;

	/** @var \WC_PDF_Product_Vouchers_Handler voucher handler/helper */
	private $voucher_handler;

	/** @var \WC_PDF_Product_Vouchers_Frontend My Account handler/helper */
	private $frontend;

	/** @var \WC_PDF_Product_Vouchers_Admin PDF product vouchers admin */
	private $admin;

	/** @var \WC_PDF_Product_Vouchers_Customizer admin customizer handler */
	private $customizer;

	/** @var \WC_PDF_Product_Vouchers_Query instance */
	protected $query;

	/** @var \WC_PDF_Product_Vouchers_Background_Generator instance */
	protected $background_generator;

	/** @var \WC_PDF_Product_Vouchers_Cron instance */
	protected $cron;

	/** @var \WC_PDF_Product_Vouchers_Download_Handler instance */
	protected $download_handler;

	/** @var \WC_PDF_Product_Vouchers_Redemption_Handler instance */
	protected $redemption_handler;


	/**
	 * Sets up the main plugin class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain'        => 'woocommerce-pdf-product-vouchers',
				'dependencies'       => array(
					'php_extensions' => array(
						'dom',
						'gd',
						'mbstring',
					),
				),
			)
		);

		// generate voucher pdf, attach to emails, handle downloads
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_classes' ) );

		// make sure template files are searched for in our plugin
		add_filter( 'woocommerce_locate_template',      array( $this, 'locate_template' ), 20, 2 );
		add_filter( 'woocommerce_locate_core_template', array( $this, 'locate_template' ), 20, 2 );

		// GDPR compliance: anonymize vouchers when personal data is removed or access to download is revoked
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_personal_data_eraser' ) );

		// initializes post types a bit later than WooCommerce core ones
		add_action( 'init', array( $this, 'init_post_types' ), 25 );

		$this->add_milestone_hooks();
	}


	/**
	 * Initializes the plugin.
	 *
	 * @internal
	 *
	 * @since 3.6.0
	 */
	public function init_plugin() {

		$this->includes();
	}


	/**
	 * Initializes post types.
	 *
	 * @internal
	 *
	 * @since 3.6.0-dev.2
	 */
	public function init_post_types() {

		\WC_PDF_Product_Vouchers_Post_Types::initialize();
	}


	/**
	 * Loads and initializes the plugin lifecycle handler.
	 *
	 * @since 3.6.0-dev.2
	 */
	protected function init_lifecycle_handler() {

		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-upgrade.php' );

		$this->lifecycle_handler = new \WC_PDF_Product_Vouchers_Upgrade( $this );
	}


	/**
	 * Loads required files and classes.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// Background generator must be loaded all the time, otherwise background jobs simply won't work
		require_once( $this->get_framework_path() . '/utilities/class-sv-wp-async-request.php' );
		require_once( $this->get_framework_path() . '/utilities/class-sv-wp-background-job-handler.php' );

		$this->background_generator = $this->load_class( '/includes/class-wc-pdf-product-vouchers-background-generator.php', 'WC_PDF_Product_Vouchers_Background_Generator' );

		// load post types
		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-post-types.php' );

		// load helper functions
		require_once( $this->get_plugin_path() . '/includes/functions/wc-pdf-product-vouchers-functions.php' );

		if ( is_admin() ) {
			$this->admin_includes();
		}

		require_once( $this->get_plugin_path() . '/includes/abstract-wc-voucher-base.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-voucher.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-voucher-template.php' );

		$this->product            = $this->load_class( '/includes/class-wc-pdf-product-vouchers-product.php', 'WC_PDF_Product_Vouchers_Product' );
		$this->cart               = $this->load_class( '/includes/class-wc-pdf-product-vouchers-cart.php', 'WC_PDF_Product_Vouchers_Cart' );
		$this->voucher_handler    = $this->load_class( '/includes/class-wc-pdf-product-vouchers-handler.php', 'WC_PDF_Product_Vouchers_Handler' );
		$this->query              = $this->load_class( '/includes/class-wc-pdf-product-vouchers-query.php', 'WC_PDF_Product_Vouchers_Query' );
		$this->cron               = $this->load_class( '/includes/class-wc-pdf-product-vouchers-cron.php', 'WC_PDF_Product_Vouchers_Cron' );
		$this->download_handler   = $this->load_class( '/includes/class-wc-pdf-product-vouchers-download-handler.php', 'WC_PDF_Product_Vouchers_Download_Handler' );
		$this->redemption_handler = $this->load_class( '/includes/class-wc-pdf-product-vouchers-redemption-handler.php', 'WC_PDF_Product_Vouchers_Redemption_Handler' );
		$this->frontend           = $this->load_class( '/includes/frontend/class-wc-pdf-product-vouchers-frontend.php', 'WC_PDF_Product_Vouchers_Frontend' );
		$this->customizer         = $this->load_class( '/includes/customizer/class-wc-pdf-product-vouchers-customizer.php', 'WC_PDF_Product_Vouchers_Customizer' );
		$this->integrations       = $this->load_class( '/includes/Integrations.php', Integrations::class );

		require_once( $this->get_plugin_path() . '/includes/interface-wc-pdf-product-vouchers-redeem.php' );
		require_once( $this->get_plugin_path() . '/includes/interface-wc-pdf-product-vouchers-redeem-response.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-redeem-multi.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-redeem-single.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-redeem-response-single.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-redeem-response-multi.php' );

		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-order.php' );

		// AJAX includes
		if ( is_ajax() ) {
			$this->ajax_includes();
		}
	}


	/**
	 * Includes required voucher admin files.
	 *
	 * @since 1.0
	 */
	private function admin_includes() {

		require_once( $this->get_plugin_path() . '/includes/admin/class-wc-pdf-product-vouchers-admin.php' );

		$this->admin = new \WC_PDF_Product_Vouchers_Admin( $this );
	}


	/**
	 * Include required AJAX classes.
	 *
	 * @since 3.0.0
	 */
	private function ajax_includes() {

		$this->ajax = $this->load_class( '/includes/class-wc-pdf-product-vouchers-ajax.php', 'WC_PDF_Product_Vouchers_AJAX' );
	}


	/**
	 * Returns the product class instance.
	 *
	 * @since 2.6.0
	 *
	 * @return \WC_PDF_Product_Vouchers_Product
	 */
	public function get_product_instance() {

		return $this->product;
	}


	/**
	 * Returns the cart class instance.
	 *
	 * @since 2.6.0
	 *
	 * @return \WC_PDF_Product_Vouchers_Cart
	 */
	public function get_cart_instance() {

		return $this->cart;
	}


	/**
	 * Returns the "My Account" handler class instance.
	 *
	 * TODO remove this method by version 3.8.0 {FN 2018-07-04}
	 *
	 * @since 2.6.0
	 * @deprecated since 3.0.0 (the WC_PDF_Product_Vouchers_My_Account class was replaced by WC_PDF_Product_Vouchers_Frontend)
	 *
	 * @return \WC_PDF_Product_Vouchers_Frontend
	 */
	public function get_my_account_instance() {

		_deprecated_function( 'WC_PDF_Product_Vouchers::get_my_account_instance()', '3.0.0', 'wc_pdf_product_vouchers()->get_frontend_instance()' );

		return $this->get_frontend_instance();
	}


	/**
	 * Returns the front end instance.
	 *
	 * @since 3.6.0-dev.2
	 *
	 * @return \WC_PDF_Product_Vouchers_Frontend
	 */
	public function get_frontend_instance() {

		return $this->frontend;
	}


	/**
	 * Returns the voucher handler class instance.
	 *
	 * @since 2.6.0
	 *
	 * @return \WC_PDF_Product_Vouchers_Handler
	 */
	public function get_voucher_handler_instance() {

		return $this->voucher_handler;
	}


	/**
	 * Returns the customizer handler class instance.
	 *
	 * @since 3.0.0
	 *
	 * @return \WC_PDF_Product_Vouchers_Customizer
	 */
	public function get_customizer_instance() {

		return $this->customizer;
	}


	/**
	 * Returns the query handler class instance.
	 *
	 * @since 3.0.0
	 *
	 * @return \WC_PDF_Product_Vouchers_Query
	 */
	public function get_query_instance() {

		return $this->query;
	}


	/**
	 * Returns the cron handler class instance.
	 *
	 * @since 3.0.0
	 *
	 * @return \WC_PDF_Product_Vouchers_Cron
	 */
	public function get_cron_instance() {

		return $this->cron;
	}


	/**
	 * Returns the main admin handler class instance.
	 *
	 * @since 2.6.0
	 *
	 * @return \WC_PDF_Product_Vouchers_Admin
	 */
	public function get_admin_instance() {

		return $this->admin;
	}


	/**
	 * Returns the background generator class instance.
	 *
	 * @since 3.2.0
	 *
	 * @return \WC_PDF_Product_Vouchers_Background_Generator
	 */
	public function get_background_generator_instance() {

		return $this->background_generator;
	}


	/**
	 * Returns the redemption handler class instance.
	 *
	 * @since 3.7.1
	 *
	 * @return \WC_PDF_Product_Vouchers_Redemption_Handler
	 */
	public function get_redemption_handler_instance() {

		return $this->redemption_handler;
	}


	/**
	 * Returns deprecated/removed hooks.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		// hooks removed in 3.0.0
		$deprecated = array(
			'woocommerce_process_wc_voucher_meta' => array(
				'version' => '3.0.0',
				'removed' => true,
			),
			'wc_pdf_product_vouchers_product_name_multi_line' => array(
				'version' => '3.0.0',
				'removed' => true,
			),
			'woocommerce_voucher_number' => array(
				'version'     => '3.0.0',
				'removed'     => true,
				'map'         => true,
				'replacement' => 'wc_pdf_product_vouchers_get_voucher_number',
			),
			'wc_pdf_product_vouchers_get_expiry' => array(
				'version'     => '3.0.0',
				'removed'     => true,
				'map'         => true,
				'replacement' => 'wc_pdf_product_vouchers_get_expiry_days',
			),
			'wc_pdf_product_vouchers_voucher_field_value' => array(
				'version'     => '3.0.0',
				'removed'     => true,
				'replacement' => 'wc_pdf_product_vouchers_get_{$field}',
			),
			'wc_pdf_prdouct_vouchers_voucher_search_fields' => array(
				'version'     => '3.0.0',
				'removed'     => true,
				'replacement' => 'wc_pdf_product_vouchers_voucher_search_fields',
			),
		);

		return $deprecated;
	}


	/**
	 * Adds PDF Product Vouchers email class
	 *
	 * @internal
	 *
	 * @since 1.2.0
	 *
	 * @param array $email_classes associative array
	 * @return array
	 */
	public function add_email_classes( $email_classes ) {

		$email_classes['WC_PDF_Product_Vouchers_Email_Voucher_Purchaser'] = $this->load_class( '/includes/emails/class-wc-pdf-product-vouchers-email-voucher-purchaser.php', 'WC_PDF_Product_Vouchers_Email_Voucher_Purchaser' );
		$email_classes['WC_PDF_Product_Vouchers_Email_Voucher_Recipient'] = $this->load_class( '/includes/emails/class-wc-pdf-product-vouchers-email-voucher-recipient.php', 'WC_PDF_Product_Vouchers_Email_Voucher_Recipient' );

		return $email_classes;
	}


	/**
	 * Locates the WooCommerce template files from our templates directory.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @param string $template already found template
	 * @param string $template_name searchable template name
	 * @return string search result for the template
	 */
	public function locate_template( $template, $template_name ) {

		// only keep looking if no custom theme template was found
		// or if a default WooCommerce template was found
		if ( ! $template || Framework\SV_WC_Helper::str_starts_with( $template, WC()->plugin_path() ) ) {

			// set the path to our templates directory
			$plugin_path = $this->get_plugin_path() . '/templates/';

			// if a template is found, make it so
			if ( is_readable( $plugin_path . $template_name ) ) {
				$template = $plugin_path . $template_name;
			}
		}

		return $template;
	}


	/** Privacy methods ******************************************************/


	/**
	 * Registers a GDPR compliant personal data eraser in WordPress for handling vouchers.
	 *
	 * @internal
	 *
	 * @since 3.4.2
	 *
	 * @param array $erasers list of WordPress personal data erasers
	 * @return array
	 */
	public function register_personal_data_eraser( array $erasers ) {

		$erasers['woocommerce-pdf-product-vouchers'] = array(
			'eraser_friendly_name' => __( 'PDF Product Vouchers', 'woocommerce-pdf-product-vouchers' ),
			'callback'             => array( 'WC_PDF_Product_Vouchers', 'erase_vouchers_personal_data' ),
		);

		return $erasers;
	}


	/**
	 * Erases personal data from a voucher when an erasure request is issued in WordPress.
	 *
	 * @internal
	 *
	 * @since 3.4.2
	 *
	 * @param string $email_address address of the user that issued the erasure request
	 * @return array associative array with erasure response
	 */
	public static function erase_vouchers_personal_data( $email_address ) {

		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		if ( is_string( $email_address ) && Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.4.0' ) ) {

			$user     = get_user_by( 'email', trim( $email_address ) );
			$vouchers = $user instanceof \WP_User ? wc_pdf_product_vouchers_get_customer_available_vouchers( $user->ID ) : null;

			if ( ! empty( $vouchers ) ) {

				/* @see \wc_string_to_bool() */
				$opt_in = array( 'yes', 'true', '1', 1 );
				$erase  = in_array( get_option( 'woocommerce_erasure_request_removes_order_data', 'no' ), $opt_in, true ) || in_array( get_option( 'woocommerce_erasure_request_removes_download_data', 'no' ), $opt_in, true );

				foreach ( $vouchers as $voucher ) {

					if ( $voucher instanceof \WC_Voucher && $voucher->get_customer_id() > 0 ) {

						/**
						 * Filters whether to erase a voucher when an erasure request is issued.
						 *
						 * @since 3.4.2
						 *
						 * @param bool $erase whether to erase the voucher (true if anonymizing orders or removing access to downloads)
						 * @param \WC_Voucher $voucher the voucher being processed for erasure
						 */
						$erase = (bool) apply_filters( 'wc_pdf_product_vouchers_privacy_erase_voucher', $erase, $voucher );

						if ( $erase ) {

							$voucher->set_customer_id( 0 );
							$voucher->delete_purchaser_name();
							$voucher->delete_purchaser_email();
							$voucher->add_note( __( "Personal data removed from voucher upon original purchaser's request.", 'woocommerce-pdf-product-vouchers' ) );

							/* translators: Placeholder: %s - voucher ID */
							$response['messages'][]     = sprintf( esc_html__( 'Removed personal data from voucher "%s".', 'woocommerce-pdf-product-vouchers' ), $voucher->get_id() );
							$response['items_removed']  = true;

						} else {

							/* translators: Placeholder: %s - voucher ID */
							$response['messages'][]     = sprintf( esc_html__( 'Personal data for voucher "%s" has been retained.', 'woocommerce-pdf-product-vouchers' ), $voucher->get_id() );
							$response['items_retained'] = true;
						}
					}
				}
			}
		}

		return $response;
	}


	/** Admin methods ******************************************************/


	/**
	 * Gets the plugin configuration URL.
	 *
	 * @since 1.1.0
	 *
	 * @param string $_ unused
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $_ = null ) {

		// link to the wc_voucher list table
		return admin_url( 'edit.php?post_type=wc_voucher' );
	}


	/**
	 * Returns true if on the Vouchers List Table/Edit screens.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function is_plugin_settings() {

		return isset( $_GET['post_type'] ) && 'wc_voucher' === $_GET['post_type'];
	}


	/**
	 * Adds milestone hooks.
	 *
	 * @since 3.5.0
	 */
	protected function add_milestone_hooks() {

		// first voucher created
		add_action( 'wc_pdf_product_vouchers_voucher_created', function( $_ ) {
			$this->get_lifecycle_handler()->trigger_milestone(
				'voucher-created', lcfirst( __( 'You have created your first voucher!', 'woocommerce-pdf-product-vouchers' ) )
			);
		} );

		// first voucher redeemed
		add_action( 'wc_pdf_product_vouchers_voucher_redeemed', function( $_ ) {
			$this->get_lifecycle_handler()->trigger_milestone(
				'voucher-redeemed', lcfirst( __( 'You have redeemed your first voucher!', 'woocommerce-pdf-product-vouchers' ) )
			);
		} );
	}


	/**
	 * Checks if required PHP extensions are loaded and adds an admin notice for any missing extensions.
	 *
	 * Also plugin settings can be checked as well.
	 *
	 * @internal
	 *
	 * @since 2.1.1
	 */
	public function add_admin_notices() {

		parent::add_admin_notices();

		$this->add_file_permissions_notices();
		$this->add_wp_cron_disabled_notice();

		$screen         = get_current_screen();
		$notice_handler = $this->get_admin_notice_handler();

		// only render on plugins or settings screen
		if ( $screen && ( 'plugins' === $screen->id || $this->is_plugin_settings() ) ) {

			if ( 'yes' === get_option( 'wc_pdf_product_vouchers_upgraded_to_3_0_0' ) ) {

				// display a notice for installations that are upgrading
				$message_id  = 'wc_pdf_product_vouchers_upgrade_install';

				/* translators: Placeholders: %1$s - this plugin name, %2$s - opening HTML <a> anchor tag, %3$s - closing HTML </a> tag */
				$message_content = sprintf( __( 'Hi there! It looks like you have upgraded %1$s from an older version. We have added lots of new features, please %2$scheck out the documentation%3$s for an overview!', 'woocommerce-pdf-product-vouchers' ), $this->get_plugin_name(), '<a target="_blank" href="' . $this->get_documentation_url() . '">', '</a>' );

			} else {

				// display a notice for fresh installs
				$message_id = 'wc_pdf_product_vouchers_fresh_install';

				/* translators: Placeholders: %1$s - the plugin name, %2$s - opening HTML <a> anchor tag, %3$s closing HTML </a> tag */
				$message_content = sprintf( __( 'Thanks for installing %1$s! To get started, please take a minute to %2$sread the documentation%3$s :)', 'woocommerce-pdf-product-vouchers' ), $this->get_plugin_name(), '<a href="' . $this->get_documentation_url()  . '" target="_blank">', '</a>' );
			}

			// add notice
			$notice_handler->add_admin_notice( $message_content, $message_id, [
				'always_show_on_settings' => false,
				'notice_class'            => 'updated',
			] );

			if ( $this->lower_than_recommended_memory() ) {

				$message_id      = 'wc_pdf_product_vouchers_low_memory';
				$message_content = sprintf(
					/* translators: Placeholders: %1$s - recommended minimum memory; %2$s - <a> tag; %3$s - </a> tag */
					__( 'Heads up! It looks like your server might be configured with less than the recommended memory for generating PDFs. Please ask your hosting company to increase the memory limit for PHP to at least %1$s, or switch to a %2$srecommended hosting provider%3$s.', 'woocommerce-pdf-product-vouchers' ),
					size_format( self::RECOMMENDED_MEMORY ),
					'<a href="https://www.skyverge.com/upgrading-php-versions/#recommended-hosts" target="_blank">', '</a>'
				);

				$notice_handler->add_admin_notice( $message_content, $message_id, [ 'notice_class' => 'error' ] );
			}

			$this->add_security_plugin_notice();
			$this->add_bulk_generate_notice();
		}

		if ( $this->is_plugin_settings() && ! $this->get_background_generator_instance()->test_connection() ) {

			$message_id      = 'generate-pdf-loopback-notice';
			$message_content = sprintf(
			/* translators: Placeholders: %1$s - <strong>; %2$s - </strong>; %3$s, %5$s - <a> tags; %4$s - </a> tag */
				__( '%1$sBackground PDF Generation%2$s is currently unavailable because your site does not support background processing. To speed up your checkouts with vouchers by generating PDFs in the background, please ask your hosting company to ensure your server has loopback connections enabled, or switch to a %3$srecommended hosting provider%4$s.', 'woocommerce-pdf-product-vouchers' ),
				'<strong>',
				'</strong>',
				'<a href="https://www.skyverge.com/upgrading-php-versions/#recommended-hosts" target="_blank">',
				'</a>'
			);

			$notice_handler->add_admin_notice( $message_content, $message_id, [ 'notice_class' => 'error' ] );
		}
	}


	/**
	 * Checks if the server has lower than the recommended memory for generating PDFs.
	 *
	 * @since 3.6.0
	 *
	 * @return bool
	 */
	protected function lower_than_recommended_memory() {

		$lower_than_recommended = false;

		if ( function_exists( 'ini_get' ) ) {
			$memory_limit           = ini_get( 'memory_limit' );
			$memory_in_bytes        = Framework\SV_WC_Plugin_Compatibility::convert_hr_to_bytes( $memory_limit );
			$lower_than_recommended = $memory_in_bytes < self::RECOMMENDED_MEMORY;
		}

		return $lower_than_recommended;
	}


	/**
	 * Renders an admin error if there's a directory permission that will prevent voucher files from being written
	 *
	 * @since 2.1.1
	 */
	private function add_file_permissions_notices() {

		/* translators: Placeholders: %1$s - plugin name, %2$s - uploads path, %3$s - <code> tag or empty string, %4$s - </code> tag or empty string */
		$message    = __( '%1$s: non-writable path %3$s%2$s%4$s detected, please fix directory permissions or voucher files may not be able to be generated.', 'woocommerce-pdf-product-vouchers' );
		$message_id = null;
		$upload_dir = wp_upload_dir();

		// check for file permission errors
		if ( ! is_writable( $upload_dir['basedir'] ) ) {
			$message    = sprintf( $message, $this->get_plugin_name(), $upload_dir['basedir'], '', '' );
			$message_id = 'bad-perms-1';
		} elseif ( ! is_writable( self::get_woocommerce_uploads_path() ) ) {
			$message    = sprintf( $message, $this->get_plugin_name(), self::get_woocommerce_uploads_path(), '<code>', '</code>' );
			$message_id = 'bad-perms-2';
		} elseif ( file_exists( self::get_uploads_path() ) && ! is_writable( self::get_uploads_path() ) ) {
			$message    = sprintf( $message, $this->get_plugin_name(), self::get_uploads_path(), '', '' );
			$message_id = 'bad-perms-3';
		}

		if ( $message_id ) {
			$this->get_admin_notice_handler()->add_admin_notice( $message, $message_id );
		}
	}


	/**
	 * Renders an admin notice when a security plugin is detected that may interfere with PDF generation.
	 *
	 * @since 3.6.0
	 */
	private function add_security_plugin_notice() {

		if ( defined( 'WORDFENCE_VERSION' ) || defined( 'SUCURISCAN_VERSION' ) || class_exists( 'ITSEC_Core' ) ) {

			$message = __( 'Hey there! It looks like you may have a security plugin activated. Some security plugins may interfere with automatic PDF Voucher generation. If you are having trouble with this, please consider temporarily disabling the security plugin to see if it fixes things. If so, you may need to add an exception to the security plugin for PDF Product Vouchers to re-enable automatic PDF generation.', 'woocommerce-pdf-product-vouchers' );

			$this->get_admin_notice_handler()->add_admin_notice( $message, 'security-plugin-installed', [
				'always_show_on_settings' => false,
				'notice_class'            => 'notice-warning',
			] );
		}
	}


	/**
	 * Renders admin notices containing the status of bulk voucher generate jobs.
	 *
	 * @since 3.6.0
	 */
	private function add_bulk_generate_notice() {

		$jobs = $this->get_background_generator_instance()->get_jobs( [ 'order'  => 'ASC' ] ) ?: [];

		foreach ( $jobs as $job ) {

			if ( isset( $job->source ) && 'bulk_action' === $job->source ) {

				$message_id = 'pdf_vouchers_bulk_job_' . $job->id;
				$message    = null;
				$params     = [];

				switch ( $job->status ) {

					case 'processing':

						$message = sprintf(
							/* translators: %1$s - Number of voucher being generated, %2$s - Total number of vouchers to generate */
							__( '[Processing] Generating %1$s of %2$s PDFs', 'woocommerce-pdf-product-vouchers' ),
							isset( $job->progress ) ? (int) $job->progress + 1 : 1,
							count( $job->voucher_ids )
						);

						$params = [ 'notice_class' => 'notice-info' ];
					break;

					case 'queued':

						$cancel_link = esc_url( add_query_arg( [
							'action'    => 'cancel_bulk_voucher_generate',
							'job_id'    => $job->id,
							'security'  => wp_create_nonce( 'cancel_bulk_voucher_generate_' . $job->id ),
						], admin_url( 'admin.php' ) ) );

						$message = sprintf(
							/* translators: %1$d - number of voucher IDs, %2$s - PDF singular/plural wording, %3$s - <a> tag, %4$s - </a> tag */
							__( '[Queued] Generate %1$d %2$s - %3$sCancel%4$s  ', 'woocommerce-pdf-product-vouchers' ),
							count( $job->voucher_ids ),
							_n( 'PDF', 'PDFs', count( $job->voucher_ids ), 'woocommerce-pdf-product-vouchers' ),
							"<a href='$cancel_link'>",
							'</a>'
						);

						$params = [ 'notice_class' => 'notice-warning' ];
					break;

					case 'completed':

						$message = sprintf(
							/* translators: %1$d - Number of vouchers generated, %2$s - singular or plural of 'PDFs' */
							__( 'Successfully generated %1$d %2$s', 'woocommerce-pdf-product-vouchers' ),
							count( $job->voucher_ids ),
							_n( 'PDF', 'PDFs', count( $job->voucher_ids ), 'woocommerce-pdf-product-vouchers' )
						);

						$params = [
							'always_show_on_settings' => false,
							'notice_class'            => 'notice-success'
						];
					break;

					case 'failed':

						$message = sprintf(
							/* translators: %1$d - Number of vouchers generated, %2$s - singular or plural of 'PDFs', %3$s - Error message */
							__( 'Failed while generating %1$d %2$s - %3$s', 'woocommerce-pdf-product-vouchers' ),
							count( $job->voucher_ids ),
							_n( 'PDF', 'PDFs', count( $job->voucher_ids ), 'woocommerce-pdf-product-vouchers' ),
							Framework\SV_WC_Helper::str_truncate( $job->failure_reason, 120 )
						);

						$params = [
							'always_show_on_settings' => false,
							'notice_class'            => 'error'
						];
					break;
				}

				if ( null !== $message ) {
					$this->get_admin_notice_handler()->add_admin_notice( $message, $message_id, $params );
				}
			}
		}
	}


	/**
	 * Adds a notice if WP Cron is disabled, preventing vouchers from being generated.
	 *
	 * @since 3.6.0
	 */
	private function add_wp_cron_disabled_notice() {

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {

			$message_id =
			$message    = sprintf(
				/* translators: %1$s - <a> tag, %2$s - </a> tag */
				__( 'Heads up! It looks like WP Cron is disabled - this will prevent PDF Product Vouchers from being automatically generated! %1$sClick here%2$s to learn more about this configuration.', 'woocommerce-pdf-product-vouchers' ),
				'<a href="https://wordpress.org/support/article/editing-wp-config-php/#disable-cron-and-cron-timeout" target="_blank">',
				'</a>'
			);

			$this->get_admin_notice_handler()->add_admin_notice( $message, $message_id, [ 'notice_class' => 'error' ] );
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Main PDF Product Vouchers Instance, ensures only one instance is/can be loaded
	 *
	 * @see wc_pdf_product_vouchers()
	 *
	 * @since 2.2.0
	 *
	 * @return \WC_PDF_Product_Vouchers
	 */
	public static function instance() {

		if ( null === self::$instance ) {

			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @since 1.0.0
	 *
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce PDF Product Vouchers', 'woocommerce-pdf-product-vouchers' );
	}


	/**
	 * Returns the plugin file.
	 *
	 * Returns the full path and filename of the plugin file.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Returns the uploads path, which is used to store the generated PDF product voucher files.
	 *
	 * @since 1.0.0
	 *
	 * @return string upload path for this plugin
	 */
	public static function get_uploads_path() {

		return self::get_woocommerce_uploads_path() . '/woocommerce_pdf_product_vouchers';
	}


	/**
	 * Returns the plugin sales page URL.
	 *
	 * @since 3.5.0
	 *
	 * @return string
	 */
	public function get_sales_page_url() {

		return 'https://woocommerce.com/products/pdf-product-vouchers/';
	}


	/**
	 * Returns the plugin documentation url.
	 *
	 * @since 2.4.0
	 *
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return 'https://docs.woocommerce.com/document/woocommerce-pdf-product-vouchers/';
	}


	/**
	 * Returns the plugin support URL.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_support_url() {

		return 'https://woocommerce.com/my-account/marketplace-ticket-form/';
	}


}


/**
 * Returns the One True Instance of PDF Product Vouchers.
 *
 * @since 2.2.0
 *
 * @return \WC_PDF_Product_Vouchers
 */
function wc_pdf_product_vouchers() {

	return \WC_PDF_Product_Vouchers::instance();
}

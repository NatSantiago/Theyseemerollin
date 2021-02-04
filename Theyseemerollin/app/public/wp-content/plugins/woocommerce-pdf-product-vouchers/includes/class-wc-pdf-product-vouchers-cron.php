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
 * PDF Vouchers Cron Class
 *
 * Adds custom update schedule and schedules voucher expiry update events
 *
 * @since 3.0.0
 */
class WC_PDF_Product_Vouchers_Cron {


	/**
	 * Adds hooks and filters
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		// Schedule expiry events if they don't exist, run in both frontend and
		// backend so events are still scheduled when an admin reactivates the plugin.
		add_action( 'init', array( $this, 'schedule_voucher_expiry' ), 900 );

		// expire vouchers
		add_action( 'wc_pdf_product_vouchers_expire_vouchers', array( $this, 'expire_vouchers' ) );
	}


	/**
	 * Adds the expiry event if not already scheduled
	 *
	 * This performs a `do_action( 'wc_pdf_product_vouchers_expire_vouchers' )`
	 * on our custom schedule.
	 *
	 * @since 3.0.0
	 */
	public function schedule_voucher_expiry() {

		if ( ! wp_next_scheduled( 'wc_pdf_product_vouchers_expire_vouchers' ) ) {
			wp_schedule_event( time(), 'hourly', 'wc_pdf_product_vouchers_expire_vouchers' );
		}
	}


	/**
	 * Sets vouchers whose expiration date is the past, as expired
	 *
	 * @since 3.0.0
	 */
	public function expire_vouchers() {

		$voucher_posts = get_posts( array(
			'nopaging'     => true,
			'post_type'    => 'wc_voucher',
			'post_status'  => 'wcpdf-active',
			'meta_query'   => array(
				'relation' => 'AND',
				array(
					'key'     => '_expiration_date',
					'compare' => '>',
					'value'   => '0',
				),
				array(
					'key'     => '_expiration_date',
					'compare' => '<=',
					'value'   => time(),
				),
			),
		) );

		if ( ! empty( $voucher_posts ) ) {
			foreach ( $voucher_posts as $post ) {

				$voucher = wc_pdf_product_vouchers_get_voucher( $post );

				$voucher->update_status( 'expired' );
			}
		}
	}


}

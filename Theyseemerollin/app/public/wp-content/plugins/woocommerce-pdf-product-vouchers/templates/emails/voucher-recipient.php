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

/**
 * Voucher recipient html email
 *
 * @type \WC_Order $order the order object associated with this email
 * @type string $email_heading the configurable email heading
 * @type int $voucher_count the number of vouchers being attached
 * @type string $message optional customer-supplied message to display
 * @type string $recipient_name optional customer-supplied recipient name
 * @type \WC_PDF_Product_Vouchers_Email_Voucher_Recipient $email the email being sent
 *
 * @version 3.5.3
 * @since 1.2
 */

defined( 'ABSPATH' ) or exit; ?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( _n( "Hi there. You've been sent a voucher!", "Hi there. You've been sent %d vouchers!", $voucher_count, 'woocommerce-pdf-product-vouchers' ), $voucher_count ); ?></p>

<?php if ( $message ) : ?>
	<p>&ldquo;<?php echo esc_html( $message ); ?>&rdquo;</p>
<?php endif; ?>

<p><?php echo _n( 'You can find your voucher attached to this email', 'You can find your vouchers attached to this email', $voucher_count, 'woocommerce-pdf-product-vouchers' ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>

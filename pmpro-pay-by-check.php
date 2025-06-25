<?php
/*
Plugin Name: Paid Memberships Pro - Pay by Check Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-pay-by-check-add-on/
Description: A collection of customizations useful when allowing users to pay by check for Paid Memberships Pro levels.
Version: 1.1.4
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-pay-by-check
Domain Path: /languages
*/
/*
	Sample use case: You have a paid level that you want to allow people to pay by check for.

	1. Change your Payment Settings to the "Pay by Check" gateway and make sure to set the "Instructions" with instructions for how to pay by check. Save.
	2. Change the Payment Settings back to use your gateway of choice. Behind the scenes the Pay by Check settings are still stored.

	* Users who choose to pay by check will have their order to "pending" status.
	* Users with a pending order will not have access based on their level.
	* After you recieve and cash the check, you can edit the order to change the status to "success", which will give the user access.
	* An email is sent to the user RE the status change.
*/

/*
	Settings, Globals and Constants
*/
define( 'PMPRO_PAY_BY_CHECK_DIR', dirname(__FILE__) );
define( 'PMPRO_PAY_BY_CHECK_BASE_FILE', __FILE__ );
define( 'PMPROPBC_VER', '1.1.4' );

require_once PMPRO_PAY_BY_CHECK_DIR . '/includes/admin.php';
require_once PMPRO_PAY_BY_CHECK_DIR . '/includes/checkout.php';
require_once PMPRO_PAY_BY_CHECK_DIR . '/includes/crons.php';
require_once PMPRO_PAY_BY_CHECK_DIR . '/includes/emails.php';
require_once PMPRO_PAY_BY_CHECK_DIR . '/includes/frontend.php';
require_once PMPRO_PAY_BY_CHECK_DIR . '/includes/functions.php';
require_once PMPRO_PAY_BY_CHECK_DIR . '/includes/member-pending-deprecated.php';

/**
 * Set up email templates.
 */
function pmpropbc_load_email_templates() {
 	if ( class_exists( 'PMPro_Email_Template' ) ) {
 		require_once PMPRO_PAY_BY_CHECK_DIR . '/classes/email-templates/class-pmpro-email-template-check-pending-admin.php';
		require_once PMPRO_PAY_BY_CHECK_DIR . '/classes/email-templates/class-pmpro-email-template-check-pending.php';
		require_once PMPRO_PAY_BY_CHECK_DIR . '/classes/email-templates/class-pmpro-email-template-check-pending-reminder.php';
 	} else {
 		// Legacy email templates.
 		add_filter( 'pmproet_templates', 'pmpropbc_email_template_to_pmproet_add_on' );
 	}
 }
 add_action( 'init', 'pmpropbc_load_email_templates', 8 );

/*
	Load plugin textdomain.
*/
function pmpropbc_load_textdomain() {
  load_plugin_textdomain( 'pmpro-pay-by-check', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmpropbc_load_textdomain' );

/*
Function to add links to the plugin row meta
*/
function pmpropbc_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-pay-by-check.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/pmpro-pay-by-check-add-on/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-pay-by-check' ) ) . '">' . __( 'Docs', 'pmpro-pay-by-check' ) . '</a>',
			'<a href="' . esc_url('httsp://www.paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-pay-by-check' ) ) . '">' . __( 'Support', 'pmpro-pay-by-check' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpropbc_plugin_row_meta', 10, 2);
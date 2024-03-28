<?php
/*
Plugin Name: Paid Memberships Pro - Pay by Check Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-pay-by-check-add-on/
Description: A collection of customizations useful when allowing users to pay by check for Paid Memberships Pro levels.
Version: 0.12
Author: Stranger Studios
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
define( 'PMPROPBC_VER', '0.12' );

include_once( PMPRO_PAY_BY_CHECK_DIR . '/includes/deprecated.php' );

/*
	Load plugin textdomain.
*/
function pmpropbc_load_textdomain() {
  load_plugin_textdomain( 'pmpro-pay-by-check', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmpropbc_load_textdomain' );

/*
	Add settings to the edit levels page
*/
//show the checkbox on the edit level page
function pmpropbc_pmpro_membership_level_after_other_settings()
{
	$level_id = intval($_REQUEST['edit']);
	$options = pmpropbc_getOptions($level_id);
	$check_gateway_label = get_option( 'pmpro_check_gateway_label' ) ?: __( 'Check', 'pmpro-pay-by-check' ); // Default to 'Pay by Check' if no option is set.
?>
<h3 class="topborder"><?php  echo esc_html( sprintf( __( 'Pay by %s Settings', 'pmpro-pay-by-check' ), $check_gateway_label ) ); ?></h3>
<p><?php echo esc_html( sprintf( __( 'Change this setting to allow or disallow the "Pay by %s" option for this level.', 'pmpro-pay-by-check' ), $check_gateway_label ) ); ?></p>
<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="pbc_setting"><?php echo esc_html( sprintf( __( 'Allow Paying by %s:', 'pmpro-pay-by-check' ), $check_gateway_label ) );?></label></th>
		<td>
			<select id="pbc_setting" name="pbc_setting">
				<option value="0" <?php selected($options['setting'], 0);?>><?php esc_html_e( 'No. Use the default gateway only.', 'pmpro-pay-by-check' );?></option>
				<option value="1" <?php selected($options['setting'], 1);?>><?php echo esc_html( sprintf( __( 'Yes. Users choose between default gateway and %s.', 'pmpro-pay-by-check' ), $check_gateway_label ) );?></option>
				<option value="2" <?php selected($options['setting'], 2);?>><?php echo esc_html( sprintf( __( 'Yes. Users can only pay by %s.', 'pmpro-pay-by-check' ), $check_gateway_label ) );?></option>
			</select>
		</td>
	</tr>
	<tr class="pbc_recurring_field">
		<th scope="row" valign="top"><label for="pbc_renewal_days"><?php _e('Send Renewal Emails:', 'pmpro-pay-by-check');?></label></th>
		<td>
			<input type="text" id="pbc_renewal_days" name="pbc_renewal_days" size="5" value="<?php echo esc_attr($options['renewal_days']);?>" /> <?php _e('days before renewal.', 'pmpro-pay-by-check');?>
		</td>
	</tr>
	<tr class="pbc_recurring_field">
		<th scope="row" valign="top"><label for="pbc_reminder_days"><?php _e('Send Reminder Emails:', 'pmpro-pay-by-check');?></label></th>
		<td>
			<input type="text" id="pbc_reminder_days" name="pbc_reminder_days" size="5" value="<?php echo esc_attr($options['reminder_days']);?>" /> <?php _e('days after a missed payment.', 'pmpro-pay-by-check');?>
		</td>
	</tr>
	<tr class="pbc_recurring_field">
		<th scope="row" valign="top"><label for="pbc_cancel_days"><?php _e('Cancel Membership:', 'pmpro-pay-by-check');?></label></th>
		<td>
			<input type="text" id="pbc_cancel_days" name="pbc_cancel_days" size="5" value="<?php echo esc_attr($options['cancel_days']);?>" /> <?php _e('days after a missed payment.', 'pmpro-pay-by-check');?>
		</td>
	</tr>
</tbody>
</table>
<?php
}
add_action('pmpro_membership_level_after_other_settings', 'pmpropbc_pmpro_membership_level_after_other_settings');

//save pay by check settings when the level is saved/added
function pmpropbc_pmpro_save_membership_level($level_id)
{
	//get values
	if(isset($_REQUEST['pbc_setting']))
		$pbc_setting = intval($_REQUEST['pbc_setting']);
	else
		$pbc_setting = 0;

	$renewal_days = intval($_REQUEST['pbc_renewal_days']);
	$reminder_days = intval($_REQUEST['pbc_reminder_days']);
	$cancel_days = intval($_REQUEST['pbc_cancel_days']);

	//build array
	$options = array(
		'setting' => $pbc_setting,
		'renewal_days' => $renewal_days,
		'reminder_days' => $reminder_days,
		'cancel_days' => $cancel_days,
	);

	//save
	delete_option('pmpro_pay_by_check_setting_' . $level_id);
	delete_option('pmpro_pay_by_check_options_' . $level_id);
	add_option('pmpro_pay_by_check_options_' . intval($level_id), $options, "", "no");
}
add_action("pmpro_save_membership_level", "pmpropbc_pmpro_save_membership_level");

/*
	Helper function to get options.
*/
function pmpropbc_getOptions($level_id)
{
	if($level_id > 0)
	{
		//option for level, check the DB
		$options = get_option('pmpro_pay_by_check_options_' . $level_id, false);
		if(empty($options))
		{
			//check for old format to convert (_setting_ without an s)
			$options = get_option('pmpro_pay_by_check_setting_' . $level_id, false);
			if(!empty($options))
			{
				delete_option('pmpro_pay_by_check_setting_' . $level_id);
				$options = array('setting'=>$options, 'renewal_days'=>'', 'reminder_days'=>'', 'cancel_days'=>'');
				add_option('pmpro_pay_by_check_options_' . $level_id, $options, NULL, 'no');
			}
			else
			{
				//default
				$options = array('setting'=>0, 'renewal_days'=>'', 'reminder_days'=>'', 'cancel_days'=>'');
			}
		}
	}
	else
	{
		//default for new level
		$options = array('setting'=>0, 'renewal_days'=>'', 'reminder_days'=>'', 'cancel_days'=>'');
	}

	return $options;
}

/*
	Add pay by check as an option
*/
//add option to checkout along with JS
function pmpropbc_checkout_boxes()
{
	global $gateway, $pmpro_level, $pmpro_review;
	$gateway_setting = get_option("pmpro_gateway");

	$options = pmpropbc_getOptions($pmpro_level->id);

	$check_gateway_label = get_option( 'pmpro_check_gateway_label' ) ?: __( 'Check', 'pmpro-pay-by-check' );

	//only show if the main gateway is not check and setting value == 1 (value == 2 means only do check payments)
	if ( $gateway_setting != "check" && $options['setting'] == 1 ) { ?>
	<div id="pmpro_payment_method" class="pmpro_checkout">
		<hr />
		<h2>
			<span class="pmpro_checkout-h2-name"><?php esc_html_e( 'Choose Your Payment Method', 'pmpro-pay-by-check'); ?></span>
		</h2>
		<div class="pmpro_checkout-fields">
			<span class="gateway_<?php echo esc_attr($gateway_setting); ?>">
					<input type="radio" name="gateway" value="<?php echo $gateway_setting;?>" <?php if(!$gateway || $gateway == $gateway_setting) { ?>checked="checked"<?php } ?> />
							<?php if($gateway_setting == "paypalexpress" || $gateway_setting == "paypalstandard") { ?>
								<a href="javascript:void(0);" class="pmpro_radio"><?php _e('Pay with PayPal', 'pmpro-pay-by-check');?></a> &nbsp;
							<?php } elseif($gateway_setting == 'twocheckout') { ?>
								<a href="javascript:void(0);" class="pmpro_radio"><?php _e('Pay with 2Checkout', 'pmpro-pay-by-check');?></a> &nbsp;
							<?php } elseif( $gateway_setting == 'payfast' ) { ?>
								<a href="javascript:void(0);" class="pmpro_radio"><?php _e('Pay with PayFast', 'pmpro-pay-by-check');?></a> &nbsp;
							<?php } else { ?>
								<a href="javascript:void(0);" class="pmpro_radio"><?php _e('Pay by Credit Card', 'pmpro-pay-by-check');?></a> &nbsp;
							<?php } ?>
			</span> <!-- end gateway_$gateway_setting -->
			<span class="gateway_check">
					<input type="radio" name="gateway" value="check" <?php if($gateway == "check") { ?>checked="checked"<?php } ?> />
					<a href="javascript:void(0);" class="pmpro_radio"><?php echo esc_html( sprintf( __( 'Pay by %s', 'pmpro-pay-by-check' ), $check_gateway_label ) ); ?></a> &nbsp;
			</span> <!-- end gateway_check -->
			<?php
				//support the PayPal Website Payments Pro Gateway which has PayPal Express as a second option natively
				if ( $gateway_setting == "paypal" ) { ?>
					<span class="gateway_paypalexpress">
						<input type="radio" name="gateway" value="paypalexpress" <?php if($gateway == "paypalexpress") { ?>checked="checked"<?php } ?> />
						<a href="javascript:void(0);" class="pmpro_radio"><?php esc_html_e( 'Check Out with PayPal', 'pmpro-pay-by-check' ); ?></a>
					</span>
				<?php
				}
			?>
		</div> <!-- end pmpro_checkout-fields -->
	</div> <!-- end #pmpro_payment_method -->
	<?php
	} elseif ( $gateway_setting != "check" && $options['setting'] == 2 ) { ?>
		<input type="hidden" name="gateway" value="check" />
	<?php
	}
}
add_action("pmpro_checkout_boxes", "pmpropbc_checkout_boxes", 20);

/**
 * Toggle payment method when discount code is updated
 */
function pmpropbc_pmpro_applydiscountcode_return_js() {
	?>
	pmpropbc_togglePaymentMethodBox();
	<?php
}
add_action('pmpro_applydiscountcode_return_js', 'pmpropbc_pmpro_applydiscountcode_return_js');

/**
 * Enqueue scripts on the frontend.
 */
function pmpropbc_enqueue_scripts() {

	if(!function_exists('pmpro_getLevelAtCheckout'))
		return;
	
	global $gateway, $pmpro_level, $pmpro_review, $pmpro_pages, $post, $pmpro_msg, $pmpro_msgt;

	// If post not set, bail.
	if( ! isset( $post ) ) {
		return;
	}

	//make sure we're on the checkout page
	if(!is_page($pmpro_pages['checkout']) && !empty($post) && strpos($post->post_content, "[pmpro_checkout") === false)
		return;
	
	wp_register_script('pmpro-pay-by-check', plugins_url( 'js/pmpro-pay-by-check.js', __FILE__ ), array( 'jquery' ), PMPROPBC_VER );
	
	//store original msg and msgt values in case these function calls below affect them
	$omsg = $pmpro_msg;
	$omsgt = $pmpro_msgt;

	//get original checkout level and another with discount code applied	
	$pmpro_nocode_level = pmpro_getLevelAtCheckout(false, '^*NOTAREALCODE*^');
	$pmpro_code_level = pmpro_getLevelAtCheckout();			//NOTE: could be same as $pmpro_nocode_level if no code was used

	// Determine whether this level is a "check only" level.
	$check_only = 0;
	if ( ! empty( $pmpro_code_level->id ) ) {
		$options = pmpropbc_getOptions( $pmpro_code_level->id );
		if ( $options['setting'] == 2 ) {
			$check_only = 1;
		}
	}
	
	//restore these values
	$pmpro_msg = $omsg;
	$pmpro_msgt = $omsgt;
	
	wp_localize_script('pmpro-pay-by-check', 'pmpropbc', array(
			'gateway' => get_option('pmpro_gateway'),
			'nocode_level' => $pmpro_nocode_level,
			'code_level' => $pmpro_code_level,
			'pmpro_review' => (bool)$pmpro_review,
			'is_admin'  =>  is_admin(),
            'hide_billing_address_fields' => apply_filters('pmpro_hide_billing_address_fields', false ),
			'check_only' => $check_only,
		)
	);

	wp_enqueue_script('pmpro-pay-by-check');

}
add_action("wp_enqueue_scripts", 'pmpropbc_enqueue_scripts');

/**
 * Enqueue scripts in the dashboard.
 */
function pmpropbc_admin_enqueue_scripts() {
	//make sure this is the edit level page
	
	wp_register_script('pmpropbc-admin', plugins_url( 'js/pmpro-pay-by-check-admin.js', __FILE__ ), array( 'jquery' ), PMPROPBC_VER );
	wp_enqueue_script('pmpropbc-admin');
}
add_action('admin_enqueue_scripts', 'pmpropbc_admin_enqueue_scripts' );

//add check as a valid gateway
function pmpropbc_pmpro_valid_gateways($gateways)
{
    $gateways[] = "check";
    return $gateways;
}
add_filter("pmpro_valid_gateways", "pmpropbc_pmpro_valid_gateways");

/**
 * Force check gateway if pbc_setting is 2.
 *
 * @deprecated TBD Now handled similarly to pbc_setting 1.
 */
function pmpropbc_pmpro_get_gateway($gateway)
{
	_deprecated_function( __FUNCTION__, 'TBD' );

	$level = pmpro_getLevelAtCheckout();

	if ( ! empty( $level->id ) )
	{
		$options = pmpropbc_getOptions( $level->id );

    	if($options['setting'] == 2)
    		$gateway = "check";
	}

	return $gateway;
}

/*
	Need to remove some filters added by the check gateway.
	The default gateway will have it's own idea RE this.
*/
function pmpropbc_init_include_billing_address_fields()
{
	//make sure PMPro is active
	if(!function_exists('pmpro_getGateway'))
		return;

	//billing address and payment info fields
	$level = pmpro_getLevelAtCheckout();
	if ( ! empty( $level->id ) )
	{
		$options = pmpropbc_getOptions( $level->id );
    			
		if($options['setting'] == 2)
		{
			//Only hide the address if we're not using the Address for Free Levels Add On
			if ( ! function_exists( 'pmproaffl_pmpro_required_billing_fields' ) ) {				
				//hide billing address and payment info fields
				add_filter('pmpro_include_billing_address_fields', '__return_false', 20);
				add_filter('pmpro_include_payment_information_fields', '__return_false', 20);
			}

			// Need to also specifically remove them for Stripe.
			remove_filter( 'pmpro_include_payment_information_fields', array( 'PMProGateway_stripe', 'pmpro_include_payment_information_fields' ) );

			//Hide the toggle section if the PayPal Express Add On is active
			remove_action( "pmpro_checkout_boxes", "pmproappe_pmpro_checkout_boxes", 20 );
		} else {
			//keep paypal buttons, billing address fields/etc at checkout
			$default_gateway = get_option('pmpro_gateway');
			if($default_gateway == 'paypalexpress') {
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalexpress', 'pmpro_checkout_default_submit_button'));
				if ( version_compare( PMPRO_VERSION, '2.1', '>=' ) ) {
					add_action( 'pmpro_checkout_preheader', array( 'PMProGateway_paypalexpress', 'pmpro_checkout_preheader' ) );
				} else {
					/**
					 * @deprecated No longer used since paid-memberships-pro v2.1
					 */
					add_action( 'pmpro_checkout_after_form', array( 'PMProGateway_paypalexpress', 'pmpro_checkout_after_form' ) );
				}
			} elseif($default_gateway == 'paypalstandard') {
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalstandard', 'pmpro_checkout_default_submit_button'));
			} elseif($default_gateway == 'paypal') {
				if ( version_compare( PMPRO_VERSION, '2.1', '>=' ) ) {
					add_action( 'pmpro_checkout_preheader', array( 'PMProGateway_paypal', 'pmpro_checkout_preheader' ) );
				} else {
					/**
					 * @deprecated No longer used since paid-memberships-pro v2.1
					 */
					add_action( 'pmpro_checkout_after_form', array( 'PMProGateway_paypal', 'pmpro_checkout_after_form' ) );
				}
				add_filter('pmpro_include_payment_option_for_paypal', '__return_false');
			} elseif($default_gateway == 'twocheckout') {
				//undo the filter to change the checkout button text
				remove_filter('pmpro_checkout_default_submit_button', array('PMProGateway_twocheckout', 'pmpro_checkout_default_submit_button'));
			} else if( $default_gateway == 'payfast' ) {
				add_filter( 'pmpro_include_billing_address_fields', '__return_false' );	
			} else {				
				//onsite checkouts
				
				//the check gateway class in core adds filters like these
				remove_filter( 'pmpro_include_billing_address_fields', '__return_false' );
				remove_filter( 'pmpro_include_payment_information_fields', '__return_false' );
				
				//make sure the default gateway is loading their billing address fields
				if(class_exists('PMProGateway_' . $default_gateway) && method_exists('PMProGateway_' . $default_gateway, 'pmpro_include_billing_address_fields')) {
					add_filter('pmpro_include_billing_address_fields', array('PMProGateway_' . $default_gateway, 'pmpro_include_billing_address_fields'));
				}					
			}			
		}
	}

	//instructions at checkout
	remove_filter('pmpro_checkout_after_payment_information_fields', array('PMProGateway_check', 'pmpro_checkout_after_payment_information_fields'));
	add_filter('pmpro_checkout_after_payment_information_fields', 'pmpropbc_pmpro_checkout_after_payment_information_fields');	
	
	//Show a different message for users whose checks are pending
	add_filter( 'pmpro_non_member_text_filter', 'pmpropbc_check_pending_lock_text' );
}
add_action('init', 'pmpropbc_init_include_billing_address_fields', 20);

/*
	Show instructions on the checkout page.
*/
function pmpropbc_pmpro_checkout_after_payment_information_fields() {
	global $gateway, $pmpro_level;

	$options = pmpropbc_getOptions($pmpro_level->id);

	if( !empty($options) && $options['setting'] > 0 ) {
		$instructions = get_option("pmpro_instructions");
		if($gateway != 'check')
			$hidden = 'style="display:none;"';
		else
			$hidden = '';
		?>
		<div class="pmpro_check_instructions" <?php echo $hidden; ?>><?php echo wp_kses_post( $instructions ); ?></div>
		<?php
	}
}

/*
	Handle pending check payments
*/
//add pending as a default status when editing orders
function pmpropbc_pmpro_order_statuses($statuses)
{
	if(!in_array('pending', $statuses))
	{
		$statuses[] = 'pending';
	}

	return $statuses;
}
add_filter('pmpro_order_statuses', 'pmpropbc_pmpro_order_statuses');

//set check orders to pending until they are paid
function pmpropbc_pmpro_check_status_after_checkout($status)
{
	return "pending";
}
add_filter("pmpro_check_status_after_checkout", "pmpropbc_pmpro_check_status_after_checkout");


/**
 * Cancels all previously pending check orders if a user purchases the same level via a different payment method.
 * 
 * @since 0.11
 */
function pmpropbc_cancel_previous_pending_orders( $user_id, $order ) {
	global $wpdb;

	$membership_id = $order->membership_id;
	//Check to make sure PBC is enabled for the level first.
	$pbc_settings = pmpropbc_getOptions( $membership_id );

	// Assume no PBC setting is enabled for this level, so probably no cancellation setting should run.
	if ( $pbc_settings['setting'] == 0 ) {
		return;
	}
	
	// Not a renewal order for the same level just return.
	if ( ! $order->is_renewal() ) {
		return;
	}

	// Do not run code if the user is spamming checkout with check as the gateway selected.
	if ( $order->gateway == 'check' ) {
		return;
	}

	// Update any outstanding check payments for this level ID.
	$SQLquery = "UPDATE $wpdb->pmpro_membership_orders
					SET `status` = 'token'
					WHERE `user_id` = " . esc_sql( $user_id ) . "					 	
						AND `gateway` = 'check'
						AND `status` = 'pending'
						AND `membership_id` = '" . esc_sql( $membership_id ) . "'
						AND `timestamp` < '" . esc_sql( date( 'Y-m-d H:i:s', $order->timestamp ) ) . "'";

	$results = $wpdb->query( $SQLquery );
}
add_action( 'pmpro_after_checkout', 'pmpropbc_cancel_previous_pending_orders', 10, 2 );

/**
 * Check if a member's status is still pending, i.e. they haven't made their first check payment.
 *
 * @since .5
 *
 * @param int $user_id ID of the user to check.
 * @param int $level_id ID of the level to check. If 0, will return if user is pending for any level.
 *
 * @return bool If status is pending or not.
 */
function pmpropbc_isMemberPending($user_id, $level_id = 0)
{
	global $pmpropbc_pending_member_cache;

	//check the cache first
	if(isset($pmpropbc_pending_member_cache) && 
	   isset($pmpropbc_pending_member_cache[$user_id]) && 
	   isset($pmpropbc_pending_member_cache[$user_id][$level_id]))
		return $pmpropbc_pending_member_cache[$user_id][$level_id];
	
	//make room for this user's data in the cache
	if(!is_array($pmpropbc_pending_member_cache)) {
		$pmpropbc_pending_member_cache = array();
	} elseif(!is_array($pmpropbc_pending_member_cache[$user_id])) {
		$pmpropbc_pending_member_cache[$user_id] = array();
	}	
	$pmpropbc_pending_member_cache[$user_id][$level_id] = false;

	// If level is 0, we should check if user is pending for any level.
	if ( empty( $level_id ) ) {
		$is_pending = false;
		$levels = pmpro_getMembershipLevelsForUser( $user_id );
		if ( ! empty( $levels) ) {
			foreach ( $levels as $level ) {
				if ( pmpropbc_isMemberPending( $user_id, $level->id ) ) {
					$is_pending = true;
				}
			}
		}
		$pmpropbc_pending_member_cache[$user_id][$level_id] = $is_pending;
		return $is_pending;
	}

	//check their last order
	$order = new MemberOrder();
	$order->getLastMemberOrder($user_id, false, $level_id);		//NULL here means any status

	if(!empty($order->status))
	{
		if($order->status == "pending")
		{
			//for recurring levels, we should check if there is an older successful order
			$membership_level = pmpro_getSpecificMembershipLevelForUser( $user_id, $level_id );
						
			//unless the previous order has status success and we are still within the grace period
			$paid_order = new MemberOrder();
			$paid_order->getLastMemberOrder($user_id, array('success', 'cancelled'), $order->membership_id);
			
			if(!empty($paid_order) && !empty($paid_order->id) && $paid_order->gateway === 'check')
			{
				//how long ago is too long?
				$options = pmpropbc_getOptions($membership_level->id);
				
				if(pmpro_isLevelRecurring($membership_level)) {
					$cutoff = strtotime("- " . $membership_level->cycle_number . " " . $membership_level->cycle_period, current_time("timestamp")) - ($options['cancel_days']*3600*24);
				} else {
					$cutoff = strtotime("- " . $membership_level->expiration_number . " " . $membership_level->expiration_period, current_time("timestamp")) - ($options['cancel_days']*3600*24);
				}
				
				//too long ago?
				if($paid_order->timestamp < $cutoff)
					$pmpropbc_pending_member_cache[$user_id][$level_id] = true;
				else
					$pmpropbc_pending_member_cache[$user_id][$level_id] = false;
			}
			else
			{
				//no previous order, this must be the first
				$pmpropbc_pending_member_cache[$user_id][$level_id] = true;
			}			
		}
	}
	
	return $pmpropbc_pending_member_cache[$user_id][$level_id];
}

/**
 * Check if user has access to content based on their membership level.
 *
 * @param int $user_id ID of the user to check.
 * @param array(int) $content_levels Array of level IDs to check. If empty, will check if user has access to any level.
 *
 *	@return bool If user has access to content or not.
 */
function pmprobpc_memberHasAccessWithAnyLevel( $user_id, $content_levels = null ) {
	$user_levels = pmpro_getMembershipLevelsForUser( $user_id );
	if ( empty( $user_levels ) ) {
		return false;
	}

	$user_level_ids = wp_list_pluck( $user_levels, 'id' );
	if ( empty( $content_levels ) ) {
		// Check all user levels.
		$content_levels = $user_level_ids;
	}

	// Loop through all content levels.
	foreach ( $content_levels as $content_level ) {
		if ( in_array( $content_level, $user_level_ids ) && ! pmpropbc_isMemberPending( $user_id, $content_level ) ) {
			return true;
		}
	}
	return false;
}


/*
 *	In case anyone was using the typo'd function name.
 *
 * @deprecated TBD Use pmpropbc_isMemberPending() instead.
 */
function pmprobpc_isMemberPending($user_id) {
	_deprecated_function( __FUNCTION__, 'TBD', 'pmpropbc_isMemberPending()' );
	return pmpropbc_isMemberPending($user_id);
}

//if a user's last order is pending status, don't give them access
function pmpropbc_pmpro_has_membership_access_filter( $hasaccess, $mypost, $myuser, $post_membership_levels ) {
	//if they don't have access, ignore this
	if ( ! $hasaccess ) {
		return $hasaccess;
	}

	if ( empty( $post_membership_levels ) ) {
		return $hasaccess;
	}

	//if this isn't locked by level, ignore this
	$hasaccess = pmprobpc_memberHasAccessWithAnyLevel( $myuser->ID, wp_list_pluck( $post_membership_levels, 'id' ) );

	return $hasaccess;
}
add_filter("pmpro_has_membership_access_filter", "pmpropbc_pmpro_has_membership_access_filter", 10, 4);


/**
 * Filter membership shortcode restriction based on pending status.
 * 
 * @since 0.10
 */
function pmpropbc_pmpro_member_shortcode_access( $hasaccess, $content, $levels, $delay ) {
	global $current_user;
	// If they don't have a access already, just bail.
	if ( ! $hasaccess ) {
		return $hasaccess;
	}

	//If no levels attribute is added to the shortcode, assume access for any level
	if( ! is_array( $levels ) ) {
		return pmprobpc_memberHasAccessWithAnyLevel( $current_user->ID, $levels );
	}

	// If we are checking if the user is not a member, we don't want to hide this content if they are pending.
	foreach ( $levels as $level ) {
		if ( intval( $level ) <= 0 ) {
			return $hasaccess;
		}
	}

	// We only need to run this check for logged-in user's as PMPro will handle logged-out users.
	if ( is_user_logged_in() ) {
		$hasaccess = pmprobpc_memberHasAccessWithAnyLevel( $current_user->ID, $levels );
	}

	return $hasaccess;
}
add_filter( 'pmpro_member_shortcode_access', 'pmpropbc_pmpro_member_shortcode_access', 10, 4 );

/**
 * Show levels with pending payments on the account page.
 */
function pmpropbc_pmpro_account_bullets_bottom() {
	$user_levels = pmpro_getMembershipLevelsForUser( get_current_user_id() );
	if ( empty( $user_levels ) ) {
		return;
	}

	foreach ( $user_levels as $level ) {
		// Get the last order for this level.
		$order = new MemberOrder();
		$order->getLastMemberOrder( get_current_user_id(), array('success', 'pending', 'cancelled' ), $level->id );

		// If the order is pending and it was a check payment, show a message.
		if ( $order->status == 'pending' && $order->gateway == 'check' ) {
			?>
			<li>
				<?php
				// Check if the user is pending for the level.
				if ( pmpropbc_isMemberPending( $order->user_id, $order->membership_id ) ) {
					printf( esc_html__('%sYour %s membership is pending.%s We are still waiting for payment for %syour latest invoice%s.', 'pmpro-pay-by-check'), '<strong>', esc_html( $level->name ), '</strong>', sprintf( '<a href="%s">', pmpro_url('invoice', '?invoice=' . $order->code) ), '</a>' );
				} else {
					printf( esc_html__('%sImportant Notice:%s We are still waiting for payment on %sthe latest invoice%s for your %s membership.', 'pmpro-pay-by-check'), '<strong>', '</strong>', sprintf( '<a href="%s">', pmpro_url('invoice', '?invoice=' . $order->code ) ), '</a>', esc_html( $level->name ) );
				}
				?>
			</li>
			<?php
		}
	}
}
add_action('pmpro_account_bullets_bottom', 'pmpropbc_pmpro_account_bullets_bottom');

/**
 * If an invoice is pending, show a message on the invoice page.
 */
function pmpropbc_pmpro_invoice_bullets_bottom() {
	if ( empty( $_REQUEST['invoice'] ) ) {
		return;
	}

	// Get the order.
	$order = new MemberOrder( $_REQUEST['invoice'] );

	// Check if it is pending and a check payment.
	if ( $order->status == 'pending' && $order->gateway == 'check' ) {
		?>
		<li>
			<?php
			// Check if the user is pending for the level.
			if ( pmpropbc_isMemberPending( $order->user_id, $order->membership_id ) ) {
				printf( esc_html__('%sMembership pending.%s We are still waiting for payment of this invoice.', 'pmpro-pay-by-check'), '<strong>', '</strong>' );
			} else {
				printf( esc_html__('%sImportant Notice:%s We are still waiting for payment of this invoice.', 'pmpro-pay-by-check'), '<strong>', '</strong>' );
			}
			?>
		</li>
		<?php
	}
}
add_action('pmpro_invoice_bullets_bottom', 'pmpropbc_pmpro_invoice_bullets_bottom');


/**
 * Filter the confirmation message of Paid Memberships Pro when the gateway is check and the payment isn't successful.
 *
 * @param string $confirmation_message The confirmation message before it is altered.
 * @param object $invoice The PMPro MemberOrder object.
 * @return string $confirmation_message The level confirmation message.
 */
function pmpropbc_confirmation_message( $confirmation_message, $invoice ) {

	// Only filter orders that are done by check.
	if ( $invoice->gateway !== 'check' || ( $invoice->gateway == 'check' && $invoice->status == 'success' ) ) {
		return $confirmation_message;
	}

	$user = get_user_by( 'ID', $invoice->user_id );
	
	$confirmation_message = '<p>' . sprintf( __( 'Thank you for your membership to %1$s. Your %2$s membership status is: <b>%3$s</b>.', 'pmpro-pay-by-check' ), get_bloginfo( 'name' ), $invoice->membership_level->name, $invoice->status ) . ' ' . __( 'Once payment is received and processed you will gain access to your membership content.', 'pmpro-pay-by-check' ) . '</p>';

	// Put the level confirmation from level settings into the message.
	$level_obj = pmpro_getLevel( $invoice->membership_id );
	if ( ! empty( $level_obj->confirmation ) ) {
		$confirmation_message .= wpautop( wp_unslash( $level_obj->confirmation ) );
	}

	$confirmation_message .= '<p>' . sprintf( __( 'Below are details about your membership account and a receipt for your initial membership invoice. A welcome email with a copy of your initial membership invoice has been sent to %s.', 'pmpro-pay-by-check' ), $user->user_email ) . '</p>';

	// Put the check instructions into the message.
	$invoice->getMembershipLevel();
	if ( ! empty( $invoice ) && $invoice->gateway == 'check' && ! pmpro_isLevelFree( $invoice->membership_level ) ) {
		$confirmation_message .= '<div class="pmpro_payment_instructions">' . wpautop( wp_unslash( get_option( 'pmpro_instructions' ) ) ) . '</div>';
	}
	
	// Run it through wp_kses_post in case someone translates the strings to have weird code.
	return wp_kses_post( $confirmation_message );

}
add_filter( 'pmpro_confirmation_message', 'pmpropbc_confirmation_message', 10, 2 );

/*
	TODO Add note to non-member text RE waiting for check to clear
*/

/**
 * Send Invoice to user when changing order status to "success" for Check based payment.
 *
 * @param MemberOrder $morder - Updated order as it's being saved
 */
function pmpropbc_send_invoice_email( $morder ) {
    // Only worry about this if this is a check order.
    if ( 'check' !== strtolower( $morder->payment_type ) ) {
		return;
	}

	// If using PMPro v3.0+, update the subscription data.
	if ( method_exists( $morder, 'get_subscription' ) ) {
		$subscription = $morder->get_subscription();
		if ( ! empty( $subscription ) ) {
			$subscription->update();
		}
	}

	$recipient = get_user_by( 'ID', $morder->user_id );
	$invoice_email = new PMProEmail();
	$invoice_email->sendInvoiceEmail( $recipient, $morder );
}
add_action( 'pmpro_order_status_success', 'pmpropbc_send_invoice_email', 10, 1 );

/**
 *	Process recurring orders.
 */
function pmpropbc_recurring_orders() {
	global $wpdb;

	// If not using PMPro v3.0, run the legacy function.
	if ( ! class_exists( 'PMPro_Subscription' ) ) {
		pmpropbc_recurring_orders_legacy();
		return;
	}

	// Get all levels that we will check for.
	$levels = pmpro_getAllLevels(true, true);
	if ( empty( $levels ) ) {
		$levels = array();
	}

	// Loop through all levels.
	foreach ( $levels as $level ) {
		// Get options for the level.
		$options = pmpropbc_getOptions($level->id);

		// Get the cutoffs for each 'stage'.
		$renewal_days          = empty( $options['renewal_days'] ) ? 0 : $options['renewal_days'];
		$create_order_cutoff   = date('Y-m-d H:i:s', strtotime( '+ ' . $renewal_days . ' days', current_time( 'timestamp' ) ));
		$reminder_days         = empty( $options['reminder_days'] ) ? 0 : $options['reminder_days'];
		$reminder_order_cutoff = date('Y-m-d H:i:s', strtotime( '- ' . $reminder_days . ' days', current_time( 'timestamp' ) ));
		$cancel_days           = empty( $options['cancel_days'] ) ? 0 : $options['cancel_days'];
		$cancel_order_cutoff   = date('Y-m-d H:i:s', strtotime( '- ' . $cancel_days . ' days', current_time( 'timestamp' ) ));

		// Get all the subscriptions that we need to check.
		$subscription_query_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->pmpro_subscriptions WHERE status = 'active' AND membership_level_id = %d AND next_payment_date <= %s AND gateway = 'check' ORDER BY next_payment_date ASC",
				$level->id,
				$create_order_cutoff
			)
		);

		// Loop through all subscriptions.
		foreach ( $subscription_query_results as $subscription_query_result ) {
			// Get the PMPro_Subscription object.
			$subscription = new PMPro_Subscription( $subscription_query_result->id );

			// Get the most recent pending order.
			$pending_orders = $subscription->get_orders(
				array(
					'status' => 'pending',
					'limit'  => 1,
				)
			);
			$pending_order = ! empty( $pending_orders ) ? $pending_orders[0] : null;

			// Get the most recent successful order.
			$success_orders = $subscription->get_orders(
				array(
					'status' => 'success',
					'limit'  => 1,
				)
			);
			$success_order = ! empty( $success_orders ) ? $success_orders[0] : null;

			// If the timestamp of either the pending or successful order is after the subscription's next payment date by more than a day, then the subscription data is off.
			// Update the subscription and continue to the next subscription.
			$pending_order_timestamp = ! empty( $pending_order ) ? strtotime( $pending_order->timestamp ) : 0;
			$success_order_timestamp = ! empty( $success_order ) ? strtotime( $success_order->timestamp ) : 0;
			$latest_order_timestamp = max( $pending_order_timestamp, $success_order_timestamp );
			if ( $latest_order_timestamp > $subscription->get_next_payment_date() + 86400 ) {
				$subscription->update();
				continue;
			}

			// Get the user.
			$user = get_userdata( $subscription->get_user_id() );

			// Handle order creation.
			if ( $subscription->get_next_payment_date( 'Y-m-d H:i:s' ) <= $create_order_cutoff ) {
				// If no pending orders exist, we need to create one.
				if ( empty( $pending_order ) ) {
					// Create a new order.
					$pending_order = new MemberOrder();
					$pending_order->user_id = $subscription->get_user_id();
					$pending_order->membership_id = $subscription->get_membership_level_id();
					$pending_order->InitialPayment = $subscription->get_billing_amount();
					$pending_order->PaymentAmount = $subscription->get_billing_amount();
					$pending_order->BillingPeriod = $subscription->get_cycle_period();
					$pending_order->BillingFrequency = $subscription->get_cycle_number();
					$pending_order->subscription_transaction_id = $subscription->get_subscription_transaction_id();
					$pending_order->gateway = 'check';
					$pending_order->payment_type = 'Check';
					$pending_order->status = 'pending';
					$pending_order->timestamp = $subscription->get_next_payment_date();

					// Copy billing address from last order.
					if ( ! empty( $success_order ) ) {
						$pending_order->billing = new stdClass();
						$pending_order->billing->name = $success_order->billing->name;
						$pending_order->billing->street = $success_order->billing->street;
						$pending_order->billing->city = $success_order->billing->city;
						$pending_order->billing->state = $success_order->billing->state;
						$pending_order->billing->zip = $success_order->billing->zip;
						$pending_order->billing->country = $success_order->billing->country;
					}

					// Save the order.
					$pending_order->saveOrder();

					// Send an invoice email.
					if ( ! empty( $user ) ) {
						$email = new PMProEmail();
						$email->template = 'check_pending';
						$email->email = $user->user_email;
						$email->subject = sprintf( __( 'New Invoice for %s at %s', 'pmpro-pay-by-check' ), $level->name, get_option( 'blogname' ) );

						// Get body from template.
						$email->body = file_get_contents(PMPRO_PAY_BY_CHECK_DIR . '/email/' . $email->template . '.html');

						// Set up more data.
						$email->data = array(
							'name' => $user->display_name,
							'user_login' => $user->user_login,
							'sitename' => get_option('blogname'),
							'siteemail' => pmpro_getOption('from_email'),
							'membership_id' => $user->membership_level->id,
							'membership_level_name' => $user->membership_level->name,
							'membership_cost' => pmpro_getLevelCost($user->membership_level),
							'login_link' => wp_login_url(pmpro_url('account')),
							'display_name' => $user->display_name,
							'user_email' => $user->user_email,
							'instructions' => wp_unslash( pmpro_getOption('instructions') ),
							'invoice_id' => $pending_order->code,
							'invoice_total' => pmpro_formatPrice($pending_order->total),
							'invoice_date' => date(get_option('date_format'), $pending_order->timestamp),
							'billing_name' => $pending_order->billing->name,
							'billing_street' => $pending_order->billing->street,
							'billing_city' => $pending_order->billing->city,
							'billing_state' => $pending_order->billing->state,
							'billing_zip' => $pending_order->billing->zip,
							'billing_country' => $pending_order->billing->country,
							'billing_phone' => $pending_order->billing->phone,
							'cardtype' => $pending_order->cardtype,
							'accountnumber' => hideCardNumber($pending_order->accountnumber),
							'expirationmonth' => $pending_order->expirationmonth,
							'expirationyear' => $pending_order->expirationyear,
							'billing_address' => pmpro_formatAddress($pending_order->billing->name,
																$pending_order->billing->street,
																'', //address 2
																$pending_order->billing->city,
																$pending_order->billing->state,
																$pending_order->billing->zip,
																$pending_order->billing->country,
																$pending_order->billing->phone),
							'discount_code' => $pending_order->getDiscountCode() ? '<p>' . __('Discount Code', 'pmpro-pay-by-check') . ': ' . $pending_order->discount_code->code . '</p>\n' : '',

						);

						// Send the email.
						$email->sendEmail();
					}
				}
			}

			// Handle reminder emails.
			if ( $subscription->get_next_payment_date( 'Y-m-d H:i:s' ) <= $reminder_order_cutoff ) {
				// If we have a pending order, check if a reminder email has already been sent.
				if ( ! empty( $pending_order ) && ( empty( $pending_order->notes ) || ( ! str_contains( $pending_order->notes, 'Reminder Sent:' ) && ! str_contains( $pending_order->notes, 'Reminder Skipped:' ) ) ) ) {
					// Send a reminder email.
					if ( ! empty( $user ) ) {
						$email = new PMProEmail();
						$email->template = 'check_pending_reminder';
						$email->email = $user->user_email;
						$email->subject = sprintf( __( 'Reminder: New Invoice for %s at %s', 'pmpro-pay-by-check' ), $level->name, get_option( 'blogname' ) );

						// Get body from template.
						$email->body = file_get_contents(PMPRO_PAY_BY_CHECK_DIR . '/email/' . $email->template . '.html');

						// Set up more data.
						$email->data = array(
							'name' => $user->display_name,
							'user_login' => $user->user_login,
							'sitename' => get_option('blogname'),
							'siteemail' => pmpro_getOption('from_email'),
							'membership_id' => $user->membership_level->id,
							'membership_level_name' => $user->membership_level->name,
							'membership_cost' => pmpro_getLevelCost($user->membership_level),
							'login_link' => wp_login_url(pmpro_url('account')),
							'display_name' => $user->display_name,
							'user_email' => $user->user_email,
							'instructions' => wp_unslash( pmpro_getOption('instructions') ),
							'invoice_id' => $pending_order->code,
							'invoice_total' => pmpro_formatPrice($pending_order->total),
							'invoice_date' => date(get_option('date_format'), $pending_order->timestamp),
							'billing_name' => $pending_order->billing->name,
							'billing_street' => $pending_order->billing->street,
							'billing_city' => $pending_order->billing->city,
							'billing_state' => $pending_order->billing->state,
							'billing_zip' => $pending_order->billing->zip,
							'billing_country' => $pending_order->billing->country,
							'billing_phone' => $pending_order->billing->phone,
							'cardtype' => $pending_order->cardtype,
							'accountnumber' => hideCardNumber($pending_order->accountnumber),
							'expirationmonth' => $pending_order->expirationmonth,
							'expirationyear' => $pending_order->expirationyear,
							'billing_address' => pmpro_formatAddress($pending_order->billing->name,
																$pending_order->billing->street,
																'', //address 2
																$pending_order->billing->city,
																$pending_order->billing->state,
																$pending_order->billing->zip,
																$pending_order->billing->country,
																$pending_order->billing->phone),
							'discount_code' => $pending_order->getDiscountCode() ? '<p>' . __('Discount Code', 'pmpro-pay-by-check') . ': ' . $pending_order->discount_code->code . '</p>\n' : '',
						);

						// Send the email.
						$email->sendEmail();
					}

					// Update the order notes.
					$pending_order->notes .= 'Reminder Sent: ' . date( 'Y-m-d', current_time( 'timestamp' ) ) . "\n";
					$pending_order->saveOrder();
				}
			}

			// Handle cancellation.
			if ( $subscription->get_next_payment_date( 'Y-m-d H:i:s' ) <= $cancel_order_cutoff ) {
				// Cancel the user's membership, which will also cancel the subscription.
				do_action('pmpro_membership_pre_membership_expiry', $subscription->get_user_id(), $subscription->get_membership_level_id() );
				if ( pmpro_cancelMembershipLevel( $subscription->get_membership_level_id(), $subscription->get_user_id(), 'expired' ) ) {
					do_action('pmpro_membership_post_membership_expiry', $subscription->get_user_id(), $subscription->get_membership_level_id() );

					// Notify the user.
					$send_email = apply_filters('pmpro_send_expiration_email', true, $subscription->get_user_id());
					if ( ! empty( $user ) && $send_email ) {
						//send an email
						$pmproemail = new PMProEmail();
						$pmproemail->sendMembershipExpiredEmail( $user, $subscription->get_membership_level_id() );
					}
				} else {
					// Cancellation failed. Mark the subscription as cancelled.
					$subscription->cancel_at_gateway();
				}
			}
		}
	}
}
add_action('pmpropbc_recurring_orders', 'pmpropbc_recurring_orders');

/*
 * Send reminder emails for overdue check orders.
 */
function pmpropbc_reminder_emails() {
	// If not using PMPro v3.0, run the legacy function.
	if ( ! class_exists( 'PMPro_Subscription' ) ) {
		pmpropbc_reminder_emails_legacy();
		return;
	}

	// If using PMPro v3.0+, we can disable this cron as it is handled by pmpropbc_recurring_orders.
	wp_clear_scheduled_hook('pmpropbc_reminder_emails');
}
add_action('pmpropbc_reminder_emails', 'pmpropbc_reminder_emails');

/*
 * Cancel overdue check orders.
 */
function pmpropbc_cancel_overdue_orders() {
	// If not using PMPro v3.0, run the legacy function.
	if ( ! class_exists( 'PMPro_Subscription' ) ) {
		pmpropbc_cancel_overdue_orders_legacy();
		return;
	}

	// If using PMPro v3.0+, we can disable this cron as it is handled by pmpropbc_recurring_orders.
	wp_clear_scheduled_hook('pmpropbc_cancel_overdue_orders');
}
add_action('pmpropbc_cancel_overdue_orders', 'pmpropbc_cancel_overdue_orders');

/**
 *  Show a different message for users whose checks are pending
 */
function pmpropbc_check_pending_lock_text( $text ){
	global $current_user;

	//if a user does not have a membership level, return default text.
	if( !pmpro_hasMembershipLevel() ){
		return $text;
	}

	
	
	if(pmpropbc_isMemberPending($current_user->ID)==true && pmpropbc_wouldHaveMembershipAccessIfNotPending()==true){
		$text = __("Your payment is currently pending. You will gain access to this page once it is approved.", "pmpro-pay-by-check");
	}
	return $text;
}

function pmpropbc_wouldHaveMembershipAccessIfNotPending($user_id = NULL){
	global $current_user;
	if(!$user_id)
		$user_id = $current_user->ID;
	
	remove_filter("pmpro_has_membership_access_filter", "pmpropbc_pmpro_has_membership_access_filter", 10, 4);
	$toReturn = pmpro_has_membership_access(NULL, NULL, true)[0];
	add_filter("pmpro_has_membership_access_filter", "pmpropbc_pmpro_has_membership_access_filter", 10, 4);
	return $toReturn;
}


/*
	Activation/Deactivation
*/
function pmpropbc_activation()
{
	//schedule crons
	wp_schedule_event(current_time('timestamp'), 'daily', 'pmpropbc_cancel_overdue_orders');
	wp_schedule_event(current_time('timestamp')+1, 'daily', 'pmpropbc_recurring_orders');
	wp_schedule_event(current_time('timestamp')+2, 'daily', 'pmpropbc_reminder_emails');

	do_action('pmpropbc_activation');
}
function pmpropbc_deactivation()
{
	//remove crons
	wp_clear_scheduled_hook('pmpropbc_cancel_overdue_orders');
	wp_clear_scheduled_hook('pmpropbc_recurring_orders');
	wp_clear_scheduled_hook('pmpropbc_reminder_emails');

	do_action('pmpropbc_deactivation');
}
register_activation_hook(__FILE__, 'pmpropbc_activation');
register_deactivation_hook(__FILE__, 'pmpropbc_deactivation');

/*
Function to add links to the plugin row meta
*/
function pmpropbc_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-pay-by-check.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/pmpro-pay-by-check-add-on/')  . '" title="' . esc_attr( __( 'View Documentation', 'paid-memberships-pro' ) ) . '">' . __( 'Docs', 'paid-memberships-pro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'paid-memberships-pro' ) ) . '">' . __( 'Support', 'paid-memberships-pro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpropbc_plugin_row_meta', 10, 2);

/**
 * Add the Pay by Check email templates to the Core PMPro Email Templates.
 * 
 * @param array $template - The existing PMPro Email Templates.
 * @return array $template - The updated PMPro Email Templates.
 * @since TBD.
 */
function pmpropbc_email_template_to_pmproet_add_on( $template ) {

	$template['check_pending'] = array(
		'subject'     => 'New Invoice for !!display_name!! at !!sitename!!',
		'description' => 'Pay By Check - Check Pending',
		'body'        => file_get_contents( PMPRO_PAY_BY_CHECK_DIR . '/email/check_pending.html' ), 
	);
	$template['check_pending_reminder'] = array(
		'subject'     => 'Reminder: New Invoice for !!display_name!! at !!sitename!!',
		'description' => 'Pay By Check - Check Pending Reminder',
		'body'        => file_get_contents( PMPRO_PAY_BY_CHECK_DIR . '/email/check_pending_reminder.html' ), 
	);

	return $template;
}
add_filter( 'pmproet_templates', 'pmpropbc_email_template_to_pmproet_add_on' );

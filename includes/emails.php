<?php

/**
 * Add the Pay by Check email templates to the Core PMPro Email Templates.
 * 
 * @param array $template - The existing PMPro Email Templates.
 * @return array $template - The updated PMPro Email Templates.
 * @since 1.0.
 */
function pmpropbc_email_template_to_pmproet_add_on( $template ) {

	$template['check_pending'] = array(
		'subject'     => 'New Invoice for !!display_name!! at !!sitename!!',
		'description' => 'Pay By Check - Check Pending',
		'body'        => __( '<p>You have a new invoice for !!sitename!!.</p>

		!!instructions!!
		
		<p>Below are details about your membership account and a receipt for your membership invoice.</p>
		
		<p>Account: !!display_name!! (!!user_email!!)</p>
		<p>Membership Level: !!membership_level_name!!</p>
		
		<p>
			Invoice #!!invoice_id!! on !!invoice_date!!<br />
			Total Billed: !!invoice_total!!
		</p>
		
		<p>Log in to your membership account here: !!login_link!!</p>', 'pmpro_pay_by_check' ),
	);
	$template['check_pending_admin'] = array(
		'subject'     => 'Pending checkout for !!display_name!! at !!sitename!!',
		'description' => 'Pay By Check - Check Pending Admin',
		'body'        => __( '<p>There is a pending checkout at !!sitename!!.</p>
		
		<p>Account: !!display_name!! (!!user_email!!)</p>
		<p>Membership Level: !!membership_level_name!!</p>

		<p>
			Invoice #!!invoice_id!! on !!invoice_date!!<br />
			Total Billed: !!invoice_total!!
		</p>', 'pmpro_pay_by_check' ),
	);
	$template['check_pending_reminder'] = array(
		'subject'     => 'Reminder: New Invoice for !!display_name!! at !!sitename!!',
		'description' => 'Pay By Check - Check Pending Reminder',
		'body'        => __( '<p>This is a reminder. You have a new invoice for !!sitename!!.</p>

		!!instructions!!
		
		<p>Below are details about your membership account and a receipt for your membership invoice.</p>
		
		<p>Account: !!display_name!! (!!user_email!!)</p>
		<p>Membership Level: !!membership_level_name!!</p>
		
		<p>
			Invoice #!!invoice_id!! on !!invoice_date!!<br />
			Total Billed: !!invoice_total!!
		</p>
		
		<p>Log in to your membership account here: !!login_link!!</p>', 'pmpro_pay_by_check' ),
	);

	return $template;
}

/**
 * Send the check_pending email.
 *
 * @param MemberOrder $order - The order object.
 * @return bool - True if the email was sent, false otherwise.
 */
function pmpropbc_send_check_pending_email( $order ) {
	// Get the user.
	$user = get_userdata( $order->user_id );
	if ( empty( $user ) ) {
		return false;
	}

	if ( class_exists( 'PMPro_Email_Template_Check_Pending' ) ) {
		// Use the PMPro Email Template class if available.
		$email_template = new PMPro_Email_Template_Check_Pending( $user, $order );
		return $email_template->send();
	}

	// Get the membership level.
	$level = $order->getMembershipLevel();
	if ( empty( $level ) ) {
		return false;
	}

	$email = new PMProEmail();
	$email->template = "check_pending";
	$email->email = $user->user_email;
	$email->subject = sprintf(__("New Invoice for %s at %s", "pmpro-pay-by-check"), $level->name, get_option("blogname"));

	//setup more data
	$email->data = array(
		"name" => $user->display_name,
		"user_login" => $user->user_login,
		"sitename" => get_option("blogname"),
		"siteemail" => pmpro_getOption("from_email"),
		"membership_id" => $level->id,
		"membership_level_name" => $level->name,
		"membership_cost" => pmpro_getLevelCost( $level ),
		"login_link" => wp_login_url(pmpro_url("account")),
		"display_name" => $user->display_name,
		"user_email" => $user->user_email,
	);

	$email->data["instructions"] = wp_unslash(  pmpro_getOption('instructions') );
	$email->data["invoice_id"] = $order->code;
	$email->data["invoice_total"] = pmpro_formatPrice($order->total);
	$email->data["invoice_date"] = date(get_option('date_format'), $order->timestamp);
	$email->data["billing_name"] = $order->billing->name;
	$email->data["billing_street"] = $order->billing->street;
	$email->data["billing_city"] = $order->billing->city;
	$email->data["billing_state"] = $order->billing->state;
	$email->data["billing_zip"] = $order->billing->zip;
	$email->data["billing_country"] = $order->billing->country;
	$email->data["billing_phone"] = $order->billing->phone;
	$email->data["cardtype"] = $order->cardtype;
	$email->data["accountnumber"] = hideCardNumber($order->accountnumber);
	$email->data["expirationmonth"] = $order->expirationmonth;
	$email->data["expirationyear"] = $order->expirationyear;
	$email->data["billing_address"] = pmpro_formatAddress($order->billing->name,
															$order->billing->street,
															"", //address 2
															$order->billing->city,
															$order->billing->state,
															$order->billing->zip,
															$order->billing->country,
															$order->billing->phone);

	if($order->getDiscountCode())
		$email->data["discount_code"] = "<p>" . __("Discount Code", "pmpro-pay-by-check") . ": " . $order->discount_code->code . "</p>\n";
	else
		$email->data["discount_code"] = "";

	//send the email
	return $email->sendEmail();
}

/**
 * Send the check_pending_admin email.
 *
 * @since 1.1
 *
 * @param MemberOrder $order - The order object.
 * @return bool - True if the email was sent, false otherwise.
 */
function pmpropbc_send_check_pending_admin_email( $order ) {
	// Get the user.
	$user = get_userdata( $order->user_id );
	if ( empty( $user ) ) {
		return false;
	}

	// If the PMPro Email Template class is available, use it.
	if ( class_exists( 'PMPro_Email_Template_Check_Pending_Admin' ) ) {
		$email_template = new PMPro_Email_Template_Check_Pending_Admin( $user, $order );
		return $email_template->send();
	}

	// Get the membership level.
	$level = $order->getMembershipLevel();
	if ( empty( $level ) ) {
		return false;
	}

	$email = new PMProEmail();
	$email->template = "check_pending_admin";
	$email->email = get_bloginfo("admin_email");
	$email->subject = sprintf(__("Pending checkout for !!display_name!! at !!sitename!!", "pmpro-pay-by-check"), $level->name, get_option("blogname"));

	//setup more data
	$email->data = array(
		"name" => $user->display_name,
		"user_login" => $user->user_login,
		"sitename" => get_option("blogname"),
		"siteemail" => pmpro_getOption("from_email"),
		"membership_id" => $level->id,
		"membership_level_name" => $level->name,
		"membership_cost" => pmpro_getLevelCost( $level ),
		"login_link" => wp_login_url(pmpro_url("account")),
		"display_name" => $user->display_name,
		"user_email" => $user->user_email,
	);

	$email->data["instructions"] = wp_unslash(  pmpro_getOption('instructions') );
	$email->data["invoice_id"] = $order->code;
	$email->data["invoice_total"] = pmpro_formatPrice($order->total);
	$email->data["invoice_date"] = date(get_option('date_format'), $order->timestamp);
	$email->data["billing_name"] = $order->billing->name;
	$email->data["billing_street"] = $order->billing->street;
	$email->data["billing_city"] = $order->billing->city;
	$email->data["billing_state"] = $order->billing->state;
	$email->data["billing_zip"] = $order->billing->zip;
	$email->data["billing_country"] = $order->billing->country;
	$email->data["billing_phone"] = $order->billing->phone;
	$email->data["cardtype"] = $order->cardtype;
	$email->data["accountnumber"] = hideCardNumber($order->accountnumber);
	$email->data["expirationmonth"] = $order->expirationmonth;
	$email->data["expirationyear"] = $order->expirationyear;
	$email->data["billing_address"] = pmpro_formatAddress($order->billing->name,
															$order->billing->street,
															"", //address 2
															$order->billing->city,
															$order->billing->state,
															$order->billing->zip,
															$order->billing->country,
															$order->billing->phone);

	if($order->getDiscountCode())
		$email->data["discount_code"] = "<p>" . __("Discount Code", "pmpro-pay-by-check") . ": " . $order->discount_code->code . "</p>\n";
	else
		$email->data["discount_code"] = "";

	//send the email
	return $email->sendEmail();
}

/**
 * Send the check_pending_reminder email.
 *
 * @param MemberOrder $order - The order object.
 * @return bool - True if the email was sent, false otherwise.
 */
function pmpropbc_send_check_pending_reminder_email( $order ) {
	// Get the user.
	$user = get_userdata( $order->user_id );
	if ( empty( $user ) ) {
		return false;
	}

	// If the PMPro Email Template class is available, use it.
	if ( class_exists( 'PMPro_Email_Template_Check_Pending_Reminder' ) ) {
		$email_template = new PMPro_Email_Template_Check_Pending_Reminder( $user, $order );
		return $email_template->send();
	}

	// Get the membership level.
	$level = $order->getMembershipLevel();
	if ( empty( $level ) ) {
		return false;
	}

	$email = new PMProEmail();
	$email->template = "check_pending_reminder";
	$email->email = $user->user_email;
	$email->subject = sprintf(__("Reminder: New Invoice for %s at %s", "pmpro-pay-by-check"), $level->name, get_option("blogname"));

	//setup more data
	$email->data = array(
		"name" => $user->display_name,
		"user_login" => $user->user_login,
		"sitename" => get_option("blogname"),
		"siteemail" => pmpro_getOption("from_email"),
		"membership_id" => $level->id,
		"membership_level_name" => $level->name,
		"membership_cost" => pmpro_getLevelCost( $level ),
		"login_link" => wp_login_url(pmpro_url("account")),
		"display_name" => $user->display_name,
		"user_email" => $user->user_email,
	);

	$email->data["instructions"] = wp_unslash(  pmpro_getOption('instructions') );
	$email->data["invoice_id"] = $order->code;
	$email->data["invoice_total"] = pmpro_formatPrice($order->total);
	$email->data["invoice_date"] = date(get_option('date_format'), $order->timestamp);
	$email->data["billing_name"] = $order->billing->name;
	$email->data["billing_street"] = $order->billing->street;
	$email->data["billing_city"] = $order->billing->city;
	$email->data["billing_state"] = $order->billing->state;
	$email->data["billing_zip"] = $order->billing->zip;
	$email->data["billing_country"] = $order->billing->country;
	$email->data["billing_phone"] = $order->billing->phone;
	$email->data["cardtype"] = $order->cardtype;
	$email->data["accountnumber"] = hideCardNumber($order->accountnumber);
	$email->data["expirationmonth"] = $order->expirationmonth;
	$email->data["expirationyear"] = $order->expirationyear;
	$email->data["billing_address"] = pmpro_formatAddress($order->billing->name,
															$order->billing->street,
															"", //address 2
															$order->billing->city,
															$order->billing->state,
															$order->billing->zip,
															$order->billing->country,
															$order->billing->phone);

	if($order->getDiscountCode())
		$email->data["discount_code"] = "<p>" . __("Discount Code", "pmpro") . ": " . $order->discount_code->code . "</p>\n";
	else
		$email->data["discount_code"] = "";

	//send the email
	$email->sendEmail();
}

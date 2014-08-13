<?php
/**
 * Functions booking actions 
 * This includes validating & processing bookings, responding to gateway notifications
 * and e-mailing admins / bookees.
 *
 * @package booking-functions
 */

/* Listen for gateway confirmation/needs action */
add_action( 'eventorganiser_gateway_notification_pending', 'eventorganiser_booking_needs_action', 10, 2 );
add_action( 'eventorganiser_gateway_notification_transaction', 'eventorganiser_booking_payment_confirmed', 10, 2 );

/* Listen for booking confirmation - send emails */
add_action( 'transition_post_status', '_eventorganiser_maybe_notify_confirmed_booking', 10, 3 );

/* Maybe notify admin of new booking */
if ( in_array( 'new', eventorganiser_pro_get_option( 'notify_bookings' ) ) ) {
	add_action( 'eventorganiser_new_booking', 'eventorganiser_notify_new_booking' );
}


/**
 * Notifies the admin when a new booking is made.
 *
 * @since 1.0
 * @used-by eventorganiser_process_booking()
 * @ignore
 *
 * @param int     $booking_id The Booking ID
 */
function eventorganiser_notify_new_booking( $booking_id ) {

	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$post_id = (int) eo_get_booking_meta( $booking_id, 'event_id' );
	$occurrence_id = (int) eo_get_booking_meta( $booking_id, 'occurrence_id' );
	$event = get_the_title( $post_id );
	$user_id = (int) eo_get_booking_meta( $booking_id, 'bookee' );
	

	$hr= '<hr style="color:#E6E8E6;background-color:#E6E8E6;height:1px;border:0">';

	$title =
		'<h2 style="display:block;font-family:Arial;font-size:30px;font-weight:bold;line-height:120%;margin-right:0;margin-bottom:15px;margin-left:0;text-align:left;color:#333 !important">'
		.__( 'New event booking', 'eventorganiserp' ).'</h2>';

	$preamble = sprintf(
		'<p>'.__( 'A new booking has been made on your site %s:', 'eventorganiserp' ).'</p>'
		.'<h3 style="color:black;"> Booking #%d for %s %s </h3>',
		$blogname,
		$booking_id,
		$event,
		empty( $occurrence_id ) ? '' : eo_get_the_start( '(jS F Y)', $post_id, null, $occurrence_id )
	);

	$booking_table = _eventorganiser_get_booking_table_for_email( $booking_id );

	$bookee_details =sprintf(
			'<h3 style="color:black;"> %s </h3>
			<p><strong>%s:</strong> %s </p>
			<p><strong>%s:</strong> %s </p>',
			__( 'Bookee', 'eventorganiserp' ),
			__( 'Username', 'eventorganiserp' ),
			eo_get_booking_meta( $booking_id, 'bookee_display_name' ),
			__( 'Email', 'eventorganiserp' ),
			eo_get_booking_meta( $booking_id, 'bookee_email' )
	);
	
	$booking_fields = sprintf( '<h3 style="color:black;"> %s </h3>', __( 'Booking form', 'eventorganiserp' ) );
	$booking_fields .= eventorganiser_email_form_submission_list( $booking_id );

	$postamble = sprintf( 
			'<p>'.__( 'You can view the <a href="%s">booking here</a>', 'eventorganiserp' ). '</p>', 
			eventorganiser_edit_booking_url( $booking_id ) 
	);

	$message = $title.$preamble.$booking_table.$hr.$bookee_details.$hr.$booking_fields.$hr.$postamble;

	eventorganiser_mail( eo_get_booking_notification_email( $booking_id ), sprintf( __( '[%s] New Event Booking for %s', 'eventorganiserp' ), $blogname, $event ), $message, false, false, 'eo-email-template-event-organiser.php' );
}


function _eventorganiser_get_booking_table_for_email( $booking_id ){
	
	$total_price = eo_get_booking_meta( $booking_id, 'booking_amount' );
	$total_qty = eo_get_booking_meta( $booking_id, 'ticket_quantity' );
	$tickets = eo_get_booking_tickets( $booking_id );
	
	$booking_table = sprintf(
			'<table style="width:100%%;text-align:center;">
				<thead style="font-weight:bold;"><tr> <th>%s</th><th> %s </th> <th>%s</th></tr></thead>
				<tbody>',
			__( 'Ticket', 'eventorganiserp' ),
			__( 'Price', 'eventorganiserp' ),
			__( 'Quantity', 'eventorganiserp' )
	);
	
	foreach ( $tickets as $ticket ) {
		$booking_table .= sprintf(
				'<tr> <td>%s<td> %s </td> <td>%d</td></tr>',
				esc_html( $ticket->ticket_name ),
				eo_format_price( $ticket->ticket_price ),
				$ticket->ticket_quantity
		);
	}
	
	$booking_table .= apply_filters( 'eventorganiser_get_booking_table_for_email_pre_total', '', $booking_id );
	$booking_table .= sprintf( '<tr> <td>%s</td><td> %s </td> <td>%d</td></tr></tbody></table>', __( 'Total' ), eo_format_price( $total_price ), $total_qty );

	return apply_filters( 'eventorganiser_get_booking_table_for_email', $booking_table, $booking_id );
}

/**
 * Listens for a status change of a booking to 'confirmed', and triggers `eventorganiser_notify_confirmed_booking()`.
 *
 * Hooked onto `pending_to_confirmed` - when booking status is updated
 *
 * @since 1.7
 * @ignore
 *
 * @param string  $new_status The post's new status
 * @param string  $old_status The post's old status (maybe the same as $new_status)
 * @param WP_Post $post       The post object
 */
function _eventorganiser_maybe_notify_confirmed_booking( $new_status, $old_status, $post ){
	
	if ( $new_status !== $old_status && 'confirmed' == $new_status && 'eo_booking' == get_post_type( $post ) ){
		eventorganiser_notify_confirmed_booking( $post->ID );
	}	
	
}

/**
 * Notifies the bookee (and optionlly the admin) when a new booking is **confirmed**
 *
 * @since 1.0
 * @ignore
 *
 * @param int $booking_id The Booking ID
 */
function eventorganiser_notify_confirmed_booking( $booking_id ) {

	if ( 'eo_booking' != get_post_type( $booking_id ) ){
		return;
	}
	//Allow plugins to over-ride the behaviour of this function
	if( !apply_filters( 'eventorganiser_notify_confirmed_booking', true, $booking_id ) ){
		return;
	} 
		
	/*First email bookee */
	$template = eventorganiser_pro_get_option( 'email_template' );

	/* Get email details */
	$from_name = get_bloginfo( 'name' );
	$from_email = eo_get_admin_email( $booking_id );

	/* Get messgage from the options */
	$message = eventorganiser_email_template_tags( eventorganiser_pro_get_option( 'email_tickets_message' ), $booking_id, $template );
	$message = wpautop( $message );

	/* Set headers */
	$headers = array(
		'from:' . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>",
		'reply-to:' . $from_email
	);
	
	$bookee_email = eo_get_booking_meta( $booking_id, 'bookee_email' );

	$subject = apply_filters( 'eventorganiser_booking_confirmed_email_subject', __( 'Thank you for your booking', 'eventorganiserp' ), $booking_id );
	$message = apply_filters( 'eventorganiser_booking_confirmed_email_body', $message, $booking_id );
	$headers = apply_filters( 'eventorganiser_booking_confirmed_email_headers', $headers, $booking_id );
	$attachments = apply_filters( 'eventorganiser_booking_confirmed_email_attachments', array(), $booking_id );
	$template = apply_filters( 'eventorganiser_booking_confirmed_email_template', $template, $booking_id );

	eventorganiser_mail( $bookee_email, $subject, $message, $headers, $attachments, $template );

	if ( in_array( 'confirmed', eventorganiser_pro_get_option( 'notify_bookings' ) ) ) {
		//Notify admin
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$post_id = (int) eo_get_booking_meta( $booking_id, 'event_id' );
		$occurrence_id = (int) eo_get_booking_meta( $booking_id, 'occurrence_id' );
		$event = get_the_title( $post_id );
		$user_id = (int) eo_get_booking_meta( $booking_id, 'bookee' );
		$total_price = eo_get_booking_meta( $booking_id, 'booking_amount' );
		$total_qty = eo_get_booking_meta( $booking_id, 'ticket_quantity' );

		$hr= '<hr style="color:#E6E8E6;background-color:#E6E8E6;height:1px;border:0">';

		$title =
			'<h2 style="display:block;font-family:Arial;font-size:30px;font-weight:bold;line-height:120%;margin-right:0;margin-bottom:15px;margin-left:0;text-align:left;color:#333 !important">'
			.__( 'Booking Confirmed', 'eventorganiserp' ).'</h2>';

		$preamble = sprintf(
			'<p>'.__( 'A new booking has been confirmed on your site %s:', 'eventorganiserp' ).'</p>'
			.'<h3 style="color:black;"> Booking #%d for %s %s </h3>',
			$blogname,
			$booking_id,
			$event,
			empty( $occurrence_id ) ? '' : eo_get_the_start( '(jS F Y)', $post_id, null, $occurrence_id )
		);
		
		$booking_table = _eventorganiser_get_booking_table_for_email( $booking_id );
		
		$bookee = get_userdata( $user_id );
		$bookee_details =sprintf(
			'<h3 style="color:black;"> %s </h3>
			<p><strong>%s:</strong> %s </p>
			<p><strong>%s:</strong> %s </p>',
			__( 'Bookee', 'eventorganiserp' ),
			__( 'Username', 'eventorganiserp' ),
			eo_get_booking_meta( $booking_id, 'bookee_display_name' ),
			__( 'Email', 'eventorganiserp' ),
			eo_get_booking_meta( $booking_id, 'bookee_email' )
		);
		
		$booking_fields = sprintf( '<h3 style="color:black;"> %s </h3>', __( 'Booking form', 'eventorganiserp' ) );
		$booking_fields .= eventorganiser_email_form_submission_list( $booking_id );

		$postamble = sprintf(
				'<p>'.__( 'You can view the <a href="%s">booking here</a>', 'eventorganiserp' ). '</p>',
				eventorganiser_edit_booking_url( $booking_id )
		);

		$message = $title.$preamble.$booking_table.$hr.$bookee_details.$hr.$booking_fields.$hr.$postamble;

		eventorganiser_mail( eo_get_booking_notification_email( $booking_id ), sprintf( __( '[%s] Confirmed Event Booking for %s', 'eventorganiserp' ), $blogname, $event ), $message, array(), array(), 'eo-email-template-event-organiser.php' );
	}
}


/**
 * Validates booking submission
 * Checks gateways & tickets
 * 
 * @param unknown_type $input
 * @param unknown_type $form
 * @param unknown_type $errors
 * @return number
 */
function _eo_validate_booking_submission( $input, $form, $errors ){
	
	$event_id = (int) $form->get( 'event_id' );
	$occurrence_id = (int) $form->get( 'occurrence_id' );
	
	$book_series = eventorganiser_pro_get_option( 'book_series' ) ? true : false;
	$tickets = $form->get_element( 'ticketpicker' )->get_value();
	
	$gateway = $form->get_element( 'gateway' )->get_value(); 
	
	//In case the user is regestering
	$name    = $form->get_element( 'name' );
	$email   = $form->get_element( 'email' );
	$account = $form->get_element( 'create-account' );
	
	$fname   = $name ? $name->get_value( 'fname' ) : '';
	$lname   = $name ? $name->get_value( 'lname' ) : '';
	$email   = $email ? sanitize_email( $email->get_value() ) : '';
	$account = $account ? $account->get_value() : ( eventorganiser_pro_get_option( 'allow_guest_booking' ) == 1 );
		
	//Sanity check
	if ( empty( $event_id ) || ( !$book_series && empty( $occurrence_id ) ) ) {
		//Event not specified
		$form->add_error( 'invalid_event', __( 'Please select an event.', 'eventorganiserp' ) );
	}
	
	/* Check bookee details */
	if (  !is_user_logged_in() && eventorganiser_pro_get_option( 'allow_guest_booking' ) ) {
	
		$fname =  eo_sanitize_name( $fname );
		$lname =  eo_sanitize_name( $lname );
		$email = sanitize_email( $email );
	
		if ( empty( $fname ) ) {
			$form->add_error( 'invalid_bookee_name', __( 'Please provide a name.', 'eventorganiserp' ) );
		}
	
		if ( !is_email( $email ) ) {
			$form->add_error( 'invalid_bookee_email', __( 'Please provide a valid email address.', 'eventorganiserp' ) );
			
		}elseif ( ( $account || 1 == eventorganiser_pro_get_option( 'allow_guest_booking' ) ) && email_exists( $email ) ) {
			//If we are allowing 'anonymous bookees' then we don't need to register an acount
			$form->add_error( 'invalid_bookee_email', __( 'This email is already registered, please choose another one or log-in with that account', 'eventorganiserp' ) );
		}
	
		//We'll add them later after we've checked for any more errors.
	}elseif( !is_user_logged_in() ){
		$form->add_error( 'bookee_not_logged_in', __( 'You must be logged in to place a booking', 'eventorganiserp' ) );
	}else {
		$user_id = get_current_user_id();
	}
	
	/* Remove ticket types that have not been selected */
	$tickets = array_filter( $tickets );
	
	/* Ensure only tickets assigned to this event are being booked */
	$tickets_on_sale = eo_get_event_tickets_on_sale( $event_id, $occurrence_id );
	
	//(Valid) tickets for this booking
	$_tickets = array_intersect_key( $tickets_on_sale, $tickets );
	
	// Get remaining ticket quantities
	$available = eo_get_the_occurrences_tickets( $event_id, $occurrence_id );

	//This should return an array with just one element - corresponding to the series, or a particular occurrence.
	if( $available )
		$available = array_pop( $available );
		
	/* Check ticket quantity & validity */
	$total_qty =0;
	$total_price =0;
	$unavailable = false;
	$invalid = false;
	
	if ( $tickets ) {
		foreach ( $tickets as $id => $qty ) {
			$qty = (int) $qty;
			
			if( !isset( $_tickets[$id] ) && $qty > 0 ){
				//Trying to book an invalid ticket
				$invalid = true;	
				continue;
				
			}elseif ( !isset( $available['tickets'][$id] ) || $qty > $available['tickets'][$id] ) {
				//Trying to book a sold out ticket
				$unavailable = true;
				continue;
			}
			
			$total_qty = $total_qty + $qty;
			$total_price = $total_price + $qty*$_tickets[$id]['price'];
		}
	}
	
	if( !$tickets ){
		$form->add_error( 'invalid_tickets', __( 'You have not selected any tickets', 'eventorganiserp' ) );
		
	}elseif( $invalid ){
		//Invalid ticket selected
		$form->add_error( 'invalid_tickets', __( 'The tickets you have selected are no longer available.', 'eventorganiserp' ) );
		
	}elseif( 0 == $total_qty ) {
		//No tickets selected
		$form->add_error( 'invalid_tickets', __( 'Please select a ticket.', 'eventorganiserp' ) );
	
	}elseif( $unavailable ) {
		//Tickets sold out
		$form->add_error( 'sold_out', __( 'Sorry, but the requested number of tickets is unavailable.', 'eventorganiserp' ) );
	}

	//Handle the booking cap
	if( $cap = (int) get_post_meta( $event_id, '_eventorganiser_booking_cap', true ) ){
	
		//This may include pending bookings if pending reserves spaces
		$total_reserved =  isset( $available['reserved'] ) ? (int) $available['reserved'] : 0;

		if( $total_reserved + $total_qty > $cap ){
			$form->add_error( 'sold_out', __( 'Sorry, but the requested number of tickets is unavailable.', 'eventorganiserp' ) );
		}
	}
	
	if( $form->is_simple_booking_mode() //SBM is enabled
		&& count( $tickets_on_sale ) == 1 //only 1 ticket available
		&& ( eventorganiser_pro_get_option( 'book_series' ) || !eo_reoccurs( $event_id ) ) //No date selection needed
		&& $total_qty > 1 
		){
		$form->add_error( 'invalid_tickets', __( 'Sorry, but the requested number of tickets is unavailable.', 'eventorganiserp' ) );
	}
	
	/* Check gateway is active. **Do not** move to EO_Booking_Form_Element_Gateway::is_valid() */
	$enabled_gateways = eventorganiser_get_enabled_gateways();
	if ( !isset( $enabled_gateways[$gateway] ) && $total_price > 0 ) {
		$errors->add( 'invalid_gatway', __( 'The payment gateway you have chosen is invalid', 'eventorganiserp' ) );
	}
	
	return $input;
}
add_filter( 'eventorganiser_validate_booking_submission', '_eo_validate_booking_submission', 10, 3 );


/**
 * Process booking - creates booking, and redirects to gateway.
 * Hooked onto eventorganiser_process_booking_form_submission (100).
 * @param unknown_type $input
 * @param unknown_type $form_id
 * @return unknown
 */
function _eo_process_booking_submission( $form ){

	$name    = $form->get_element( 'name' );
	$email   = $form->get_element( 'email' );
	$account = $form->get_element( 'create-account' );
	
	$fname   = $name ? $name->get_value( 'fname' ) : '';
	$lname   = $name ? $name->get_value( 'lname' ) : '';
	$email   = $email ? sanitize_email( $email->get_value() ) : '';
	$account = $account ? $account->get_value() : ( eventorganiser_pro_get_option( 'allow_guest_booking' ) == 1 );
	
	$event_id = (int) $form->get( 'event_id' );
	$occurrence_id = (int) $form->get( 'occurrence_id' );
	$book_series = eventorganiser_pro_get_option( 'book_series' ) ? true : false;
	$tickets = $form->get_element( 'ticketpicker' )->get_value();
	$gateway = $form->get_element( 'gateway' )->get_value(); 
	
	/* $allow_guest_booking: 0 = no, 1 = yes, register account,  2 = yes, account optional, 3 = yes, no account */
	$allow_guest_booking = eventorganiser_pro_get_option( 'allow_guest_booking' );
	

	/* If user is logged out and we need to create an account */
	if ( !is_user_logged_in() && ( ( 2 == $allow_guest_booking && $account ) || 1 == $allow_guest_booking ) ) {
				
		$username = array_filter( array( $fname, $lname) );
		$username =  sanitize_user( implode( '.', $username ) );
		$username = eventorganiser_generate_unique_username( $username );
	
		$password = wp_generate_password( 12, false );
		$user_id = wp_create_user( $username , $password, $email );
	
		if ( ! $user_id || is_wp_error( $user_id ) ) {
			$form->add_error( 'registration_failed', 
				sprintf( 
					__( 'Registration failed. Please contact an <a href="mailto:%s">administrator</a>.', 'eventorganiserp' ), 
					get_option( 'admin_email' ) 
				) );
			return;
		}
	
		//Update user's first & last name
		wp_update_user( array ('ID' => $user_id, 'first_name' => $fname, 'last_name' => $lname ) ) ;
	
		//Set up the Password change nag & e-mail massword
		update_user_option( $user_id, 'default_password_nag', true, true );
		wp_new_user_notification( $user_id, $password );
	
		//Log the user in
		$user = eventorganiser_login_by_email( $email, $password );
		if ( !is_wp_error( $user ) )
			wp_set_current_user( $user->ID );
		
	/* If user is logged out, but account was not / cannot be created */
	}elseif( !is_user_logged_in() && ( 2 == $allow_guest_booking || 3 == $allow_guest_booking ) ){ 
		$user_id = -1;
	}else{
		$user_id = get_current_user_id();
	}
	
	//Insert booking, pending payment
	$booking = array(
		'booking_user' => $user_id,
		'booking_status' => 'pending',
		'event_id' => $event_id,
		'occurrence_id' => $occurrence_id,
		'tickets'=> $tickets,
		'gateway' => $gateway,
		'form' => $form, //Custom booking form
		'fname' => $fname, //These three are only used if $user_id === -1 (guest booking)
		'lname' => $lname,
		'email' => $email,
	);
	
	$booking_id = eo_insert_booking( $booking );
	$booking['booking_id'] = $booking_id;
	
	/* Last chance before sending! */
	do_action_ref_array( 'eventorganiser_pre_gateway_booking', array( $booking_id, $booking, $gateway, $form->errors, $form ) );
	do_action_ref_array( 'eventorganiser_pre_gateway_booking_'.$gateway, array( $booking_id, $booking, $form->errors, $form ) );
	
	/* Redirect to gateway... */
	//Free bookings don't need a gateway
	$total_price = eo_get_booking_meta( $booking_id, 'booking_amount' );
	if ( 0 == $total_price ) {
		//Free bookings are automatically confirmed
		$response = eo_confirm_booking( $booking_id, true );
		$redirect = add_query_arg( 'booking-confirmation', 'free', get_permalink( $form->get( 'page_id' ) ) ) . '#eo-bookings';
		wp_redirect( $redirect );
		exit();
	}
	
	switch ( $gateway ) {
		case 'paypal':
			if ( $total_price > 0 ) {
				$payment = new EO_Gateway_Paypal_Standard();
				$payment->return_url = add_query_arg( 'booking-confirmation', 'paypal', get_permalink( $form->get( 'page_id' ) ) );
				$payment->booking_cart( $booking );
			}
			break;
		case 'offline':
			$redirect = add_query_arg( 'booking-confirmation', 'offline', get_permalink( $form->get( 'page_id' ) ) ) . '#eo-bookings';
			wp_redirect( $redirect );
			exit();
			break;
	}
	
	//We shouldn't get this far unless there's been an error...
	if( !$form->has_errors() )
		$form->add_error( 'unknown', 'An unknown error has occurred' );
}
add_action( 'eventorganiser_process_booking_form_submission', '_eo_process_booking_submission', 100 );


/**
 * Confirms booking when cofirmation is recieved by gateway IPN handler.
 *
 * Hooked onto `eventorganiser_gateway_notification_transaction` - triggered by gateway handler
 *
 * @since 1.0
 * @ignore
 * @access private
 * @uses eo_confirm_booking()
 *
 * @param array   $log Gateway log containing booking ID and gateway transaction ID
 */
function eventorganiser_booking_payment_confirmed( $booking_id, $log ) {
	
	if( !empty( $log['transaction_id'] ) )
		update_post_meta( $booking_id, '_eo_booking_transaction_id', $log['transaction_id'] );

	//Confirm booking
	$response = eo_confirm_booking( $booking_id, true );
	$log['confirmation_response'] = $response;
	
	add_post_meta( $booking_id, '_eo_booking_gateway_log', $log );
}


/**
 * Ensures booking is marked as pending when a 'needs action' is recieved by gateway IPN handler.
 *
 * Hooked onto `eventorganiser_gateway_notification_pending`- triggered by gateway handler
 *
 * @since 1.0
 * @ignore
 * @access private
 *
 * @param array   $log Gateway log containing booking ID and gateway transaction ID
 */
function eventorganiser_booking_needs_action( $booking_id, $log ) {
	//MAYBELATER admin notification for gateway faliture.

	//Set status
	$booking = get_post( $booking_id, 'ARRAY_A' );
	$booking['post_status'] = 'pending';
	$booking['post_author'] = ( !empty( $booking['post_author'] ) ? $booking['post_author'] : -1 );
	
	wp_update_post( $booking );
	
	if( !empty( $log['transaction_id'] ) )
		update_post_meta( $booking_id, '_eo_booking_transaction_id', $log['transaction_id'] );
	
	add_post_meta( $booking_id, '_eo_booking_gateway_log', $log );
}




add_action( 'eventorganiser_action_cancel-booking','_eventorganiser_bookee_cancel_booking' );
function _eventorganiser_bookee_cancel_booking(){
	
	$booking_id = !empty( $_REQUEST['booking_id'] ) ? intval( $_REQUEST['booking_id'] ) : false;
	$return 	= !empty( $_REQUEST['return'] ) 	? intval( $_REQUEST['return'] ) 	: false;
	$nonce 		= !empty( $_REQUEST['n'] ) 			? $_REQUEST['n'] 					: false;
	
	$date = eo_get_booking_event_start_date( $booking_id, DATETIMEOBJ );
	$now = new DateTime( 'now', eo_get_blog_timezone() );

	if( get_post_type( $booking_id ) != 'eo_booking' ){
		return false;
	}
	
	if( !wp_verify_nonce( $nonce, 'cancel-booking-'.$booking_id.'-'.get_current_blog_id().'-'.$return ) ){
		wp_die( __( "You do not have permission to cancel this booking.", 'eventorganiserp' ) );
	}
	
	if( !is_user_logged_in() ){
		wp_die( __( "You must be logged in to cancel bookings.", 'eventorganiserp' ) );
	}

	if( $date <= $now ){
		wp_die( __( "This booking can no longer be cancelled.", 'eventorganiserp' ) );
	}
	
	if( eo_get_booking_meta( $booking_id, 'bookee' ) &&	get_current_user_id() == eo_get_booking_meta( $booking_id, 'bookee' )  ){
		
		$response = eo_cancel_booking( $booking_id );
		
		if( $response ){
			wp_redirect( add_query_arg(array(
				'success' 	 => 1,
				'booking_id' => $booking_id,
				'n' 		 => wp_create_nonce( 'cancelled-'.$booking_id ), 
			), get_permalink( $return ) ) );
			
		}else{
			wp_redirect( add_query_arg(array(
				'failure' 	 => 1,
				'booking_id' => $booking_id,
				'n' 		 => wp_create_nonce( 'failed-'.$booking_id ), 
			), get_permalink( $return ) ) );
		}
		
		exit();
		
	}else{
		wp_die( __( "You do not have permission to cancel this booking.", 'eventorganiserp' ) );
	}
	
}
?>
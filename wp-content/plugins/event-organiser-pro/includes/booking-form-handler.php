<?php
/**
 * Functions for outputing booking-form
 *
 * @package booking-form
 */

/**
 * Function responsible for producing booking form.
 *
 * Hooked onto {@see the_content}. Adds a booking form to the end of the
 * event content.
 *
 * @access private
 * @ignore
 * @since 1.0
 *
 * @param string  $content Post content
 * @return string Post content, maybe with form appended
 */
function eventorganiser_display_booking_table( $content ) {
	
	$p_id = get_the_ID();
	
	if( !eventorganiser_pro_get_option( 'disable_automatic_form', false ) && is_singular( 'event' ) && 'event' == get_post_type( $p_id ) ){
		$content .= eo_get_booking_form( $p_id );
	}
	
	return $content;
}
add_filter( 'the_content', 'eventorganiser_display_booking_table', 999 );

/**
 * Returns the HTML mark-up for booking form.
 * 
 * @since 1.4
 * @param int $event_id The ID of the event for which we are displaying the booking form
 * @return string HTML mark-up
 */
function eo_get_booking_form( $event_id = false ){
	
	$event_id = $event_id ? $event_id : get_the_ID();
	
	if ( 'event' != get_post_type( $event_id ) )
		return false;

	//Get tickets on sale *now* for this event
	$tickets = eo_get_event_tickets_on_sale( $event_id );
	
	//If no tickets don't show anything.
	if( empty( $tickets ) ){
		return false;
	}
	
	//Get enabled gateways
	$enabled_gateways = eventorganiser_get_enabled_gateways();
	$total_prices = ( $tickets ? eventorganiser_list_sum( $tickets, 'price' ) : 0 );
	
	//If no tickets or gateways (unless all tickets are free), don't show anything.
	if ( $total_prices > 0 && empty( $enabled_gateways ) ){
		
		if( !current_user_can( 'manage_options' ) ){
			return false;
		}
		
		return sprintf(
			'<div class="eo-booking-error">
				<p class="eo-booking-error-message eo-booking-error-no-gateway"> %s </p>
			</div>',
			__( '<strong>Error:</strong> This event has non-free tickets and no gateway has been enabled. Please enable a gateway in <em>Settings > Event Organiser > Bookings </em>. This notice only appears to admins.', 'eventorganiserp' )		
		);
	}

	if( !eventorganiser_get_option( 'disable_css' ) )
		wp_enqueue_style( 'eo_pro_frontend' );

	//Initialise form to display errors
	$form = eo_get_event_booking_form( $event_id );
	$form->set( 'event_id', $event_id );
	$form->set( 'page_id', get_the_ID() );
	
	//Event ID
	$form->add_element( new EO_Booking_Form_Element_Hidden( array(
		'id' => 'event_id',
		'value' => $form->get( 'event_id' )
	)));
	
	//Page ID
	$form->add_element( new EO_Booking_Form_Element_Hidden( array(
		'id' => 'page_id',
		'value' => $form->get( 'page_id' ),
	)));
	
	//Action
	$form->add_element( new EO_Booking_Form_Element_Hidden( array(
			'id' 			=> 'action',
			'field_name'	=> 'action',
			'value' 		=> 'eventorganiser-submit-form'
	)));
	
	//Form ID
	$form->add_element( new EO_Booking_Form_Element_Hidden( array(
			'id' 			=> 'form_id',
			'field_name'	=> 'eventorganiser-form-id',
			'field_id'		=> 'eventorganiser-form-id-'.$form->id,
			'value' 		=> $form->id,
	)));
	
	if( is_user_logged_in() ){
		$form->remove_element( 'name' );
		$form->remove_element( 'email' );
	}
	
	$form_view = new EO_Booking_Form_View( $form );
	return $form_view->render();

}

/**
 * @deprecated
 * @param unknown_type $args
 */
function eo_signup_form( $args = array() ){
	return false;
}
/**
 * @ignore
 * @param unknown_type $args
 * @return Ambigous <string, NULL>
 */
function eo_login_form( $args = array() ){
	add_filter( 'login_form_top', 'eventorganiser_pro_login_form_top' );
	add_filter( 'login_form_bottom', 'eventorganiser_pro_login_form_bottom' );
	return wp_login_form( $args );
}


/**
 * Function which inserts text at the top of the log-in form and then removes itself.
 * Hooked onto login_form_top
 *
 * @access private
 * @since 1.0
 * @ignore
 *
 * @param string  $top Content appearing above the log-in form
 * @return string  Above content with text pre-prended
 */
function eventorganiser_pro_login_form_top( $top ) {
	remove_filter( current_filter(), __FUNCTION__ );
	
	$login_prompt = '<div class="eo-booking-login-prompt">'
		.__( 'Already registered? <a href="#" class="eo-booking-login-toggle">Log-in</a>.', 'eventorganiserp' )
		.'</div>';
			
	if( eventorganiser_pro_get_option( 'allow_guest_booking' ) != 0 ){
	
		$login_prompt .= '<div class="eo-booking-no-account-prompt">'
				.__( 'Not got an account? <a href="#" class="eo-booking-no-account-toggle">Click here</a>.', 'eventorganiserp' )
			.'</div>';
	}	
		
	$login_prompt .= '<div class="eo-booking-form-login-form">'
		.'<input type="hidden" name="eo_username_or_email" value="1">' . $top;
					
	return $login_prompt . $top;
}

/**
 * Function which inserts lost password link at the bottom of the log-in form and then removes itself.
 * Hooked onto login_form_bottom
 *
 * @access private
 * @since 1.0
 * @ignore
 *
 * @param string  $bottom Content appearing below the log-in form
 * @return string  Above content with text pre-prended
 */
function eventorganiser_pro_login_form_bottom( $bottom ) {
	remove_filter( current_filter(), __FUNCTION__ );
	return sprintf( '<a href="%s" title="%s">%s</a>', esc_url( wp_lostpassword_url() ), esc_attr__( 'Password Lost and Found' ), __( 'Lost your password?' ) ) . $bottom . '</div>';
}


/**
 * If an email address is entered in the username box, then look up the matching username and authenticate as per normal, using that.
 *
 * @access private
 * @ignore
 * @since 1.0
 *
 * @param string  $user
 * @param string  $username
 * @param string  $password
 * @return Results of autheticating via wp_authenticate_username_password(), using the username found when looking up via email.
 */
function eventorganiser_pro_email_login_authenticate( $user, $username, $password ) {

	if ( !empty( $username ) && isset( $_POST['eo_username_or_email'] ) && 1 == $_POST['eo_username_or_email'] ) {
		$username = str_replace( '&', '&amp;', stripslashes( $username ) );
		if ( is_email( $username ) ) {
			$user = get_user_by( 'email', $username );
			if ( isset( $user, $user->user_login, $user->user_status ) && 0 == (int) $user->user_status )
				$username = $user->user_login;
			else
				$username = false;
		}
		remove_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
	}
	return wp_authenticate_username_password( null, $username, $password );
}
add_filter( 'authenticate', 'eventorganiser_pro_email_login_authenticate', 19, 3 );
?>
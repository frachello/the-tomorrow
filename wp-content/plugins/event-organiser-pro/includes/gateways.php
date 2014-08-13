<?php
/**
 * Gateway related functions
 *
 * @package payment-gateway
 */

/**
 * Gateway listener
 * @ignore
 */
function eventorganiser_gateway_listener() {
	
	$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
	
	if( $debug ){
		//error_log( "Checking for gateway IPN", 0 );
	}
	
	if ( isset( $_REQUEST['eo-listener'] ) ) {
		
		$action = strtolower( $_REQUEST['eo-listener'] );
		$gateway = strtolower( $_REQUEST['eo-gateway'] );
		
		if( $debug ){
			error_log( "============== Gateway found ==============", 0 );
			error_log( "Action: " . addslashes( $action ) , 0 );
			error_log( "Gateway: " . addslashes( $gateway ) , 0 );
		}

		do_action( 'eventorganiser_gateway_listener_'.$gateway.'_'.$action );
		
		if( $debug ){
			error_log( "============= END Gateway found =============", 0 );
		}
	}
}
add_action( 'init', 'eventorganiser_gateway_listener', 15 );

/**
 * Returns an array of available gateways. **Including gateways that are not live.**
 *
 * @see eventorganiser_get_enabled_gateways
 * @since 1.0
 *
 * @return array Array of the form ( key identifier => gateway label)
 */
function eventorganiser_get_gateways() {
	return apply_filters( 'eventorganiser_gateways', array( 'paypal' => 'PayPal', 'offline'=>'Offline Payment' ) );
}

/**
 * Returns an array of available gateway which are in *Live* or *Sandbox* mode
 *
 * @since 1.0
 *
 * @return array Array of the enabled gateways of the form ( key identifier => gateway label)
 */
function eventorganiser_get_enabled_gateways() {
	$gateways = eventorganiser_get_gateways();
	foreach ( $gateways as $id => $label ) {
		if ( eventorganiser_pro_get_option( $id.'_live_status' ) == -1 ) {
			unset( $gateways[$id] );
		}
	}
	return apply_filters( 'eventorganiser_enabled_gateways', $gateways );
}

/**
 * Register a gateway (child class of EO_Payment_Gateway) with Event Organiser. 
 * 
 * Pass the name of the class, e.g. 'EO_Payment_Gateway_My_Gateway'. If you the class 
 * has not been defined, provide the absolute path to the gateway via `$class_path`
 * 
 * @since 1.4
 * @param string $class The name of the class.
 * @param string $class_path Absolute path to the gateway. Or false if the class has already been defined.
 */
function eventorganiser_register_gateway( $class, $class_path = false ){
	static $registered_gateways;

	if( $class_path ){
		include_once( $class_path );
	}
	//php5.2 compatability $class::$gateway;
	$vars = get_class_vars($class);
	$gateway = $vars['gateway'];
	
	//php5.2 compatability $class::getInstance();
	$registered_gateways[$gateway] = call_user_func($class."::getInstance");
}


/**
 * Helper function for displaying a drop-down of months.
 * @uses eventorganiser_select_field()
 * @since 1.4
 * @param array $args Array of arguments. Passed to eventorganiser_select_field().
 * @return string
 */
function eo_form_select_month( $args = array() ){

	/* Insert form load scripts */
	global $wp_locale;

	$months = array();
	for( $m = 1; $m <= 12; $m++ ){
		$_m = sprintf("%02d", $m );
		$months[$_m] = sprintf( " %s - %s", $_m, $wp_locale->month[$_m] );
	}

	$args = array_merge( array(
			'name' => 'exp-month',
			'options' => $months,
			'style' => 'margin:0px;'
	), $args );

	return eventorganiser_select_field( $args );
}

/**
 * Helper function for displaying a drop-down of years, by default ranging
 * from this year to twenty years time.
 * @uses eventorganiser_select_field()
 * @since 1.4
 * @param array $args Array of arguments. Passed to eventorganiser_select_field().
 * @return string
 */
function eo_form_select_year( $args = array() ){

	/* Insert form load scripts */
	global $wp_locale;

	$year = (int) eo_format_date( 'now', 'Y' );
	$years = range( $year, $year + 20 );

	$args = array_merge( array(
			'name' => 'exp-year',
			'options' => array_combine( $years, $years ),
			'style' => 'margin:0px;'
	), $args );

	return eventorganiser_select_field( $args );
}

/**
 * Gets the 'booking complete' message for the specified gateway.
 * 
 * This is displayed if the user is returned to the booking page after the 
 * booking has been completed. For most gateways this is after payment has been 
 * made (e.g. PayPal). For some (e.g. Online) it is probably before.
 * @param string $gateway
 */
function eventorganiser_pro_get_booking_complete_message( $gateway ){
	
	switch( $gateway ){
		
		case 'offline':
			$message = __( 'Thank you for your booking. You shall receive an e-mail containing your tickets once we have confirmed payment', 'eventorganiserp' );
			break;
		
		case 'free':
			$message = __( 'Thank you for registering', 'eventorganiserp' ); 			
			break;
		
		case 'paypal':
		default:
			$message = __( 'Thank you for your booking. You shall receive an e-mail containing your tickets shortly', 'eventorganiserp' );
			break;	
	}
	
	//Backwards compat:
	$message = apply_filters( 'eventorganiser_pro_get_option_booking_complete_message', $message );
	$message = apply_filters( 'eventorganiser_pro_get_option_booking_complete_message_'.$gateway, $message );
	
	return $message;
}

?>